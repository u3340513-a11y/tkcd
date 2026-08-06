<?php

declare(strict_types=1);

namespace App\Core;

/**
 * .env dosyasını okuyup tip güvenli erişim sağlayan yardımcı.
 *
 * Neden: Sırlar ve ortama özel değerler kod içinde tutulmaz. Değerler
 * $_ENV/$_SERVER üzerine yazılmaz; yalnızca sınıf içinde tutulur, böylece
 * phpinfo() benzeri çıktılarda sızma riski oluşmaz.
 */
final class Env
{
    /** @var array<string, string> */
    private static array $values = [];

    private static bool $loaded = false;

    /**
     * Dosya yoksa sessizce geçilir; bu durumda tüm okumalar varsayılana düşer.
     */
    public static function load(string $file): void
    {
        self::$loaded = true;

        if (!is_readable($file)) {
            return;
        }

        $lines = file($file, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);

        if ($lines === false) {
            return;
        }

        foreach ($lines as $line) {
            $line = trim($line);

            // PHP 7.x uyumlu: str_starts_with ve str_contains yerine strpos
            if ($line === '' || $line[0] === '#' || strpos($line, '=') === false) {
                continue;
            }

            [$key, $value] = explode('=', $line, 2);

            self::$values[trim($key)] = self::normalize(trim($value));
        }
    }

    public static function string(string $key, string $default = ''): string
    {
        return self::$values[$key] ?? $default;
    }

    public static function bool(string $key, bool $default = false): bool
    {
        if (!isset(self::$values[$key])) {
            return $default;
        }

        return filter_var(self::$values[$key], FILTER_VALIDATE_BOOLEAN, FILTER_NULL_ON_FAILURE) ?? $default;
    }

    public static function int(string $key, int $default = 0): int
    {
        if (!isset(self::$values[$key]) || !is_numeric(self::$values[$key])) {
            return $default;
        }

        return (int) self::$values[$key];
    }

    public static function isLoaded(): bool
    {
        return self::$loaded;
    }

    /**
     * Tırnak içine alınmış değerlerden tırnakları temizler.
     */
    private static function normalize(string $value): string
    {
        if (strlen($value) > 1) {
            $first = $value[0];
            $last = $value[strlen($value) - 1];

            if (($first === '"' && $last === '"') || ($first === "'" && $last === "'")) {
                return substr($value, 1, -1);
            }
        }

        return $value;
    }
}
