<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Etkinlik / haber varlığı.
 *
 * "image" alanı boş bırakılabilir; arayüz bu durumda görsel yerine kurumsal
 * desenli bir alternatif gösterir (graceful degradation).
 */
final class Event
{
    /**
     * @param list<string> $body
     */
    public function __construct(
        public readonly string $slug,
        public readonly string $title,
        public readonly string $summary,
        public readonly string $category,
        public readonly string $publishedAt,
        public readonly ?string $image,
        public readonly string $imageAlt,
        public readonly string $icon,
        public readonly array $body,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        /** @var list<string> $body */
        $body = array_values(array_filter(
            (array) ($row['body'] ?? []),
            static fn (mixed $paragraph): bool => is_string($paragraph) && trim($paragraph) !== '',
        ));

        $image = isset($row['image']) && is_string($row['image']) && $row['image'] !== ''
            ? $row['image']
            : null;

        return new self(
            slug: (string) ($row['slug'] ?? ''),
            title: (string) ($row['title'] ?? ''),
            summary: (string) ($row['summary'] ?? ''),
            category: (string) ($row['category'] ?? ''),
            publishedAt: (string) ($row['published_at'] ?? ''),
            image: $image,
            imageAlt: (string) ($row['image_alt'] ?? ($row['title'] ?? '')),
            icon: (string) ($row['icon'] ?? 'calendar'),
            body: $body,
        );
    }

    public function url(): string
    {
        return '/etkinlikler/' . $this->slug;
    }
}
