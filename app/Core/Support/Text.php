<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Metin dönüşümleri. Türkçe karakter duyarlıdır.
 */
final class Text
{
    private const TURKISH_MAP = [
        'ç' => 'c', 'Ç' => 'c', 'ğ' => 'g', 'Ğ' => 'g', 'ı' => 'i', 'İ' => 'i',
        'ö' => 'o', 'Ö' => 'o', 'ş' => 's', 'Ş' => 's', 'ü' => 'u', 'Ü' => 'u',
    ];

    /**
     * URL dostu, yalnızca [a-z0-9-] içeren bir anahtar üretir.
     */
    public static function slug(string $value): string
    {
        $value = strtr($value, self::TURKISH_MAP);
        $value = mb_strtolower($value, 'UTF-8');
        $value = preg_replace('/[^a-z0-9]+/u', '-', $value) ?? '';

        return trim($value, '-');
    }

    /**
     * Kelime bütünlüğünü bozmadan kısaltır; SEO açıklamalarında kullanılır.
     */
    public static function excerpt(string $value, int $limit = 160, string $suffix = '…'): string
    {
        $value = trim(preg_replace('/\s+/u', ' ', strip_tags($value)) ?? '');

        if (mb_strlen($value, 'UTF-8') <= $limit) {
            return $value;
        }

        $cut = mb_substr($value, 0, $limit, 'UTF-8');
        $lastSpace = mb_strrpos($cut, ' ', 0, 'UTF-8');

        return rtrim($lastSpace === false ? $cut : mb_substr($cut, 0, $lastSpace, 'UTF-8'), ' ,.;:') . $suffix;
    }

    /**
     * Türkçe uzun tarih biçimi (ör. 12 Ağustos 2026).
     */
    public static function longDate(string $isoDate): string
    {
        $months = [
            1 => 'Ocak', 'Şubat', 'Mart', 'Nisan', 'Mayıs', 'Haziran',
            'Temmuz', 'Ağustos', 'Eylül', 'Ekim', 'Kasım', 'Aralık',
        ];

        $timestamp = strtotime($isoDate);

        if ($timestamp === false) {
            return '';
        }

        return sprintf(
            '%d %s %s',
            (int) date('j', $timestamp),
            $months[(int) date('n', $timestamp)],
            date('Y', $timestamp),
        );
    }
}
