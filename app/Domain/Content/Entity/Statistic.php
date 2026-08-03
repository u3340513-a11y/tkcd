<?php

declare(strict_types=1);

namespace App\Domain\Content\Entity;

/**
 * Anasayfa sayaç kutucuğu (81 il temsilciliği, 995+ üye vb.).
 */
final class Statistic
{
    public function __construct(
        public readonly int $value,
        public readonly string $suffix,
        public readonly string $label,
        public readonly string $icon,
    ) {
    }

    /**
     * @param array<string, mixed> $row
     */
    public static function fromArray(array $row): self
    {
        return new self(
            value: (int) ($row['value'] ?? 0),
            suffix: (string) ($row['suffix'] ?? ''),
            label: (string) ($row['label'] ?? ''),
            icon: (string) ($row['icon'] ?? 'star'),
        );
    }
}
