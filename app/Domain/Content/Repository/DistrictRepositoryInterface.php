<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\District;

interface DistrictRepositoryInterface
{
    /**
     * @return list<District>
     */
    public function findAll(): array;
}
