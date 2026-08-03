<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\District;
use App\Domain\Content\Repository\DistrictRepositoryInterface;

final class PhpFileDistrictRepository implements DistrictRepositoryInterface
{
    private const SOURCE = 'districts';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findAll(): array
    {
        return array_map(
            static fn (array $row): District => District::fromArray($row),
            $this->loader->load(self::SOURCE),
        );
    }
}
