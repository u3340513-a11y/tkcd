<?php

declare(strict_types=1);

namespace App\Domain\Navigation;

/**
 * Menü öğesi.
 *
 * "active" bilgisi görüntüleme anında hesaplanır; böylece aynı menü tanımı
 * her sayfada yeniden kullanılabilir.
 */
final class NavigationItem
{
    /**
     * @param list<NavigationItem> $children
     */
    public function __construct(
        public readonly string $label,
        public readonly string $path,
        public readonly bool $active,
        public readonly array $children = [],
        public readonly string $priority = '0.5',
    ) {
    }

    public function hasChildren(): bool
    {
        return $this->children !== [];
    }

    /**
     * Üst menü, alt kırılımlarından biri aktifse kendisi de aktif sayılır.
     */
    public function isActiveBranch(): bool
    {
        if ($this->active) {
            return true;
        }

        foreach ($this->children as $child) {
            if ($child->active) {
                return true;
            }
        }

        return false;
    }
}
