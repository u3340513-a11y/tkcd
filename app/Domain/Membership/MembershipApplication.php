<?php

declare(strict_types=1);

namespace App\Domain\Membership;

/**
 * Üyelik başvurusu değer nesnesi.
 *
 * Neden: Ham POST verisini tip-güvenli bir nesneye sararak
 * servis ve repository katmanlarına temiz bir kontrat sağlar.
 * Tek sorumluluk: veriyi taşımak.
 */
final readonly class MembershipApplication
{
    public function __construct(
        public string $adiSoyadi,
        public string $telefon,
        public string $eposta,
        public string $kanGrubu,
        public string $dogumTarihi,
        public string $ikametIli,
        public string $ikametIlcesi,
        public string $trabzonIlcesi,
        public string $kurum,
        public string $gorevUnvan,
        public string $calismaSekli,
    ) {
    }
}
