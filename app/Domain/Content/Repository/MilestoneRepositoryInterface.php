<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\Milestone;

interface MilestoneRepositoryInterface
{
    /**
     * Kronolojik sırada Trabzon tarihçesi durakları.
     *
     * @return list<Milestone>
     */
    public function findAll(): array;
}
