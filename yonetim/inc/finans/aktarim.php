<?php

declare(strict_types=1);

/**
 * Ayrıştırılmış Excel/CSV verisini TEK İŞLEMDE (transaction) veritabanına yazar.
 *
 * Güvenlik/veri bütünlüğü:
 *  - Herhangi bir hatada tüm işlem geri alınır; yarım aktarım olmaz.
 *  - Idempotent: aynı dosya tekrar yüklense de mükerrer kayıt oluşmaz
 *    (`kaynak_anahtari`), iptal edilmiş kayıtlar da yeniden doğmaz.
 *  - Aynı kişi+ay için elle girilmiş aktif aidat varsa üzerine yazılmaz, "çakışma" sayılır.
 *  - Aidat tarihi Excel'de tutulmadığından ilgili ayın 1'i yazılır (kaynak = 'excel').
 */
final class FinansAktarimServisi
{
    public const KAYNAK_EXCEL = 'excel';
    public const NET_DEVIR_ANAHTARI = 'net';

    /** @var Closure(string, string, ?int): void */
    private readonly Closure $gunluk;

    /**
     * @param Closure(string, string, ?int): void $gunluk
     */
    public function __construct(private readonly FinansDeposu $depo, Closure $gunluk)
    {
        $this->gunluk = $gunluk;
    }

    /**
     * @param array<string, mixed>|null $aidat           aidatListesi() çıktısı
     * @param string[]                  $devirAnahtarlari Kasaya "devir" yazılacak farklar: "yil-ay"
     * @param array<int, array{tarih:string, taraf:string, aciklama:string, kurus:int}> $bagislar
     * @param array<int, array{tarih:string, taraf:string, aciklama:string, kurus:int}> $giderler
     *
     * @return array<string, int>  Sayaçlar (eklenen / zaten var / çakışan)
     *
     * @throws Throwable Hata olursa işlem geri alınır ve istisna yeniden fırlatılır.
     */
    public function uygula(?array $aidat, array $devirAnahtarlari, array $bagislar, array $giderler, string $kullanici): array
    {
        $db = $this->depo->baglanti();
        $sayac = array_fill_keys([
            'uye_eklendi', 'aidat_eklendi', 'aidat_zaten', 'aidat_cakisma',
            'devir_eklendi', 'devir_zaten', 'bagis_eklendi', 'bagis_zaten', 'gider_eklendi', 'gider_zaten',
        ], 0);

        $db->beginTransaction();
        try {
            if ($aidat !== null) {
                $this->aidatiYaz($aidat, $kullanici, $sayac);
                $this->devirleriYaz($aidat, $devirAnahtarlari, $kullanici, $sayac);
            }
            $this->kayitlariYaz(FinansDeposu::TUR_BAGIS, $bagislar, $kullanici, $sayac, 'bagis');
            $this->kayitlariYaz(FinansDeposu::TUR_GIDER, $giderler, $kullanici, $sayac, 'gider');
            $db->commit();
        } catch (Throwable $e) {
            if ($db->inTransaction()) {
                $db->rollBack();
            }
            throw $e;
        }

        ($this->gunluk)('finans_aktarim', 'Excel aktarımı tamamlandı: ' . json_encode($sayac), null);

        return $sayac;
    }

    /**
     * @param array<string, mixed> $aidat
     * @param array<string, int>   $sayac
     */
    private function aidatiYaz(array $aidat, string $kullanici, array &$sayac): void
    {
        $idler = [];
        foreach ($aidat['uyeler'] as $uye) {
            $id = $this->depo->aidatUyesiAdlaBul($uye['ad']);
            if ($id === null) {
                $id = $this->depo->aidatUyesiEkle($uye['ad'], $uye['sira']);
                $sayac['uye_eklendi']++;
            }
            $idler[$uye['ad']] = $id;
        }

        foreach ($aidat['odemeler'] as $odeme) {
            $uyeId = $idler[$odeme['ad']] ?? throw new RuntimeException('Aidat ödemesi listede olmayan bir kişiye ait: ' . $odeme['ad']);
            $kaynak = hash('sha256', 'aidat|' . $odeme['ad'] . '|' . $odeme['yil'] . '|' . $odeme['ay']);
            $aidatAnahtari = FinansServisi::aidatAnahtari($uyeId, $odeme['yil'], $odeme['ay']);

            if ($this->depo->kaynakAnahtariVarMi($kaynak)) {
                $sayac['aidat_zaten']++;
            } elseif ($this->depo->aidatAnahtariVarMi($aidatAnahtari)) {
                $sayac['aidat_cakisma']++;
            } else {
                $this->depo->hareketEkle([
                    'tur' => FinansDeposu::TUR_AIDAT, 'tarih' => sprintf('%04d-%02d-01', $odeme['yil'], $odeme['ay']),
                    'donem_yil' => $odeme['yil'], 'donem_ay' => $odeme['ay'], 'tutar_kurus' => $odeme['kurus'],
                    'aidat_uye_id' => $uyeId, 'aciklama' => 'Excel aktarımı', 'kaynak' => self::KAYNAK_EXCEL,
                    'kaynak_anahtari' => $kaynak, 'aidat_anahtari' => $aidatAnahtari, 'olusturan' => $kullanici,
                ]);
                $sayac['aidat_eklendi']++;
            }
        }
    }

    /**
     * "Sayfa toplamı − kişi satırları toplamı" farklarının NET tutarını (pozitif ise) tek "devir" kaydı olarak yazar.
     *
     * Neden net: bir ayda eksik, başka ayda fazla gösterilen tutarlar birbirini götürür; yalnızca
     * pozitif farkları eklemek kasayı sayfadaki toplamdan saptırır. Ay ayrıntıları açıklamaya yazılır.
     *
     * @param array<string, mixed> $aidat
     * @param string[]             $secilenler NET_DEVIR_ANAHTARI içeriyorsa yazılır
     * @param array<string, int>   $sayac
     */
    private function devirleriYaz(array $aidat, array $secilenler, string $kullanici, array &$sayac): void
    {
        $net = array_sum(array_column($aidat['farklar'], 'fark'));
        if ($net <= 0 || !in_array(self::NET_DEVIR_ANAHTARI, $secilenler, true)) {
            return;
        }
        $kaynak = hash('sha256', 'devir|net');
        if ($this->depo->kaynakAnahtariVarMi($kaynak)) {
            $sayac['devir_zaten']++;

            return;
        }
        $ayrinti = implode(', ', array_map(
            static fn (array $f): string => FinansDonem::AY_ADLARI[$f['ay']] . ' ' . $f['yil'] . ' ' . ($f['fark'] > 0 ? '+' : '−') . FinansTutar::bicimle(abs($f['fark'])),
            $aidat['farklar']
        ));
        $ilk = $aidat['farklar'][0];
        $this->depo->hareketEkle([
            'tur' => FinansDeposu::TUR_DEVIR, 'tarih' => sprintf('%04d-%02d-01', $ilk['yil'], $ilk['ay']),
            'donem_yil' => $ilk['yil'], 'donem_ay' => $ilk['ay'], 'tutar_kurus' => $net,
            'taraf' => 'Excel mutabakat farkı',
            'aciklama' => mb_substr('Excel "Aylık Toplam" ile kişi satırları arasındaki net fark: ' . $ayrinti, 0, FinansServisi::AZAMI_ACIKLAMA),
            'kaynak' => self::KAYNAK_EXCEL, 'kaynak_anahtari' => $kaynak, 'olusturan' => $kullanici,
        ]);
        $sayac['devir_eklendi']++;
    }

    /**
     * @param array<int, array{tarih:string, taraf:string, aciklama:string, kurus:int}> $kayitlar
     * @param array<string, int> $sayac
     */
    private function kayitlariYaz(string $tur, array $kayitlar, string $kullanici, array &$sayac, string $sayacOnek): void
    {
        $tekrar = [];
        foreach ($kayitlar as $kayit) {
            $parmakIzi = $tur . '|' . $kayit['tarih'] . '|' . $kayit['taraf'] . '|' . $kayit['aciklama'] . '|' . $kayit['kurus'];
            $tekrar[$parmakIzi] = ($tekrar[$parmakIzi] ?? 0) + 1;
            $kaynak = hash('sha256', $parmakIzi . '|' . $tekrar[$parmakIzi]);

            if ($this->depo->kaynakAnahtariVarMi($kaynak)) {
                $sayac[$sayacOnek . '_zaten']++;
                continue;
            }
            $this->depo->hareketEkle([
                'tur' => $tur, 'tarih' => $kayit['tarih'],
                'donem_yil' => (int) substr($kayit['tarih'], 0, 4), 'donem_ay' => (int) substr($kayit['tarih'], 5, 2),
                'tutar_kurus' => $kayit['kurus'], 'taraf' => $kayit['taraf'],
                'aciklama' => $kayit['aciklama'] !== '' ? $kayit['aciklama'] : null,
                'kaynak' => self::KAYNAK_EXCEL, 'kaynak_anahtari' => $kaynak, 'olusturan' => $kullanici,
            ]);
            $sayac[$sayacOnek . '_eklendi']++;
        }
    }
}
