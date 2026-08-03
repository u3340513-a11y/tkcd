<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Application\ViewModel\HomePageViewModel;
use App\Domain\Content\Repository\ActivityAreaRepositoryInterface;
use App\Domain\Content\Repository\AnnouncementRepositoryInterface;
use App\Domain\Content\Repository\DistrictRepositoryInterface;
use App\Domain\Content\Repository\EventRepositoryInterface;
use App\Domain\Content\Repository\MilestoneRepositoryInterface;
use App\Domain\Content\Repository\StatisticRepositoryInterface;

/**
 * Anasayfanın veri toplama işini üstlenen uygulama servisi.
 *
 * Neden: Denetleyici yalnızca HTTP ile ilgilenir; hangi verinin, hangi
 * sınırla ve hangi kaynaklardan geleceği bu servistedir. Böylece aynı veri
 * kümesi ileride farklı bir kanalda (ör. API) yeniden kullanılabilir.
 */
final class HomePageService
{
    private const ANNOUNCEMENT_LIMIT = 4;
    private const EVENT_LIMIT = 3;

    public function __construct(
        private readonly StatisticRepositoryInterface $statistics,
        private readonly AnnouncementRepositoryInterface $announcements,
        private readonly ActivityAreaRepositoryInterface $activityAreas,
        private readonly EventRepositoryInterface $events,
        private readonly DistrictRepositoryInterface $districts,
        private readonly MilestoneRepositoryInterface $milestones,
    ) {
    }

    public function build(): HomePageViewModel
    {
        return new HomePageViewModel(
            statistics: $this->statistics->findAll(),
            announcements: $this->announcements->findLatest(self::ANNOUNCEMENT_LIMIT),
            activityAreas: $this->activityAreas->findAll(),
            events: $this->events->findLatest(self::EVENT_LIMIT),
            districts: $this->districts->findAll(),
            milestones: $this->milestones->findAll(),
        );
    }
}
