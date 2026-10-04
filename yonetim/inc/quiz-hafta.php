<?php
/**
 * TS Bilgi Yarışması — haftalık dönem yardımcısı.
 *
 * Neden var: Liderlik tablosu ve kişisel skorlar her Pazar 00:00'da
 * sıfırlanmalı. Tüm quiz dosyaları aynı hafta kodunu kullansın diye
 * hesaplama tek yerde tutulur (DRY).
 */

if (!function_exists('quizHaftaKodu')) {
    /**
     * İçinde bulunulan haftanın kodunu döndürür. Hafta Pazar 00:00'da başlar.
     *
     * Girdi : opsiyonel zaman (varsayılan: şimdi)
     * Çıktı : haftanın başladığı Pazar gününün tarihi, "YYYY-MM-DD" (10 karakter)
     */
    function quizHaftaKodu(?DateTimeImmutable $an = null): string
    {
        $an ??= new DateTimeImmutable('now');
        $pazardanBeriGecenGun = (int) $an->format('w'); // 0 = Pazar
        return $an->modify("-{$pazardanBeriGecenGun} days")->format('Y-m-d');
    }
}
