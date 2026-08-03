<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;

/**
 * Statik varlık bağlantılarını üretir ve sürümler.
 *
 * Neden: cPanel'de dosya güncellendiğinde tarayıcı önbelleğinin bayat içerik
 * sunmaması için bağlantıya dosya değişiklik zamanından türetilen bir sürüm
 * imzası eklenir. Böylece varlıklar bir yıl boyunca güvenle önbelleklenebilir.
 */
final class AssetManager
{
    /** @var array<string, string> */
    private array $resolved = [];

    private readonly string $publicPath;

    public function __construct(private readonly Config $config)
    {
        $this->publicPath = rtrim($this->config->string('app.paths.public'), '/');
    }

    public function url(string $relativePath): string
    {
        $relativePath = '/' . ltrim($relativePath, '/');

        if (isset($this->resolved[$relativePath])) {
            return $this->resolved[$relativePath];
        }

        $file = $this->publicPath . $relativePath;
        $version = is_file($file) ? substr((string) filemtime($file), -8) : null;

        return $this->resolved[$relativePath] = $version === null
            ? $relativePath
            : $relativePath . '?v=' . $version;
    }

    /**
     * Open Graph, kanonik bağlantı ve JSON-LD için mutlak adres üretir.
     */
    public function absolute(string $path): string
    {
        return $this->config->string('app.url') . '/' . ltrim($path, '/');
    }

    public function exists(string $relativePath): bool
    {
        return $relativePath !== '' && is_file($this->publicPath . '/' . ltrim($relativePath, '/'));
    }
}
