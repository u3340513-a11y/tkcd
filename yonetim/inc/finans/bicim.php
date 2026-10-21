<?php

declare(strict_types=1);

/**
 * Para tutarı dönüşümleri.
 *
 * Neden var: para float ile tutulursa toplamlarda kuruş kayıpları oluşur.
 * Tüm tutarlar veritabanında ve PHP'de TAM SAYI KURUŞ olarak taşınır;
 * yalnızca ekranda TL biçimine çevrilir.
 */
final class FinansTutar
{
    /** 1 TL = 100 kuruş. */
    public const KURUS_CARPANI = 100;

    /** Tek bir kayıt için kabul edilen en büyük tutar: 999.999.999,99 TL. */
    public const AZAMI_KURUS = 99999999999;

    /**
     * Türkçe biçimli tutar metnini kuruşa çevirir.
     *
     * Girdi : "1.500,50", "1500", "₺ 1.000,00", "1500,5" gibi metin.
     * Çıktı : pozitif tam sayı kuruş; boş, "-", sıfır, negatif, geçersiz
     *         veya azami tutarı aşan değerlerde null.
     */
    public static function kurusaCevir(string $girdi): ?int
    {
        $temiz = str_replace(['₺', 'TL', "\xC2\xA0", ' '], '', trim($girdi));
        if ($temiz === '' || $temiz === '-') {
            return null;
        }
        if (preg_match('/^(\d{1,3}(?:\.\d{3})+|\d+)(?:,(\d{1,2}))?$/', $temiz, $parca) !== 1) {
            return null;
        }

        $lira  = (int) str_replace('.', '', $parca[1]);
        $kurus = isset($parca[2]) ? (int) str_pad($parca[2], 2, '0') : 0;
        $toplam = $lira * self::KURUS_CARPANI + $kurus;

        return ($toplam > 0 && $toplam <= self::AZAMI_KURUS) ? $toplam : null;
    }

    /** Hücre boş mu ("", "-", "₺ -")? Boş hücre "ödeme yok" demektir, hata değildir. */
    public static function bosMu(string $girdi): bool
    {
        $temiz = str_replace(['₺', 'TL', "\xC2\xA0", ' '], '', trim($girdi));

        return $temiz === '' || $temiz === '-';
    }

    /** Kuruşu "1.234,56" (isteğe bağlı "₺" ile) biçimler; negatif değerleri destekler. */
    public static function bicimle(int $kurus, bool $sembol = true): string
    {
        $isaret = $kurus < 0 ? '-' : '';
        $mutlak = abs($kurus);
        $metin  = number_format(intdiv($mutlak, self::KURUS_CARPANI), 0, ',', '.')
            . ',' . str_pad((string) ($mutlak % self::KURUS_CARPANI), 2, '0', STR_PAD_LEFT);

        return $isaret . ($sembol ? '₺' : '') . $metin;
    }
}

/**
 * Dönem (yıl/ay) sabitleri ve doğrulaması.
 */
final class FinansDonem
{
    public const AY_ADLARI = [
        1 => 'Ocak', 2 => 'Şubat', 3 => 'Mart', 4 => 'Nisan', 5 => 'Mayıs', 6 => 'Haziran',
        7 => 'Temmuz', 8 => 'Ağustos', 9 => 'Eylül', 10 => 'Ekim', 11 => 'Kasım', 12 => 'Aralık',
    ];

    public const EN_KUCUK_YIL = 2000;
    public const EN_BUYUK_YIL = 2100;

    public static function gecerliMi(int $yil, int $ay): bool
    {
        return $yil >= self::EN_KUCUK_YIL && $yil <= self::EN_BUYUK_YIL && isset(self::AY_ADLARI[$ay]);
    }

    /**
     * GG.AA.YYYY, GG/AA/YYYY veya YYYY-AA-GG metnini "YYYY-AA-GG" biçimine çevirir.
     *
     * Çıktı: geçerli bir takvim tarihi değilse null.
     */
    public static function tarihiNormallestir(string $girdi): ?string
    {
        $temiz = trim($girdi);
        if (preg_match('/^(\d{1,2})[.\/-](\d{1,2})[.\/-](\d{4})$/', $temiz, $p) === 1) {
            [$gun, $ay, $yil] = [(int) $p[1], (int) $p[2], (int) $p[3]];
        } elseif (preg_match('/^(\d{4})-(\d{1,2})-(\d{1,2})$/', $temiz, $p) === 1) {
            [$yil, $ay, $gun] = [(int) $p[1], (int) $p[2], (int) $p[3]];
        } else {
            return null;
        }

        if (!checkdate($ay, $gun, $yil) || $yil < self::EN_KUCUK_YIL || $yil > self::EN_BUYUK_YIL) {
            return null;
        }

        return sprintf('%04d-%02d-%02d', $yil, $ay, $gun);
    }
}
