<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Duyuru varlığı.
 *
 * Anasayfadaki kayan duyuru şeridinde ve duyurular sayfasında kullanılır.
 */
final class Announcement
{
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $publishedAt,
        public readonly bool $highlighted = false,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            slug: (string) ($row['slug'] ?? ''),
            title: (string) ($row['title'] ?? ''),
            summary: (string) ($row['summary'] ?? ''),
            publishedAt: (string) ($row['published_at'] ?? ''),
            highlighted: (bool) ($row['highlighted'] ?? false),
        );
    }
}
