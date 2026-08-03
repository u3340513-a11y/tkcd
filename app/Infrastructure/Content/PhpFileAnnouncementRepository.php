<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\Announcement;
use App\Domain\Content\Repository\AnnouncementRepositoryInterface;

/**
 * Duyuruları PHP veri dosyasından okuyan uygulama.
 *
 * Admin panel fazında yerini PDO tabanlı bir uygulamaya bırakacaktır;
 * arayüz sözleşmesi aynı kalacağı için üst katmanlarda değişiklik gerekmez.
 */
final class PhpFileAnnouncementRepository implements AnnouncementRepositoryInterface
{
    private const SOURCE = 'announcements';

    public function __construct(private readonly DataFileLoader $loader)
    {
    }

    public function findLatest(int $limit = 10): array
    {
        $rows = $this->loader->load(self::SOURCE);

        usort(
            $rows,
            static fn (array $a, array $b): int => strcmp(
                (string) ($b['published_at'] ?? ''),
                (string) ($a['published_at'] ?? ''),
            ),
        );

        return array_map(
            static fn (array $row): Announcement => Announcement::fromArray($row),
            array_slice($rows, 0, max(1, $limit)),
        );
    }
}
