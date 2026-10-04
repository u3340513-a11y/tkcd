<?php
/**
 * TS Bilgi Yarışması — tekrarsız soru seçici.
 *
 * Neden var: Her turda havuzdan bağımsız rastgele seçim yapmak, limit
 * kalktıktan sonra aynı soruların çok sık gelmesine yol açıyordu. Burada
 * kullanıcı oturumunda karıştırılmış bir "deste" tutulur; havuzdaki tüm
 * sorular tükenmeden hiçbir soru tekrar sorulmaz.
 */

if (!function_exists('quizSoruIndeksleriSec')) {
    /**
     * Girdi : havuz boyutu, bir turdaki soru adedi, oturum dizisi (referans)
     * Çıktı : karışık sırada, desteden çekilmiş soru index'leri
     */
    function quizSoruIndeksleriSec(int $havuzBoyutu, int $adet, array &$oturum): array
    {
        $anahtar = 'quiz_kalan_sorular';

        $kalan = array_values(array_filter(
            (array) ($oturum[$anahtar] ?? []),
            static fn($i): bool => is_int($i) && $i >= 0 && $i < $havuzBoyutu
        ));

        if (count($kalan) < $adet) {
            $yeniDeste = array_values(array_diff(range(0, $havuzBoyutu - 1), $kalan));
            shuffle($yeniDeste);
            $kalan = array_merge($kalan, $yeniDeste);
        }

        $secilen = array_splice($kalan, 0, $adet);
        $oturum[$anahtar] = $kalan;
        shuffle($secilen);

        return $secilen;
    }
}
