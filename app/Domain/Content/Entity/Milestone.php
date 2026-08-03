<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Trabzon tarihçesi zaman çizelgesi durağı.
 */
final class Milestone
{
    public function __construct(
        public readonly string $period,
        public readonly string $title,
        public readonly string $description,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            period: (string) ($row['period'] ?? ''),
            title: (string) ($row['title'] ?? ''),
            description: (string) ($row['description'] ?? ''),
        );
    }
}
