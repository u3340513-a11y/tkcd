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

    /**
     * Verilen telefon numarası veritabanında kayıtlı mı kontrol eder.
     *
     * @param string $telefon Tam telefon numarası (örn: "05321234567")
     * @return bool true → zaten kayıtlı
     */
    public function existsByTelefon(string $telefon): bool;

    /**
     * Verilen ad-soyad ve doğum tarihi kombinasyonu veritabanında kayıtlı mı kontrol eder.
     * Aynı kişinin farklı telefon numarasıyla tekrar kayıt olmasını engeller.
     *
     * @param string $adiSoyadi Ad ve soyad (büyük/küçük harf duyarsız)
     * @param string $dogumTarihi Doğum tarihi (YYYY-MM-DD formatı)
     * @return bool true → zaten kayıtlı
     */
    public function existsByAdSoyadiVeDogumTarihi(string $adiSoyadi, string $dogumTarihi): bool;
}
