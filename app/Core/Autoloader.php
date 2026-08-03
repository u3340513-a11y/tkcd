<?php

declare(strict_types=1);

namespace App\Core;

/**
 * PSR-4 uyumlu, bağımlılıksız sınıf yükleyici.
 *
 * Neden: Paylaşımlı cPanel ortamlarında Composer her zaman kullanılabilir
 * değildir. Bu yükleyici sayesinde proje "vendor" klasörü olmadan, dosyaların
 * sunucuya kopyalanmasıyla çalışır.
 *
 * Girdi : namespace ön eki -> kök dizin eşleşmeleri
 * Çıktı : eşleşen sınıf dosyasının yüklenmesi
 */
final class Autoloader
{
    /** @var array<string, list<string>> */
    private array $prefixes = [];

    /**
     * Bir namespace ön ekini dosya sistemindeki kök dizine bağlar.
     */
    public function addNamespace(string $prefix, string $baseDirectory): void
    {
        $prefix = trim($prefix, '\\') . '\\';
        $baseDirectory = rtrim($baseDirectory, DIRECTORY_SEPARATOR) . DIRECTORY_SEPARATOR;

        $this->prefixes[$prefix][] = $baseDirectory;
    }

    public function register(): void
    {
        spl_autoload_register([$this, 'load']);
    }

    public function load(string $class): bool
    {
        $class = ltrim($class, '\\');

        foreach ($this->prefixes as $prefix => $directories) {
            if (!str_starts_with($class, $prefix)) {
                continue;
            }

            $relativeClass = substr($class, strlen($prefix));

            foreach ($directories as $directory) {
                $file = $directory . str_replace('\\', DIRECTORY_SEPARATOR, $relativeClass) . '.php';

                if (is_file($file)) {
                    require $file;

                    return true;
                }
            }
        }

        return false;
    }
}
