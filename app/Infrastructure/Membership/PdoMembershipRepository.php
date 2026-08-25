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
                 ikamet_ili, ikamet_ilcesi, trabzon_ilcesi, kurum, gorev_unvan,
                 calisma_sekli, onay_durumu)
            VALUES
                (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'bekleyen')
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
            $application->ikametIlcesi,
            $application->trabzonIlcesi,
            $application->kurum,
            $application->gorevUnvan,
            $application->calismaSekli,
        ]);

        return (int) $pdo->lastInsertId();
    }

    /**
     * Verilen telefon numarasının veritabanında kayıtlı olup olmadığını kontrol eder.
     *
     * Neden: Üyelik formunu kaydetmeden önce telefon tekrarını tespit etmek için
     * kullanılır. COUNT(*) ile tek sorgu; indeksli sütunda O(log n) performans.
     */
    public function existsByTelefon(string $telefon): bool
    {
        // REPLACE(telefon, ' ', '') ile DB'deki boşluklu eski kayıtları da yakalar
        // Örn: "0551 605 59 69" == "05516055969" eşleşir
        $stmt = $this->connection()->prepare(
            "SELECT COUNT(*) FROM dernek_uyeler WHERE REPLACE(telefon, ' ', '') = ? LIMIT 1"
        );
        $stmt->execute([$telefon]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * Ad-soyad ve doğum tarihi kombinasyonuyla mükerrer kayıt kontrolü yapar.
     * Aynı kişinin farklı telefon numarasıyla tekrar kaydolmasını engeller.
     *
     * Neden: LOWER() ile büyük/küçük harf farkı göz ardı edilir.
     * Hem onaylı hem bekleyen kayıtlar kontrol edilir.
     */
    public function existsByAdSoyadiVeDogumTarihi(string $adiSoyadi, string $dogumTarihi): bool
    {
        $stmt = $this->connection()->prepare(
            "SELECT COUNT(*) FROM dernek_uyeler 
             WHERE LOWER(TRIM(adi_soyadi)) = LOWER(TRIM(?)) 
             AND dogum_tarihi = ? 
             LIMIT 1"
        );
        $stmt->execute([$adiSoyadi, $dogumTarihi]);

        return (int) $stmt->fetchColumn() > 0;
    }

    /**
     * E-posta adresiyle mükerrer kayıt kontrolü yapar.
     * Büyük/küçük harf duyarsız karşılaştırma yapar.
     */
    public function existsByEposta(string $eposta): bool
    {
        $stmt = $this->connection()->prepare(
            "SELECT COUNT(*) FROM dernek_uyeler WHERE LOWER(TRIM(eposta)) = LOWER(TRIM(?)) LIMIT 1"
        );
        $stmt->execute([$eposta]);

        return (int) $stmt->fetchColumn() > 0;
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
