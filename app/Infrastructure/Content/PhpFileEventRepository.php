<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\Event;
use App\Domain\Content\Repository\EventRepositoryInterface;

final class PhpFileEventRepository implements EventRepositoryInterface
{
    private const SOURCE = 'events';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findLatest(int $limit = 3): array
    {
        $rows = $this->loader->load(self::SOURCE);

        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp(
                (string) ($b['published_at'] ?? ''),
                (string) ($a['published_at'] ?? ''),
            ),
        );

        return array_map(
            static fn (array $row): Event => Event::fromArray($row),
            array_slice($rows, 0, max(1, $limit)),
        );
    }

    public function findBySlug(string $slug): ?Event
    {
        foreach ($this->loader->load(self::SOURCE) as $row) {
            if (($row['slug'] ?? null) === $slug) {
                return Event::fromArray($row);
            }
        }

        return null;
    }
}
