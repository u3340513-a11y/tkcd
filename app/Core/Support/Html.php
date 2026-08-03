<?php

declare(strict_types=1);

namespace App\Core\Support;

/**
 * Çıktı kaçırma (escaping) yardımcıları.
 *
 * Neden: Şablonlarda basılan her dinamik değer bağlamına uygun biçimde
 * kaçırılmalıdır. Tek bir yerde toplanması, XSS riskini denetlenebilir kılar.
 */
final class Html
{
    private const ENCODING = 'UTF-8';

    /**
     * HTML gövdesi ve öznitelik değeri için güvenli kaçırma.
     */
    public static function escape(?string $value): string
    {
        return htmlspecialchars($value ?? '', ENT_QUOTES | ENT_SUBSTITUTE | ENT_HTML5, self::ENCODING);
    }

    /**
     * href/src öznitelikleri için: yalnızca güvenli şemalara izin verilir.
     * Böylece "javascript:" ile başlayan bağlantı enjeksiyonu engellenir.
     */
    public static function url(?string $value): string
    {
        $value = trim($value ?? '');

        if ($value === '') {
            return '#';
        }

        if (preg_match('#^(/|\#|\?)#', $value) === 1) {
            return self::escape($value);
        }

        $scheme = strtolower((string) parse_url($value, PHP_URL_SCHEME));

        return in_array($scheme, ['http', 'https', 'mailto', 'tel'], true)
            ? self::escape($value)
            : '#';
    }

    /**
     * JSON-LD gibi <script> içeriklerinde etiket kaçışını engeller.
     */
    public static function json(array $data): string
    {
        $encoded = json_encode(
            $data,
            JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT,
        );

        return $encoded === false ? '{}' : $encoded;
    }

    /**
     * Koşullu CSS sınıfı birleştirme: ['btn' => true, 'btn--ghost' => $isGhost]
     *
     * @param array<string, bool>|list<string> $classes
     */
    public static function classes(array $classes): string
    {
        $result = [];

        foreach ($classes as $class => $enabled) {
            if (is_int($class)) {
                $result[] = (string) $enabled;

                continue;
            }

            if ($enabled) {
                $result[] = $class;
            }
        }

        return self::escape(implode(' ', array_filter($result)));
    }
}
