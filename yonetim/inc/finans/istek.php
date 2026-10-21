<?php

declare(strict_types=1);

/**
 * Finans formlarından (POST) gelen istekleri yetki + CSRF denetiminden geçirip servislere yönlendirir.
 *
 * Her işlem için gereken yetkiler ISLEMLER tablosunda tanımlıdır; yetkisiz istek
 * hiçbir servis çağrısına ulaşmaz. Dosya yükleme (CSV) burada doğrulanır ve dosya
 * diske kaydedilmez, yalnızca bellekte okunup atılır.
 */
final class FinansIstekYoneticisi
{
    /** İşlem => [gereken yetkiler (hepsi), dönülecek sekme] */
    private const ISLEMLER = [
        'aidat_kaydet'   => [[FinansYetki::KAYIT_GIR, FinansYetki::AIDAT_GORUNTULE], 'aidat'],
        'aidat_uye_ekle' => [[FinansYetki::KAYIT_GIR, FinansYetki::AIDAT_GORUNTULE], 'aidat'],
        'bagis_kaydet'   => [[FinansYetki::KAYIT_GIR, FinansYetki::KASA_GORUNTULE], 'bagis'],
        'gider_kaydet'   => [[FinansYetki::KAYIT_GIR, FinansYetki::KASA_GORUNTULE], 'gider'],
        'iptal_et'       => [[FinansYetki::KAYIT_GIR, FinansYetki::KASA_GORUNTULE], 'kasa'],
        'aktar_onizle'   => [[FinansYetki::AKTARIM, FinansYetki::AIDAT_GORUNTULE, FinansYetki::KASA_GORUNTULE], 'aktar'],
        'aktar_onayla'   => [[FinansYetki::AKTARIM, FinansYetki::AIDAT_GORUNTULE, FinansYetki::KASA_GORUNTULE], 'aktar'],
    ];

    public const AZAMI_DOSYA_BAYTI = 1048576;
    public const ONIZLEME_OMRU_SANIYE = 1800;
    public const SEKMELER = ['kasa', 'aidat', 'bagis', 'gider', 'raporlar', 'aktar'];

    private const IZINLI_MIME = ['text/plain', 'text/csv', 'application/csv', 'application/vnd.ms-excel', 'inode/x-empty'];

    /**
     * @param Closure(string): bool $yetkiVarMi
     */
    public function __construct(
        private readonly FinansServisi $servis,
        private readonly FinansAktarimServisi $aktarim,
        private readonly Closure $yetkiVarMi,
        private readonly string $kullanici,
    ) {
    }

    /**
     * İsteği işler ve yönlendirme bilgisini döndürür.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $dosyalar $_FILES
     * @param array<string, mixed> $oturum   Oturum dizisi (referans: önizleme verisi burada tutulur)
     * @return array{sekme:string, yil:int, tip:string, mesaj:string}
     */
    public function isle(array $post, array $dosyalar, array &$oturum): array
    {
        $yil = (int) ($post['yil'] ?? date('Y'));
        $islem = (string) ($post['islem'] ?? '');
        $sekme = self::ISLEMLER[$islem][1] ?? 'kasa';

        if (!isset(self::ISLEMLER[$islem])) {
            return $this->sonuc('kasa', $yil, FinansSonuc::hata('Geçersiz işlem.'));
        }
        foreach (self::ISLEMLER[$islem][0] as $yetki) {
            if (!($this->yetkiVarMi)($yetki)) {
                return $this->sonuc('kasa', $yil, FinansSonuc::hata('Bu işlem için yetkiniz yok.'));
            }
        }

        try {
            $sonuc = match ($islem) {
                'aidat_kaydet'   => $this->servis->aidatKaydet($post, $this->kullanici),
                'aidat_uye_ekle' => $this->servis->aidatUyesiEkle((string) ($post['ad_soyad'] ?? '')),
                'bagis_kaydet'   => $this->servis->bagisGiderKaydet(FinansDeposu::TUR_BAGIS, $post, $this->kullanici),
                'gider_kaydet'   => $this->servis->bagisGiderKaydet(FinansDeposu::TUR_GIDER, $post, $this->kullanici),
                'iptal_et'       => $this->iptalEt($post),
                'aktar_onizle'   => $this->onizle($dosyalar, $oturum),
                'aktar_onayla'   => $this->onayla($post, $oturum),
            };
            if ($islem === 'iptal_et') {
                $sekme = (string) ($post['sekme'] ?? 'kasa');
            }
        } catch (Throwable $e) {
            error_log('[FINANS] ' . $islem . ' hatası: ' . $e->getMessage());
            $sonuc = FinansSonuc::hata('İşlem sırasında beklenmeyen bir hata oluştu. Hiçbir veri değiştirilmedi.');
        }

        return $this->sonuc($sekme, $yil, $sonuc);
    }

    /** @param array<string, mixed> $post */
    private function iptalEt(array $post): FinansSonuc
    {
        $hareket = $this->servis->depo()->hareketBul((int) ($post['hareket_id'] ?? 0));
        if ($hareket === null) {
            return FinansSonuc::hata('Kayıt bulunamadı.');
        }
        // Aidat kayıtlarının kişi bilgisi ayrı yetkiyle korunur.
        if ($hareket['tur'] === FinansDeposu::TUR_AIDAT && !($this->yetkiVarMi)(FinansYetki::AIDAT_GORUNTULE)) {
            return FinansSonuc::hata('Bu işlem için yetkiniz yok.');
        }

        return $this->servis->iptalEt((int) $hareket['id'], (string) ($post['neden'] ?? ''), $this->kullanici);
    }

    /**
     * Yüklenen CSV'leri okur, doğrular ve ONAY BEKLEYEN önizleme olarak oturuma koyar (veritabanına yazmaz).
     *
     * @param array<string, mixed> $dosyalar
     * @param array<string, mixed> $oturum
     */
    private function onizle(array $dosyalar, array &$oturum): FinansSonuc
    {
        unset($oturum['finans_aktarim']);
        $onizleme = ['jeton' => bin2hex(random_bytes(16)), 'zaman' => time(), 'aidat' => null, 'bagis' => null, 'gider' => null];
        $mesajlar = [];
        $basarili = false;

        $alanlar = ['aidat' => 'aidatListesi', 'bagis' => 'kayitListesi', 'gider' => 'kayitListesi'];
        foreach ($alanlar as $alan => $ayristirici) {
            $icerik = $this->yuklenenIcerik($dosyalar, 'dosya_' . $alan);
            if ($icerik === null) {
                continue;
            }
            if ($icerik === false) {
                $mesajlar[] = ucfirst($alan) . ' dosyası geçersiz (yalnızca ≤ 1 MB CSV kabul edilir).';
                continue;
            }
            $ayrisan = FinansCsvAyristirici::{$ayristirici}(FinansCsvAyristirici::satirlar($icerik));
            if ($ayrisan['hatalar'] !== []) {
                $mesajlar[] = ucfirst($alan) . ': ' . implode(' ', $ayrisan['hatalar']);
                continue;
            }
            $onizleme[$alan] = $ayrisan;
            $basarili = true;
        }

        if (!$basarili) {
            return FinansSonuc::hata($mesajlar !== [] ? implode(' | ', $mesajlar) : 'En az bir CSV dosyası seçin.');
        }
        $oturum['finans_aktarim'] = $onizleme;

        return FinansSonuc::basari('Önizleme hazır. Aşağıdaki özeti kontrol edip onaylayın; henüz hiçbir kayıt yazılmadı.'
            . ($mesajlar !== [] ? ' Uyarı: ' . implode(' | ', $mesajlar) : ''));
    }

    /**
     * Önizlemeyi (kullanıcının seçimleriyle) veritabanına yazar.
     *
     * @param array<string, mixed> $post
     * @param array<string, mixed> $oturum
     */
    private function onayla(array $post, array &$oturum): FinansSonuc
    {
        $onizleme = $oturum['finans_aktarim'] ?? null;
        if (!is_array($onizleme)
            || !hash_equals((string) $onizleme['jeton'], (string) ($post['jeton'] ?? ''))
            || time() - (int) $onizleme['zaman'] > self::ONIZLEME_OMRU_SANIYE) {
            unset($oturum['finans_aktarim']);

            return FinansSonuc::hata('Önizleme süresi doldu veya geçersiz. Dosyaları yeniden yükleyin.');
        }

        $sonuc = $this->aktarim->uygula(
            $onizleme['aidat'],
            array_map('strval', (array) ($post['devir_sec'] ?? [])),
            self::secilenKayitlar($onizleme['bagis']['kayitlar'] ?? [], (array) ($post['bagis_sec'] ?? [])),
            self::secilenKayitlar($onizleme['gider']['kayitlar'] ?? [], (array) ($post['gider_sec'] ?? [])),
            $this->kullanici
        );
        unset($oturum['finans_aktarim']);

        return FinansSonuc::basari(
            'Aktarım tamamlandı. Aidat: ' . $sonuc['aidat_eklendi'] . ' eklendi, ' . $sonuc['aidat_zaten'] . ' zaten vardı, '
            . $sonuc['aidat_cakisma'] . ' çakıştı (elle girilmiş kayıt korundu). Kişi: ' . $sonuc['uye_eklendi'] . '. Devir: '
            . $sonuc['devir_eklendi'] . '. Bağış: ' . $sonuc['bagis_eklendi'] . ' (' . $sonuc['bagis_zaten'] . ' zaten vardı). Gider: '
            . $sonuc['gider_eklendi'] . ' (' . $sonuc['gider_zaten'] . ' zaten vardı).'
        );
    }

    /**
     * @param array<int, array<string, mixed>> $kayitlar
     * @param array<int, mixed>                $secilenIndeksler
     * @return array<int, array<string, mixed>>
     */
    private static function secilenKayitlar(array $kayitlar, array $secilenIndeksler): array
    {
        $secilen = [];
        foreach ($secilenIndeksler as $indeks) {
            if (is_string($indeks) && ctype_digit($indeks) && isset($kayitlar[(int) $indeks])) {
                $secilen[(int) $indeks] = $kayitlar[(int) $indeks];
            }
        }
        ksort($secilen);

        return array_values($secilen);
    }

    /**
     * Yüklenen dosyayı doğrular ve içeriğini döndürür.
     *
     * Çıktı: içerik metni; dosya seçilmediyse null; geçersizse false.
     *
     * @param array<string, mixed> $dosyalar
     */
    private function yuklenenIcerik(array $dosyalar, string $alan): string|false|null
    {
        $dosya = $dosyalar[$alan] ?? null;
        if (!is_array($dosya) || ($dosya['error'] ?? UPLOAD_ERR_NO_FILE) === UPLOAD_ERR_NO_FILE) {
            return null;
        }
        $gecici = (string) ($dosya['tmp_name'] ?? '');
        if (($dosya['error'] ?? 0) !== UPLOAD_ERR_OK || !is_uploaded_file($gecici)
            || (int) ($dosya['size'] ?? 0) > self::AZAMI_DOSYA_BAYTI
            || !in_array(strtolower(pathinfo((string) ($dosya['name'] ?? ''), PATHINFO_EXTENSION)), ['csv', 'txt'], true)
            || !in_array((new finfo(FILEINFO_MIME_TYPE))->file($gecici), self::IZINLI_MIME, true)) {
            return false;
        }
        $icerik = file_get_contents($gecici);

        return ($icerik === false || str_contains($icerik, "\0")) ? false : $icerik;
    }

    /** @return array{sekme:string, yil:int, tip:string, mesaj:string} */
    private function sonuc(string $sekme, int $yil, FinansSonuc $sonuc): array
    {
        return [
            'sekme' => in_array($sekme, self::SEKMELER, true) ? $sekme : 'kasa',
            'yil'   => FinansDonem::gecerliMi($yil, 1) ? $yil : (int) date('Y'),
            'tip'   => $sonuc->basarili ? 'success' : 'danger',
            'mesaj' => $sonuc->mesaj,
        ];
    }
}
