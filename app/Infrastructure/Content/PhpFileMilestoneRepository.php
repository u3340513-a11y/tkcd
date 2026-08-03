<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\Milestone;
use App\Domain\Content\Repository\MilestoneRepositoryInterface;

final class PhpFileMilestoneRepository implements MilestoneRepositoryInterface
{
    private const SOURCE = 'milestones';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findAll(): array
    {
        return array_map(
            static fn (array $row): Milestone => Milestone::fromArray($row),
            $this->loader->load(self::SOURCE),
        );
    }
}
