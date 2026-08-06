<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Domain\Membership\MembershipApplication;
use App\Domain\Membership\MembershipRepositoryInterface;

/**
 * Üyelik başvurusu servis katmanı.
 *
 * Neden: HTTP katmanından (controller) iş mantığını ayırır.
 * Doğrulama ve kayıt işlemleri burada merkezileşir; controller
 * yalnızca HTTP girdisini alıp servise iletmekle sorumludur.
 *
 * Doğrulama:
 *  - Ad Soyad zorunlu, 3–120 karakter
 *  - Telefon zorunlu; "05" prefix + 9 rakam → normalize edilip tam numaray verir
 *  - E-posta zorunlu; filter_var ile RFC-5321 uyumluluğu
 *  - İkamet ili zorunlu
 *  - Doğum tarihi (eğer girilmişse) date() uyumlu format
 */
final class MembershipService
{
    public function __construct(
        private readonly MembershipRepositoryInterface $repository,
    ) {
    }

    /**
     * POST verisini doğrular, değer nesnesine dönüştürür ve kaydeder.
     *
     * @param  array<string, mixed> $post  $_POST verisinin sanitize edilmiş kopyası
     * @return int                         Eklenen satır ID'si (başarı)
     * @throws \InvalidArgumentException   Zorunlu alan eksik veya geçersiz
     */
    public function apply(array $post): int
    {
        $adiSoyadi = trim((string) ($post['ad_soyad'] ?? ''));
        $telefonSuffix = trim((string) ($post['telefon'] ?? ''));
        $eposta    = trim((string) ($post['eposta'] ?? ''));
        $kanGrubu  = trim((string) ($post['kan_grubu'] ?? '')) ?: null;
        $dogumTarihi = trim((string) ($post['dogum_tarihi'] ?? '')) ?: null;
        $ikametIl  = trim((string) ($post['ikamet_il'] ?? ''));
        $trabzonIlce = trim((string) ($post['trabzon_ilce'] ?? '')) ?: null;
        $kurum       = trim((string) ($post['kurum'] ?? '')) ?: null;
        $gorev       = trim((string) ($post['gorev'] ?? '')) ?: null;
        $calismaSekli = trim((string) ($post['calisma_sekli'] ?? '')) ?: null;

        // Zorunlu alan kontrolleri
        if (mb_strlen($adiSoyadi) < 3 || mb_strlen($adiSoyadi) > 120) {
            throw new \InvalidArgumentException('Adı Soyadı 3 ile 120 karakter arasında olmalıdır.');
        }

        if ($telefonSuffix === '' || !preg_match('/^[0-9]{9}$/', $telefonSuffix)) {
            throw new \InvalidArgumentException('Telefon numarası geçersiz.');
        }

        if ($eposta === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            throw new \InvalidArgumentException('Geçerli bir e-posta adresi giriniz.');
        }

        if ($ikametIl === '') {
            throw new \InvalidArgumentException('İkamet edilen il zorunludur.');
        }

        if ($dogumTarihi === null || $dogumTarihi === '') {
            throw new \InvalidArgumentException('Doğum tarihi zorunludur.');
        }

        if ($trabzonIlce === null || $trabzonIlce === '') {
            throw new \InvalidArgumentException('Trabzon ilçesi zorunludur.');
        }

        // Telefonu tam formata çevir: "05" + 9 rakam → "0512345678" gibi
        $telefonTam = '05' . $telefonSuffix;

        // Doğum tarihini DATE formatına çevir (HTML date input: YYYY-MM-DD)
        $dogumTarihiDb = null;
        if ($dogumTarihi !== null) {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dogumTarihi);
            $dogumTarihiDb = $dt !== false ? $dt->format('Y-m-d') : null;
        }

        $application = new MembershipApplication(
            adiSoyadi:    $adiSoyadi,
            telefon:      $telefonTam,
            eposta:       $eposta,
            kanGrubu:     $kanGrubu,
            dogumTarihi:  $dogumTarihiDb,
            ikametIli:    $ikametIl,
            trabzonIlcesi: $trabzonIlce,
            kurum:        $kurum,
            gorevUnvan:   $gorev,
            calismaSekli: $calismaSekli,
        );

        return $this->repository->save($application);
    }
}
