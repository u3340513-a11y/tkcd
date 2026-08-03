<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\ActivityArea;

interface ActivityAreaRepositoryInterface
{
    /**
     * @return list<ActivityArea>
     */
    public function findAll(): array;
}
