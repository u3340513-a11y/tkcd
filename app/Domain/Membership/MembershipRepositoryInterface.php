<?php

declare(strict_types=1);

namespace App\Domain\Membership;

/**
 * Üyelik başvurusu repository arayüzü.
 *
 * Neden: Somut PDO implementasyonunu denetleyici ve servis
 * katmanından soyutlayarak Bağımlılık Tersine Çevirme ilkesini
 * (DIP) uygular. İleride farklı bir depolama motoru (Elasticsearch,
 * API, vb.) eklenirken bu arayüz değişmez.
 */
interface MembershipRepositoryInterface
{
    /**
     * Başvuruyu 'bekleyen' statüsüyle kalıcı depoya yazar.
     *
     * @param MembershipApplication $application Doğrulanmış başvuru nesnesi
     * @return int Eklenen satırın otomatik artan kimliği
     */
    public function save(MembershipApplication $application): int;
}
