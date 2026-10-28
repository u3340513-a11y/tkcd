<?php
/**
 * Dashboard ortak yardımcıları.
 *
 * Neden var: Avatar yardımcıları yönetim dashboard'unda tanımlıydı; kısıtlı rol
 * (ilçe başkanı) dashboard'u da aynı bileşenleri kullandığı için tek yerde toplandı.
 */

if (!function_exists('avatarRengi')) {
    function avatarRengi(string $isim, array $renkler): string {
        return $renkler[abs(crc32($isim)) % count($renkler)];
    }
}
if (!function_exists('basHarfleri')) {
    function basHarfleri(string $isim): string {
        $parcalar = explode(' ', trim($isim));
        $harfler = '';
        foreach (array_slice($parcalar, 0, 2) as $p) {
            $harfler .= mb_strtoupper(mb_substr(trim($p), 0, 1));
        }
        return $harfler;
    }
}

const DASHBOARD_SON_UYE_LIMITI = 6;
const DASHBOARD_YAS_MIN = 18;
const DASHBOARD_YAS_MAKS = 120;
const DASHBOARD_YAS_GRUPLARI = [
    '18–25' => [18, 25],
    '26–35' => [26, 35],
    '36–45' => [36, 45],
    '46–55' => [46, 55],
    '56–65' => [56, 65],
    '65+'   => [66, DASHBOARD_YAS_MAKS],
];

if (!function_exists('genel_dashboard_verileri')) {
    /**
     * Tüm onaylı üyelere göre dashboard kart verilerini üretir (ilçe filtresi YOKTUR).
     *
     * Girdi : PDO bağlantısı.
     * Çıktı : [
     *   'son_uyeler'    => satırlar (id, adi_soyadi, kurum, gorev_unvan, uyelik_tarihi),
     *   'kan_etiketler' => string[], 'kan_sayilar' => int[],
     *   'ilce_etiketler'=> string[], 'ilce_sayilar' => int[],
     *   'yas_dagilimi'  => [grup => adet], 'yas_ortalama'|'yas_en_genc'|'yas_en_yasli' => ?number,
     * ]
     * Hata durumunda ilgili alanlar boş döner; hata günlüğe yazılır, panel çökmez.
     */
    function genel_dashboard_verileri(PDO $db): array
    {
        $veri = [
            'son_uyeler'     => [],
            'kan_etiketler'  => [],
            'kan_sayilar'    => [],
            'ilce_etiketler' => [],
            'ilce_sayilar'   => [],
            'yas_dagilimi'   => array_fill_keys(array_keys(DASHBOARD_YAS_GRUPLARI), 0),
            'yas_ortalama'   => null,
            'yas_en_genc'    => null,
            'yas_en_yasli'   => null,
        ];

        try {
            $sorgu = $db->prepare(
                "SELECT id, adi_soyadi, kurum, gorev_unvan, uyelik_tarihi
                   FROM dernek_uyeler
                  WHERE onay_durumu = 'onayli'
                  ORDER BY id DESC LIMIT " . DASHBOARD_SON_UYE_LIMITI
            );
            $sorgu->execute();
            $veri['son_uyeler'] = $sorgu->fetchAll(PDO::FETCH_ASSOC);

            $sorgu = $db->prepare(
                "SELECT kan_grubu, COUNT(*) AS adet
                   FROM dernek_uyeler
                  WHERE onay_durumu = 'onayli' AND kan_grubu IS NOT NULL AND kan_grubu != ''
                  GROUP BY kan_grubu ORDER BY adet DESC"
            );
            $sorgu->execute();
            foreach ($sorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
                $veri['kan_etiketler'][] = $satir['kan_grubu'];
                $veri['kan_sayilar'][]   = (int) $satir['adet'];
            }

            $sorgu = $db->prepare(
                "SELECT trabzon_ilcesi, COUNT(*) AS adet
                   FROM dernek_uyeler
                  WHERE onay_durumu = 'onayli' AND trabzon_ilcesi IS NOT NULL AND trabzon_ilcesi != ''
                  GROUP BY trabzon_ilcesi ORDER BY adet DESC"
            );
            $sorgu->execute();
            foreach ($sorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
                $veri['ilce_etiketler'][] = $satir['trabzon_ilcesi'];
                $veri['ilce_sayilar'][]   = (int) $satir['adet'];
            }

            $sorgu = $db->prepare(
                "SELECT TIMESTAMPDIFF(YEAR, dogum_tarihi, CURDATE()) AS yas
                   FROM dernek_uyeler
                  WHERE onay_durumu = 'onayli'
                    AND dogum_tarihi IS NOT NULL AND dogum_tarihi != '0000-00-00'"
            );
            $sorgu->execute();
            $yaslar = array_filter(
                array_map('intval', array_column($sorgu->fetchAll(PDO::FETCH_ASSOC), 'yas')),
                static fn(int $yas): bool => $yas >= DASHBOARD_YAS_MIN && $yas <= DASHBOARD_YAS_MAKS
            );
            if ($yaslar !== []) {
                $veri['yas_ortalama'] = round(array_sum($yaslar) / count($yaslar), 1);
                $veri['yas_en_genc']  = min($yaslar);
                $veri['yas_en_yasli'] = max($yaslar);
                foreach (DASHBOARD_YAS_GRUPLARI as $etiket => [$alt, $ust]) {
                    $veri['yas_dagilimi'][$etiket] = count(array_filter(
                        $yaslar,
                        static fn(int $yas): bool => $yas >= $alt && $yas <= $ust
                    ));
                }
            }
        } catch (\PDOException $e) {
            error_log('Genel dashboard verileri hatası: ' . $e->getMessage());
        }

        return $veri;
    }
}
