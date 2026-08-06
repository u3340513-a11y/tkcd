<?php

declare(strict_types=1);

namespace App\Infrastructure\Membership;

use App\Domain\Membership\MembershipApplication;
use App\Domain\Membership\MembershipRepositoryInterface;
use PDO;

/**
 * PDO tabanlı üyelik başvurusu repository implementasyonu.
 *
 * Neden: Başvuruları `dernek_uyeler` tablosuna `onay_durumu = 'bekleyen'`
 * olarak kaydeder. Yönetim paneli (yonetim/) bu tablodan bekleyen kayıtları
 * okuyarak onay/red işlemi yapar. İki sistem aynı tabloyu paylaşır;
 * bu şekilde ayrı bir API ya da senkronizasyon mekanizmasına gerek kalmaz.
 *
 * Güvenlik:
 *  - Tüm değerler PDO prepared statement ile bağlanır; SQL enjeksiyonu imkansız.
 *  - Boş string'ler NULL olarak saklanır; veri tutarlılığı korunur.
 *
 * Lazy bağlantı:
 *  - PDO yalnızca save() çağrıldığında (POST isteğinde) oluşturulur.
 *  - Form sayfası (GET) yüklenirken veritabanına bağlanılmaz; bağlantı
 *    hatası olsa bile form görüntülenmeye devam eder.
 */
final class PdoMembershipRepository implements MembershipRepositoryInterface
{
    private ?PDO $pdo = null;

    /**
     * @param \Closure(): PDO $pdoFactory  PDO nesnesini üreten fabrika fonksiyonu
     */
    public function __construct(private readonly \Closure $pdoFactory)
    {
    }

    public function save(MembershipApplication $application): int
    {
        $sql = <<<SQL
            INSERT INTO dernek_uyeler
                (adi_soyadi, telefon, eposta, kan_grubu, dogum_tarihi,
                 ikamet_ili, trabzon_ilcesi, kurum, gorev_unvan,
                 calisma_sekli, onay_durumu)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'bekleyen')
        SQL;

        $pdo = $this->connection();
        $statement = $pdo->prepare($sql);

        $statement->execute([
            $application->adiSoyadi,
            $application->telefon,
            $application->eposta,
            $application->kanGrubu,
            $application->dogumTarihi,
            $application->ikametIli,
            $application->trabzonIlcesi,
            $application->kurum,
            $application->gorevUnvan,
            $application->calismaSekli,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * İlk çağrıda bağlantıyı kurar, sonrasında aynı nesneyi döndürür.
     */
    private function connection(): PDO
    {
        if ($this->pdo === null) {
            $this->pdo = ($this->pdoFactory)();
        }

        return $this->pdo;
    }
}
