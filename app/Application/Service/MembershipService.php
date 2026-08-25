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
        $kanGrubu  = trim((string) ($post['kan_grubu'] ?? ''));
        $dogumTarihi = trim((string) ($post['dogum_tarihi'] ?? ''));
        $ikametIl  = trim((string) ($post['ikamet_il'] ?? ''));
        $ikametIlce = trim((string) ($post['ikamet_ilcesi'] ?? ''));
        $trabzonIlce = trim((string) ($post['trabzon_ilce'] ?? ''));
        $kurum       = trim((string) ($post['kurum'] ?? ''));
        $gorev       = trim((string) ($post['gorev'] ?? ''));
        $calismaSekli = trim((string) ($post['calisma_sekli'] ?? ''));

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

        // Telefonu tam, boşluksuz forma çevir: "05" + 9 rakam
        $telefonTam = '05' . $telefonSuffix;

        // Telefon unique kontrolü: aynı numara zaten kayıtlıysa reddet
        // (existsByTelefon içinde REPLACE ile boşluklu eski kayıtlar da yakalanır)
        if ($this->repository->existsByTelefon($telefonTam)) {
            throw new \InvalidArgumentException('__TELEFON_KAYITLI__');
        }

        // Ad-soyad + doğum tarihi mükerrer kontrolü:
        // Aynı kişinin farklı telefon numarasıyla tekrar kaydolmasını engeller
        if ($dogumTarihi !== '' && $this->repository->existsByAdSoyadiVeDogumTarihi($adiSoyadi, $dogumTarihi)) {
            throw new \InvalidArgumentException('__KISI_ZATEN_KAYITLI__');
        }

        // E-posta unique kontrolü: aynı e-posta zaten kayıtlıysa reddet
        if ($eposta !== '' && $this->repository->existsByEposta($eposta)) {
            throw new \InvalidArgumentException('__EPOSTA_KAYITLI__');
        }

        // Görüntülenebilir formata çevir: "0551 605 59 69" (yönetim panelinde okunabilir)
        $telefonGosterim = $this->formatTelefon($telefonTam);

        // Doğum tarihini DATE formatına çevir (HTML date input: YYYY-MM-DD)
        $dogumTarihiDb = '';
        if ($dogumTarihi !== '') {
            $dt = \DateTimeImmutable::createFromFormat('Y-m-d', $dogumTarihi);
            $dogumTarihiDb = $dt !== false ? $dt->format('Y-m-d') : '';
        }

        $application = new MembershipApplication(
            adiSoyadi:    $adiSoyadi,
            telefon:      $telefonGosterim,
            eposta:       $eposta,
            kanGrubu:     $kanGrubu,
            dogumTarihi:  $dogumTarihiDb,
            ikametIli:    $ikametIl,
            ikametIlcesi: $ikametIlce,
            trabzonIlcesi: $trabzonIlce,
            kurum:        $kurum,
            gorevUnvan:   $gorev,
            calismaSekli: $calismaSekli,
        );

        return $this->repository->save($application);
    }

    /**
     * 11 haneli Türkiye telefon numarasını okunabilir formata çevirir.
     *
     * Neden: Yönetim panelinde "0551 605 59 69" formatı daha kolay okunur.
     * Unique kontrolü REPLACE(telefon, ' ', '') ile yapıldığından format farklılığı sorun yaratmaz.
     *
     * @param  string $telefon 11 haneli boşluksuz numara (örn: "05516055969")
     * @return string          Formatlanmış numara (örn: "0551 605 59 69")
     */
    private function formatTelefon(string $telefon): string
    {
        // Güvence: önce tüm boşlukları temizle
        $t = preg_replace('/\s+/', '', $telefon) ?? $telefon;

        if (strlen($t) !== 11) {
            return $telefon; // Beklenmedik uzunlukta — olduğu gibi bırak
        }

        // XXXX XXX XX XX
        return substr($t, 0, 4) . ' ' . substr($t, 4, 3) . ' ' . substr($t, 7, 2) . ' ' . substr($t, 9, 2);
    }
}
