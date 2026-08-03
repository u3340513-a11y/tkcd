<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\Event;

interface EventRepositoryInterface
{
    /**
     * @return list<Event>
     */
    public function findLatest(int $limit = 3): array;

    /**
     * @return Event|null Slug eşleşmezse null döner (404 üretmek çağıranın işidir).
     */
    public function findBySlug(string $slug): ?Event;
}
