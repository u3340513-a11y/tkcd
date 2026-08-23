<?php

declare(strict_types=1);

/**
 * Temsilci ağı verisi — YÖNETİM PANELİNDEN OTOMATİK BESLENİR.
 *
 * Bu dosya artık kullanılmamaktadır. İl başkanı bilgileri doğrudan
 * veritabanındaki dernek_uyeler tablosundan (temsilci_turu = 'İl Başkanı'
 * veya ek_gorev = 'İl Başkanı' koşuluyla) okunur.
 *
 * Bkz: App\Http\Controller\AboutController::fetchIlBaskanlari()
 *
 * @return array<string, mixed>
 */
return [];
