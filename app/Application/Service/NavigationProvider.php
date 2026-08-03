<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Config;
use App\Domain\Navigation\NavigationItem;

/**
 * Menü tanımını, geçerli isteğe göre aktiflik bilgisiyle birlikte üretir.
 *
 * Girdi : geçerli istek yolu
 * Çıktı : header, mobil menü, footer ve sitemap tarafından paylaşılan menü ağacı
 */
final class NavigationProvider
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return list<NavigationItem>
     */
    public function build(string $currentPath): array
    {
        return array_map(
            fn (array $item): NavigationItem => $this->toItem($item, $currentPath),
            $this->config->array('navigation'),
        );
    }

    /**
     * Sitemap üretimi için menüdeki tüm yolları düzleştirir.
     *
     * @return list<array{path: string, priority: string}>
     */
    public function flattenPaths(): array
    {
        $paths = [];

        foreach ($this->config->array('navigation') as $item) {
            $children = (array) ($item['children'] ?? []);

            if ($children === []) {
                $paths[] = [
                    'path' => (string) ($item['path'] ?? '/'),
                    'priority' => (string) ($item['priority'] ?? '0.5'),
                ];

                continue;
            }

            foreach ($children as $child) {
                $paths[] = [
                    'path' => (string) ($child['path'] ?? '/'),
                    'priority' => (string) ($child['priority'] ?? '0.5'),
                ];
            }
        }

        return $paths;
    }

    /**
     * @param array<string, mixed> $item
     */
    private function toItem(array $item, string $currentPath): NavigationItem
    {
        $children = array_map(
            fn (array $child): NavigationItem => $this->toItem($child, $currentPath),
            array_filter((array) ($item['children'] ?? []), 'is_array'),
        );

        $path = (string) ($item['path'] ?? '/');

        return new NavigationItem(
            label: (string) ($item['label'] ?? ''),
            path: $path,
            active: $path === $currentPath,
            children: array_values($children),
            priority: (string) ($item['priority'] ?? '0.5'),
        );
    }
}
