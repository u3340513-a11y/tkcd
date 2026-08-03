<?php

declare(strict_types=1);

namespace App\Application\ViewModel;

use App\Domain\Content\Entity\ActivityArea;
use App\Domain\Content\Entity\Announcement;
use App\Domain\Content\Entity\District;
use App\Domain\Content\Entity\Event;
use App\Domain\Content\Entity\Milestone;
use App\Domain\Content\Entity\Statistic;

/**
 * Anasayfa şablonunun ihtiyaç duyduğu tüm veriyi taşıyan değişmez model.
 *
 * Neden: Şablon, repository veya servislere doğrudan erişmez; yalnızca
 * kendisine verilen hazır veriyi görüntüler (Separation of Concerns).
 */
final class HomePageViewModel
{
    /**
     * @param list<Statistic> $statistics
     * @param list<Announcement> $announcements
     * @param list<ActivityArea> $activityAreas
     * @param list<Event> $events
     * @param list<District> $districts
     * @param list<Milestone> $milestones
     */
    public function __construct(
        public readonly array $statistics,
        public readonly array $announcements,
        public readonly array $activityAreas,
        public readonly array $events,
        public readonly array $districts,
        public readonly array $milestones,
    ) {
    }
}
