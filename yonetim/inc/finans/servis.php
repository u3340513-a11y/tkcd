<?php

declare(strict_types=1);

/**
 * Bir finans işleminin sonucu (başarı bayrağı + kullanıcıya gösterilecek mesaj).
 */
final class FinansSonuc
{
    private function __construct(public readonly bool $basarili, public readonly string $mesaj)
    {
    }

    public static function basari(string $mesaj): self
    {
        return new self(true, $mesaj);
    }

    public static function hata(string $mesaj): self
    {
        return new self(false, $mesaj);
    }
}

/**
 * Finans iş kuralları: doğrulama, kayıt, iptal ve kasa özeti.
 *
 * Yetki denetimi çağıran katmanda (finans-islem.php) yapılır; bu sınıf yalnızca
 * veri bütünlüğünden sorumludur. Kasa bakiyesi saklanmaz, her seferinde aktif
 * kayıtlardan hesaplanır — böylece her giriş/çıkış otomatik yansır ve bakiye
 * ile kayıtlar arasında tutarsızlık oluşamaz.
 */
final class FinansServisi
{
    public const AZAMI_ACIKLAMA = 500;
    public const AZAMI_TARAF    = 190;
    public const AZAMI_AD       = 150;
    public const EN_AZ_IPTAL_NEDENI = 3;
    public const AZAMI_IPTAL_NEDENI = 300;

    /** @var Closure(string, string, ?int): void */
    private readonly Closure $gunluk;

    /**
     * @param Closure(string, string, ?int): void $gunluk (işlem türü, açıklama, hedef kayıt id)
     */
    public function __construct(private readonly FinansDeposu $depo, Closure $gunluk)
    {
        $this->gunluk = $gunluk;
    }

    public function depo(): FinansDeposu
    {
        return $this->depo;
    }

    /**
     * Kasa özeti.
     *
     * @return array{aidat:int, bagis:int, devir:int, gider:int, gelir:int, bakiye:int}
     */
    public function kasaOzeti(): array
    {
        $t = $this->depo->turToplamlari();
        $aidat = $t[FinansDeposu::TUR_AIDAT]['kurus'] ?? 0;
        $bagis = $t[FinansDeposu::TUR_BAGIS]['kurus'] ?? 0;
        $devir = $t[FinansDeposu::TUR_DEVIR]['kurus'] ?? 0;
        $gider = $t[FinansDeposu::TUR_GIDER]['kurus'] ?? 0;
        $gelir = $aidat + $bagis + $devir;

        return ['aidat' => $aidat, 'bagis' => $bagis, 'devir' => $devir, 'gider' => $gider,
                'gelir' => $gelir, 'bakiye' => $gelir - $gider];
    }

    public function aidatUyesiEkle(string $ad): FinansSonuc
    {
        $ad = self::metniTemizle($ad);
        if ($ad === '' || mb_strlen($ad) > self::AZAMI_AD) {
            return FinansSonuc::hata('Ad soyad 1-' . self::AZAMI_AD . ' karakter olmalıdır.');
        }

        $geriAlinanId = $this->depo->aidatUyesiYenidenEtkinlestir($ad);
        if ($geriAlinanId !== null) {
            ($this->gunluk)('finans_kayit', 'Çıkarılmış kişi aidat listesine geri alındı (#' . $geriAlinanId . ').', $geriAlinanId);

            return FinansSonuc::basari('Kişi daha önce çıkarılmıştı; aidat listesine geri alındı.');
        }

        try {
            $id = $this->depo->aidatUyesiEkle($ad);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return FinansSonuc::hata('Bu isim aidat listesinde zaten var.');
            }
            throw $e;
        }
        ($this->gunluk)('finans_kayit', 'Aidat listesine kişi eklendi (#' . $id . ').', $id);

        return FinansSonuc::basari('Kişi aidat listesine eklendi.');
    }

    /**
     * Kişiyi aidat listesinden çıkarır. Kişi silinmez; geçmiş ödemeleri kasa toplamlarında kalır.
     */
    public function aidatUyesiCikar(int $id): FinansSonuc
    {
        $uye = $this->depo->aidatUyesiBul($id);
        if ($uye === null || !$this->depo->aidatUyesiCikar($id)) {
            return FinansSonuc::hata('Kişi aidat listesinde bulunamadı.');
        }
        ($this->gunluk)('finans_kayit', 'Kişi aidat listesinden çıkarıldı (#' . $id . ').', $id);

        return FinansSonuc::basari($uye['ad_soyad'] . ' aidat listesinden çıkarıldı. Geçmiş ödemeleri kasada korunur.');
    }

    /**
     * @param array{aidat_uye_id?:mixed, yil?:mixed, ay?:mixed, tarih?:mixed, tutar?:mixed, aciklama?:mixed} $girdi
     */
    public function aidatKaydet(array $girdi, string $kullanici): FinansSonuc
    {
        $uye = $this->depo->aidatUyesiBul((int) ($girdi['aidat_uye_id'] ?? 0));
        if ($uye === null) {
            return FinansSonuc::hata('Seçilen kişi aidat listesinde bulunamadı.');
        }

        $yil = (int) ($girdi['yil'] ?? 0);
        $ay  = (int) ($girdi['ay'] ?? 0);
        if (!FinansDonem::gecerliMi($yil, $ay)) {
            return FinansSonuc::hata('Geçerli bir aidat dönemi (yıl/ay) seçin.');
        }

        $tarih = FinansDonem::tarihiNormallestir((string) ($girdi['tarih'] ?? ''));
        $kurus = FinansTutar::kurusaCevir((string) ($girdi['tutar'] ?? ''));
        $aciklama = self::metniTemizle((string) ($girdi['aciklama'] ?? ''));
        if ($tarih === null) {
            return FinansSonuc::hata('Geçerli bir ödeme tarihi girin.');
        }
        if ($kurus === null) {
            return FinansSonuc::hata('Geçerli bir tutar girin (örn. 1.500,00).');
        }
        if (mb_strlen($aciklama) > self::AZAMI_ACIKLAMA) {
            return FinansSonuc::hata('Açıklama en fazla ' . self::AZAMI_ACIKLAMA . ' karakter olabilir.');
        }

        try {
            $id = $this->depo->hareketEkle([
                'tur' => FinansDeposu::TUR_AIDAT, 'tarih' => $tarih, 'donem_yil' => $yil, 'donem_ay' => $ay,
                'tutar_kurus' => $kurus, 'aidat_uye_id' => (int) $uye['id'],
                'aciklama' => $aciklama !== '' ? $aciklama : null,
                'aidat_anahtari' => self::aidatAnahtari((int) $uye['id'], $yil, $ay),
                'olusturan' => $kullanici,
            ]);
        } catch (PDOException $e) {
            if ($e->getCode() === '23000') {
                return FinansSonuc::hata(
                    $uye['ad_soyad'] . ' için ' . FinansDonem::AY_ADLARI[$ay] . ' ' . $yil
                    . ' aidatı zaten kayıtlı. Düzeltmek için önce mevcut kaydı iptal edin.'
                );
            }
            throw $e;
        }
        ($this->gunluk)('finans_kayit', 'Aidat ödemesi kaydedildi (#' . $id . ', ' . $yil . '/' . $ay . ').', $id);

        return FinansSonuc::basari(
            $uye['ad_soyad'] . ' — ' . FinansDonem::AY_ADLARI[$ay] . ' ' . $yil . ' aidatı kaydedildi ('
            . FinansTutar::bicimle($kurus) . ').'
        );
    }

    /**
     * Bağış veya gider kaydı ekler (iki tür aynı alanlara sahiptir).
     *
     * @param array{tarih?:mixed, taraf?:mixed, aciklama?:mixed, tutar?:mixed} $girdi
     */
    public function bagisGiderKaydet(string $tur, array $girdi, string $kullanici): FinansSonuc
    {
        if (!in_array($tur, [FinansDeposu::TUR_BAGIS, FinansDeposu::TUR_GIDER], true)) {
            return FinansSonuc::hata('Geçersiz kayıt türü.');
        }
        $etiket = $tur === FinansDeposu::TUR_BAGIS ? 'Bağış' : 'Gider';

        $tarih = FinansDonem::tarihiNormallestir((string) ($girdi['tarih'] ?? ''));
        $taraf = self::metniTemizle((string) ($girdi['taraf'] ?? ''));
        $aciklama = self::metniTemizle((string) ($girdi['aciklama'] ?? ''));
        $kurus = FinansTutar::kurusaCevir((string) ($girdi['tutar'] ?? ''));

        if ($tarih === null) {
            return FinansSonuc::hata('Geçerli bir tarih girin.');
        }
        if ($taraf === '' || mb_strlen($taraf) > self::AZAMI_TARAF) {
            return FinansSonuc::hata(($tur === FinansDeposu::TUR_BAGIS ? 'Firma adı' : 'Ödenen yer / firma')
                . ' 1-' . self::AZAMI_TARAF . ' karakter olmalıdır.');
        }
        if ($kurus === null) {
            return FinansSonuc::hata('Geçerli bir tutar girin (örn. 1.800,00).');
        }
        if (mb_strlen($aciklama) > self::AZAMI_ACIKLAMA) {
            return FinansSonuc::hata('Açıklama en fazla ' . self::AZAMI_ACIKLAMA . ' karakter olabilir.');
        }

        $id = $this->depo->hareketEkle([
            'tur' => $tur, 'tarih' => $tarih,
            'donem_yil' => (int) substr($tarih, 0, 4), 'donem_ay' => (int) substr($tarih, 5, 2),
            'tutar_kurus' => $kurus, 'taraf' => $taraf,
            'aciklama' => $aciklama !== '' ? $aciklama : null, 'olusturan' => $kullanici,
        ]);
        ($this->gunluk)('finans_kayit', $etiket . ' kaydı eklendi (#' . $id . ').', $id);

        return FinansSonuc::basari($etiket . ' kaydı eklendi (' . FinansTutar::bicimle($kurus) . ').');
    }

    /** Kaydı siler değil, iptal eder; kasa toplamı anında düzelir. */
    public function iptalEt(int $id, string $neden, string $kullanici): FinansSonuc
    {
        $neden = self::metniTemizle($neden);
        if (mb_strlen($neden) < self::EN_AZ_IPTAL_NEDENI || mb_strlen($neden) > self::AZAMI_IPTAL_NEDENI) {
            return FinansSonuc::hata('İptal nedeni ' . self::EN_AZ_IPTAL_NEDENI . '-' . self::AZAMI_IPTAL_NEDENI . ' karakter olmalıdır.');
        }
        if ($this->depo->hareketBul($id) === null) {
            return FinansSonuc::hata('Kayıt bulunamadı.');
        }
        if (!$this->depo->hareketIptalEt($id, $neden, $kullanici)) {
            return FinansSonuc::hata('Kayıt zaten iptal edilmiş.');
        }
        ($this->gunluk)('finans_iptal', 'Finans kaydı iptal edildi (#' . $id . ').', $id);

        return FinansSonuc::basari('Kayıt iptal edildi; kasa toplamı güncellendi. Kayıt geçmişte saklanmaya devam eder.');
    }

    /** Aynı kişi + dönem için tek aktif aidat garantisi veren anahtar. */
    public static function aidatAnahtari(int $uyeId, int $yil, int $ay): string
    {
        return $uyeId . '-' . $yil . '-' . $ay;
    }

    /** Kontrol karakterlerini atar, baştaki/sondaki boşlukları keser. */
    public static function metniTemizle(string $metin): string
    {
        return trim((string) preg_replace('/[\x00-\x1F\x7F]/u', ' ', $metin));
    }
}
