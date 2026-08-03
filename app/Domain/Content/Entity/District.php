<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Trabzon ilçesi.
 *
 * "highlight" alanı ilçenin öne çıkan kültürel/coğrafi özelliğini taşır ve
 * arayüzde bilgi kartı olarak gösterilir. "path" alanı, ana sayfadaki
 * etkileşimli il haritasında ilçenin sınırlarını çizen SVG path verisidir
 * (ortak viewBox: bkz. DistrictMapDataProvider::VIEW_BOX).
 */
final class District
{
    public function __construct(
        public readonly string $slug,
        public readonly string $name,
        public readonly string $highlight,
        public readonly bool $isCenter = false,
        public readonly string $mapPath = '',
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            slug: (string) ($row['slug'] ?? ''),
            name: (string) ($row['name'] ?? ''),
            highlight: (string) ($row['highlight'] ?? ''),
            isCenter: (bool) ($row['is_center'] ?? false),
            mapPath: (string) ($row['map_path'] ?? ''),
        );
    }
}
