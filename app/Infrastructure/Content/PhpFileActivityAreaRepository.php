<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\ActivityArea;
use App\Domain\Content\Repository\ActivityAreaRepositoryInterface;

final class PhpFileActivityAreaRepository implements ActivityAreaRepositoryInterface
{
    private const SOURCE = 'activity-areas';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findAll(): array
    {
        return array_map(
            static fn (array $row): ActivityArea => ActivityArea::fromArray($row),
            $this->loader->load(self::SOURCE),
        );
    }
}
