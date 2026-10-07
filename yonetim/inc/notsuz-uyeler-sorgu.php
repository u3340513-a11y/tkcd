<?php

declare(strict_types=1);

/**
 * Notu olmayan üyeler raporu.
 *
 * Neden var: Sayfa önizlemesi ve Excel indirmesi aynı sorguyu kullanmalı;
 * sorgu ve CSV güvenliği tek yerde tutulur (DRY).
 */
final class NotsuzUyeRaporu
{
    public const CINSIYET_HER_IKISI = 'hepsi';

    /** Rapor filtresinde seçilebilen cinsiyet değerleri (veritabanındaki yazımla aynı). */
    public const CINSIYETLER = ['Kadın', 'Erkek'];

    /** CSV hücresinde formül olarak yorumlanabilecek başlangıç karakterleri. */
    private const FORMUL_KARAKTERLERI = ['=', '+', '-', '@', "\t", "\r"];

    public function __construct(private PDO $db)
    {
    }

    /** Gelen değeri geçerli bir filtre değerine indirger; geçersizse null döner. */
    public static function cinsiyetDogrula(string $deger): ?string
    {
        if ($deger === self::CINSIYET_HER_IKISI || in_array($deger, self::CINSIYETLER, true)) {
            return $deger;
        }
        return null;
    }

    /**
     * Onaylı, cinsiyeti seçilmiş ve hiç notu bulunmayan üyeleri döner.
     *
     * Girdi : cinsiyetDogrula() ile doğrulanmış değer (Kadın | Erkek | hepsi)
     * Çıktı : üye satırları (ad sırasıyla)
     *
     * LEFT JOIN + HAVING ile üye başına ayrı not sorgusu (N+1) yapılmaz.
     *
     * @return array<int, array<string, mixed>>
     */
    public function uyeler(string $cinsiyet): array
    {
        $cinsiyetler = $cinsiyet === self::CINSIYET_HER_IKISI ? self::CINSIYETLER : [$cinsiyet];
        $yerTutucular = implode(',', array_fill(0, count($cinsiyetler), '?'));

        $sorgu = $this->db->prepare(
            "SELECT u.id, u.adi_soyadi, u.cinsiyet, u.telefon, u.eposta, u.ikamet_ili,
                    u.ikamet_ilcesi, u.trabzon_ilcesi, u.kurum, u.gorev_unvan,
                    u.calisma_sekli, u.temsilci_turu, u.uyelik_tarihi
               FROM dernek_uyeler u
               LEFT JOIN dernek_notlar n ON n.uye_id = u.id
              WHERE u.onay_durumu = 'onayli'
                AND u.cinsiyet IN ({$yerTutucular})
              GROUP BY u.id
             HAVING COUNT(n.id) = 0
              ORDER BY u.adi_soyadi ASC"
        );
        $sorgu->execute($cinsiyetler);

        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * CSV hücresini formül enjeksiyonuna (=, +, -, @ ile başlayan değerler) karşı güvenli hale getirir.
     */
    public static function csvHucre(mixed $deger): string
    {
        $metin = (string) ($deger ?? '');
        if ($metin !== '' && in_array($metin[0], self::FORMUL_KARAKTERLERI, true)) {
            return "'" . $metin;
        }
        return $metin;
    }
}
