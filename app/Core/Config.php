<?php

declare(strict_types=1);

namespace App\Core;

use App\Core\Exception\ConfigurationException;

/**
 * Yapılandırma dosyalarını nokta notasyonuyla okunabilir hale getirir.
 *
 * Girdi : config dizini yolu
 * Çıktı : get('site.contact.email') gibi tip güvenli erişim
 */
final class Config
{
    /** @var array<string, mixed> */
    private array $items = [];

    public function __construct(private readonly string $configPath)
    {
    }

    /**
     * @return mixed Anahtar bulunamazsa $default döner.
     */
    public function get(string $key, mixed $default = null): mixed
    {
        $segments = explode('.', $key);
        $file = array_shift($segments);

        if ($file === null || $file === '') {
            return $default;
        }

        $value = $this->loadFile($file);

        foreach ($segments as $segment) {
            if (!is_array($value) || !array_key_exists($segment, $value)) {
                return $default;
            }

            $value = $value[$segment];
        }

        return $value;
    }

    public function string(string $key, string $default = ''): string
    {
        $value = $this->get($key, $default);

        return is_scalar($value) ? (string) $value : $default;
    }

    /**
     * @return array<array-key, mixed>
     */
    public function array(string $key, array $default = []): array
    {
        $value = $this->get($key, $default);

        return is_array($value) ? $value : $default;
    }

    public function bool(string $key, bool $default = false): bool
    {
        $value = $this->get($key, $default);

        return is_bool($value) ? $value : $default;
    }

    /**
     * @return array<array-key, mixed>
     */
    private function loadFile(string $file): array
    {
        if (isset($this->items[$file])) {
            /** @var array<array-key, mixed> */
            return $this->items[$file];
        }

        // Path traversal koruması: yalnızca güvenli karakterlerden oluşan
        // dosya adları kabul edilir.
        if (preg_match('/^[a-z0-9_-]+$/i', $file) !== 1) {
            throw new ConfigurationException(sprintf('Geçersiz yapılandırma anahtarı: %s', $file));
        }

        $path = $this->configPath . DIRECTORY_SEPARATOR . $file . '.php';

        if (!is_file($path)) {
            throw new ConfigurationException(sprintf('Yapılandırma dosyası bulunamadı: %s', $file));
        }

        $loaded = require $path;

        if (!is_array($loaded)) {
            throw new ConfigurationException(sprintf('Yapılandırma dosyası dizi döndürmeli: %s', $file));
        }

        return $this->items[$file] = $loaded;
    }
}
