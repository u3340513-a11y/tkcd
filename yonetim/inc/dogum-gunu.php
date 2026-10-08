<?php
/**
 * Doğum günü eşleştirme yardımcıları.
 *
 * Neden var: dernek_uyeler.dogum_tarihi serbest metin olarak tutuluyor
 * (YYYY-MM-DD, GG.AA.YYYY, GG/AA/YYYY; bazen baştaki/sondaki boşluk, NBSP
 * veya tek haneli gün/ay içeriyor). SQL'de STR_TO_DATE bu kayıtlarda sessizce
 * NULL döndürüp üyeyi dashboard'dan düşürüyordu. Eşleştirme PHP'de yapılır.
 */

const DOGUM_GUNU_TARIH_BICIMLERI = [
    // YYYY-AA-GG (ayraç: - . /; ardından isteğe bağlı saat)
    '/^(?<yil>\d{4})[\-\.\/](?<ay>\d{1,2})[\-\.\/](?<gun>\d{1,2})(?:[ T]\d{1,2}:\d{2}(?::\d{2})?)?$/',
    // GG.AA.YYYY (ayraç: . / -)
    '/^(?<gun>\d{1,2})[\-\.\/](?<ay>\d{1,2})[\-\.\/](?<yil>\d{4})$/',
];

/**
 * Serbest metin doğum tarihinden "AA-GG" üretir.
 *
 * Girdi: ham dogum_tarihi değeri (null olabilir).
 * Çıktı: "10-08" gibi bir metin; tarih okunamaz/geçersizse null.
 */
function dogum_tarihi_ay_gun(?string $ham_tarih): ?string
{
    if ($ham_tarih === null) {
        return null;
    }

    $tarih = trim(str_replace("\xC2\xA0", ' ', $ham_tarih));

    foreach (DOGUM_GUNU_TARIH_BICIMLERI as $bicim) {
        if (preg_match($bicim, $tarih, $parca) !== 1) {
            continue;
        }
        $ay  = (int) $parca['ay'];
        $gun = (int) $parca['gun'];
        if (!checkdate($ay, $gun, (int) $parca['yil'])) {
            return null;
        }
        return sprintf('%02d-%02d', $ay, $gun);
    }

    return null;
}

/**
 * Verilen üyelerden doğum günü $ay_gun ("AA-GG") olanları döndürür.
 *
 * Girdi: 'dogum_tarihi' anahtarı olan üye satırları.
 * Çıktı: eşleşen satırlar (sıra korunur, anahtarlar yeniden dizilir).
 *
 * @param array<int, array<string, mixed>> $uyeler
 * @return array<int, array<string, mixed>>
 */
function dogum_gunu_olanlari_filtrele(array $uyeler, string $ay_gun): array
{
    return array_values(array_filter(
        $uyeler,
        static fn (array $uye): bool => dogum_tarihi_ay_gun($uye['dogum_tarihi'] ?? null) === $ay_gun
    ));
}
