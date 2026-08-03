<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\Statistic;

interface StatisticRepositoryInterface
{
    /**
     * @return list<Statistic>
     */
    public function findAll(): array;
}
