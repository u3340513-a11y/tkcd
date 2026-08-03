<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Faaliyet alanı kartı (kültürel etkinlikler, sosyal yardımlaşma vb.).
 */
final class ActivityArea
{
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $icon,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            title: (string) ($row['title'] ?? ''),
            description: (string) ($row['description'] ?? ''),
            icon: (string) ($row['icon'] ?? 'sparkles'),
        );
    }
}
