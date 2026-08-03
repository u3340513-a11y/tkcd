<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\Statistic;
use App\Domain\Content\Repository\StatisticRepositoryInterface;

final class PhpFileStatisticRepository implements StatisticRepositoryInterface
{
    private const SOURCE = 'statistics';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findAll(): array
    {
        return array_map(
            static fn (array $row): Statistic => Statistic::fromArray($row),
            $this->loader->load(self::SOURCE),
        );
    }
}
