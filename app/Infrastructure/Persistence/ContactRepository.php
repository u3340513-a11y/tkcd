<?php

declare(strict_types=1);

namespace App\Infrastructure\Persistence;

use PDO;

/**
 * İletişim formu mesajlarını veritabanına kaydeder.
 *
 * Neden ayrı repository: Controller'ı ince tutmak ve DB mantığını
 * tek bir yerde toplamak. Test edilebilirlik ve değiştirilebilirlik sağlar.
 */
final class ContactRepository
{
    public function __construct(private readonly PDO $pdo)
    {
    }

    /**
     * Yeni iletişim mesajını iletisim_mesajlari tablosuna kaydeder.
     *
     * @param array{ad: string, eposta: string, konu: string, mesaj: string, mail_durum: string, ip_adresi: string} $data
     */
    public function save(array $data): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO iletisim_mesajlari
                 (ad, eposta, konu, mesaj, mail_durum, ip_adresi)
             VALUES
                 (:ad, :eposta, :konu, :mesaj, :mail_durum, :ip_adresi)'
        );

        $stmt->execute([
            ':ad'         => $data['ad'],
            ':eposta'     => $data['eposta'],
            ':konu'       => $data['konu'],
            ':mesaj'      => $data['mesaj'],
            ':mail_durum' => $data['mail_durum'],
            ':ip_adresi'  => $data['ip_adresi'],
        ]);
    }

    /**
     * Sayfalanmış mesaj listesini döner (yeniden eskiye).
     *
     * @return array<int, array<string, mixed>>
     */
    public function paginate(int $limit = 50, int $offset = 0): array
    {
        $stmt = $this->pdo->prepare(
            'SELECT id, ad, eposta, konu, mesaj, mail_durum, okundu, ip_adresi, olusturuldu
               FROM iletisim_mesajlari
              ORDER BY olusturuldu DESC
              LIMIT :limit OFFSET :offset'
        );
        $stmt->bindValue(':limit',  $limit,  PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();

        /** @var array<int, array<string, mixed>> */
        return $stmt->fetchAll(PDO::FETCH_ASSOC);
    }

    /**
     * Okunmamış mesaj sayısını döner.
     */
    public function unreadCount(): int
    {
        return (int) $this->pdo->query(
            "SELECT COUNT(*) FROM iletisim_mesajlari WHERE okundu = 0"
        )?->fetchColumn();
    }

    /**
     * Mesajı okundu olarak işaretler.
     */
    public function markRead(int $id): void
    {
        $stmt = $this->pdo->prepare(
            'UPDATE iletisim_mesajlari SET okundu = 1 WHERE id = :id'
        );
        $stmt->execute([':id' => $id]);
    }
}
