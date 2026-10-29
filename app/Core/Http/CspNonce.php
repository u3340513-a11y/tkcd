<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * İstek başına tek bir CSP nonce değeri üretir.
 *
 * Neden: `script-src 'unsafe-inline'` yerine yalnızca sunucunun işaretlediği
 * inline scriptlerin çalışmasına izin vermek için kullanılır. Değer tahmin
 * edilemez olmalı ve her istekte yeniden üretilmelidir.
 *
 * Konteyner bu sınıfı tekil olarak sakladığından, başlık ve şablonlar aynı
 * istek içinde aynı değeri paylaşır.
 */
final class CspNonce
{
    private const RANDOM_BYTES = 16;

    private ?string $value = null;

    /**
     * @return string Base64 kodlu, istek boyunca sabit nonce değeri.
     */
    public function value(): string
    {
        return $this->value ??= base64_encode(random_bytes(self::RANDOM_BYTES));
    }
}

