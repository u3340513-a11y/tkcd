<?php

declare(strict_types=1);

namespace App\Domain\Content\Repository;

use App\Domain\Content\Entity\Announcement;

interface AnnouncementRepositoryInterface
{
    /**
     * Yayın tarihine göre yeniden eskiye sıralı duyurular.
     *
     * @param int $limit Sayfalama sınırı; listelerin sınırsız büyümesi engellenir.
     * @return list<Announcement>
     */
    public function findLatest(int $limit = 10): array;
}
