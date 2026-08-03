<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;

/**
 * Satır içi (inline) SVG ikon kütüphanesi.
 *
 * Neden: İkon fontu veya harici ikon paketi yerine satır içi SVG kullanılır;
 * ek ağ isteği oluşmaz, renk devralınır ve ekran okuyucular için gizlenebilir.
 *
 * Girdi : ikon adı ve isteğe bağlı CSS sınıfı
 * Çıktı : güvenli, aria-hidden SVG işaretlemesi (bilinmeyen ad için boş metin)
 */
final class IconSet
{
    /** @var array<string, string>|null */
    private ?array $icons = null;

    public function __construct(private readonly Config $config)
    {
    }

    public function render(string $name, string $class = 'icon'): string
    {
        $icons = $this->icons ??= $this->load();
        $body = $icons[$name] ?? null;

        if ($body === null) {
            return '';
        }

        return sprintf(
            '<svg class="%s" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.7"'
            . ' stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">%s</svg>',
            htmlspecialchars($class, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8'),
            $body,
        );
    }

    public function has(string $name): bool
    {
        return isset(($this->icons ??= $this->load())[$name]);
    }

    /**
     * @return array<string, string>
     */
    private function load(): array
    {
        $file = $this->config->string('app.paths.views') . '/components/icons.php';

        if (!is_file($file)) {
            return [];
        }

        $icons = require $file;

        return is_array($icons) ? $icons : [];
    }
}
