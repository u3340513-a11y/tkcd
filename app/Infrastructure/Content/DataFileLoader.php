<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Core\Config;
use App\Core\Exception\ConfigurationException;

/**
 * İçerik veri dosyalarını okuyan tekil kaynak.
 *
 * Neden: Repository'lerin tamamı aynı okuma/önbellekleme mantığını
 * paylaşır; bu mantık tek yerde toplanarak tekrar önlenir (DRY). Dosyalar
 * istek başına bir kez okunur ve OPcache sayesinde diskten tekrar
 * ayrıştırılmaz.
 *
 * Girdi : veri dosyası adı (uzantısız)
 * Çıktı : ilişkisel dizilerden oluşan liste
 */
final class DataFileLoader
{
    /** @var array<string, list<array<string, mixed>>> */
    private array $cache = [];

    private readonly string $dataPath;

    public function __construct(private readonly Config $config)
    {
        $this->dataPath = rtrim($this->config->string('app.paths.data'), '/');
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function load(string $name): array
    {
        if (isset($this->cache[$name])) {
            return $this->cache[$name];
        }

        if (preg_match('/^[a-z0-9-]+$/', $name) !== 1) {
            throw new ConfigurationException(sprintf('Geçersiz veri dosyası adı: %s', $name));
        }

        $file = $this->dataPath . '/' . $name . '.php';

        if (!is_file($file)) {
            return $this->cache[$name] = [];
        }

        $rows = require $file;

        if (!is_array($rows)) {
            throw new ConfigurationException(sprintf('Veri dosyası dizi döndürmeli: %s', $name));
        }

        /** @var list<array<string, mixed>> $filtered */
        $filtered = array_values(array_filter($rows, 'is_array'));

        return $this->cache[$name] = $filtered;
    }
}
