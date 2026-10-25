<?php

declare(strict_types=1);

/**
 * Finans modülü yetki matrisi.
 *
 * Neden var: Finans yetkileri rolden, tam kullanıcı adından veya kullanıcı adı
 * önekinden (yonetim_*) verilebilir. Kurallar tek yerde toplandığı için yetki
 * genişletmek yalnızca KURALLAR tablosunu değiştirmeyi gerektirir; sayfa, işlem
 * ve menü kodlarına dokunulmaz.
 *
 * Yetki dağılımı:
 *   - KASA_GORUNTULE, AIDAT_GORUNTULE, KAYIT_GIR : geliştirici + admin61 + yonetim_id
 *   - DISA_AKTARIM, IPTAL_GORUNTULE, AKTARIM     : yalnızca geliştirici
 *   - UYE_CIKAR                                  : geliştirici + admin61
 * Planlanan genişletme: KASA_GORUNTULE için 'kullanici_onekleri' => ['yonetim_'].
 */
final class FinansYetki
{
    /** Kasa bakiyesi, aylık özetler ve bağış/gider kayıtlarını görme. */
    public const KASA_GORUNTULE = 'kasa_goruntule';

    /** Aidatı kimin verip vermediğini (isimli aidat listesini) görme. */
    public const AIDAT_GORUNTULE = 'aidat_goruntule';

    /** Aidat/bağış/gider girişi yapma ve kaydı iptal etme. */
    public const KAYIT_GIR = 'kayit_gir';

    /** Kişiyi aidat listesinden çıkarma. */
    public const UYE_CIKAR = 'uye_cikar';

    /** Excel (CSV) aktarımı. */
    public const AKTARIM = 'aktarim';

    /** Kayıtları CSV olarak indirme. */
    public const DISA_AKTARIM = 'disa_aktarim';

    /** İptal edilmiş kayıtları listede görme. */
    public const IPTAL_GORUNTULE = 'iptal_goruntule';

    private const KURALLAR = [
        self::KASA_GORUNTULE   => ['roller' => ['gelistirici'], 'kullanicilar' => ['admin61', 'yonetim_id'], 'kullanici_onekleri' => []],
        self::AIDAT_GORUNTULE  => ['roller' => ['gelistirici'], 'kullanicilar' => ['admin61', 'yonetim_id'], 'kullanici_onekleri' => []],
        self::KAYIT_GIR        => ['roller' => ['gelistirici'], 'kullanicilar' => ['admin61', 'yonetim_id'], 'kullanici_onekleri' => []],
        self::UYE_CIKAR        => ['roller' => ['gelistirici'], 'kullanicilar' => ['admin61'], 'kullanici_onekleri' => []],
        self::AKTARIM          => ['roller' => ['gelistirici'], 'kullanicilar' => [], 'kullanici_onekleri' => []],
        self::DISA_AKTARIM     => ['roller' => ['gelistirici'], 'kullanicilar' => [], 'kullanici_onekleri' => []],
        self::IPTAL_GORUNTULE  => ['roller' => ['gelistirici'], 'kullanicilar' => [], 'kullanici_onekleri' => []],
    ];

    /**
     * Verilen rol/kullanıcı adının yetkiye sahip olup olmadığını döndürür.
     *
     * Girdi : yetki sabiti, oturumdaki rol, oturumdaki kullanıcı adı.
     * Çıktı : yetki varsa true; bilinmeyen yetkide her zaman false.
     */
    public static function izinliMi(string $yetki, string $rol, string $kullaniciAdi): bool
    {
        $kural = self::KURALLAR[$yetki] ?? null;
        if ($kural === null) {
            return false;
        }
        if (in_array($rol, $kural['roller'], true) || in_array($kullaniciAdi, $kural['kullanicilar'], true)) {
            return true;
        }
        foreach ($kural['kullanici_onekleri'] as $onek) {
            if ($kullaniciAdi !== '' && str_starts_with($kullaniciAdi, $onek)) {
                return true;
            }
        }

        return false;
    }

    /** Oturumdaki kullanıcı için yetki kontrolü (hesap geçişinde geçilen hesabın bilgisi kullanılır). */
    public static function oturumIcinVarMi(string $yetki): bool
    {
        if (($_SESSION['oturum'] ?? false) !== true) {
            return false;
        }

        return self::izinliMi($yetki, (string) ($_SESSION['rol'] ?? ''), (string) ($_SESSION['kullanici_adi'] ?? ''));
    }

    /** Finans menüsünü/sayfasını açabilecek kadar herhangi bir görüntüleme yetkisi var mı? */
    public static function menuGorunurMu(): bool
    {
        return self::oturumIcinVarMi(self::KASA_GORUNTULE) || self::oturumIcinVarMi(self::AIDAT_GORUNTULE);
    }
}
