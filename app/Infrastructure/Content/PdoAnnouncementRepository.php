<?php

declare(strict_types=1);

namespace App\Infrastructure\Content;

use App\Domain\Content\Entity\Announcement;
use App\Domain\Content\Repository\AnnouncementRepositoryInterface;
use PDO;

/**
 * DB tabanlı duyuru repository.
 *
 * Neden: Yönetim paneli duyuruları `duyurular` tablosuna (baslik, icerik,
 * aktif, tarih, olusturan) kaydeder. Bu repository aynı tablodan
 * aktif=1 kayıtları çekerek anasayfanın duyuru şeridini ve
 * Etkinlikler & Haberler bölümünü besler.
 *
 * Güvenlik:
 *  - Yalnızca aktif=1 kayıtlar döner; pasif duyurular asla görünmez.
 *  - Prepared statement ile SQL injection imkansız.
 *
 * Hata yönetimi:
 *  - DB erişim hatasında boş dizi döner; anasayfa hata vermez.
 */
final class PdoAnnouncementRepository implements AnnouncementRepositoryInterface
{
    private ?PDO $pdo = null;

    /**
     * @param \Closure(): PDO $pdoFactory
     */
    public function __construct(private readonly \Closure $pdoFactory)
    {
    }

    /**
     * Yayın tarihine göre yeniden eskiye sıralı aktif duyurular.
     *
     * @param  int             $limit Döndürülecek maksimum kayıt sayısı
     * @return list<Announcement>
     */
    public function findLatest(int $limit = 10): array
    {
        try {
            $pdo = $this->connection();

            $stmt = $pdo->prepare(
                'SELECT baslik, icerik, tarih
                   FROM duyurular
                  WHERE aktif = 1
               ORDER BY tarih DESC
                  LIMIT :limit'
            );
            $stmt->bindValue(':limit', max(1, $limit), PDO::PARAM_INT);
            $stmt->execute();

            $rows = $stmt->fetchAll();

            return array_map(
                static fn (array $row): Announcement => Announcement::fromArray([
                    'slug'         => '',          // DB'de slug yok; URL bağlantısı kullanılmayacak
                    'title'        => (string) ($row['baslik'] ?? ''),
                    'summary'      => (string) ($row['icerik'] ?? ''),
                    'published_at' => substr((string) ($row['tarih'] ?? ''), 0, 10),
                    'highlighted'  => true,
                ]),
                $rows,
            );
        } catch (\PDOException) {
            // DB hatası anasayfayı çökertmemeli; section boş kalır
            return [];
        }
    }

    private function connection(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = ($this->pdoFactory)();
        }

        return $this->pdo;
    }
}
