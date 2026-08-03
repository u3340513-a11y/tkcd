<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Tarayıcı seviyesindeki savunma başlıklarını uygular.
 *
 * Neden: XSS, clickjacking, MIME sniffing ve referrer sızıntısına karşı
 * ilk savunma hattıdır. Apache mod_headers devre dışıysa bile PHP üzerinden
 * garanti altına alınır.
 */
final class SecurityHeaders
{
    private const CSP_DIRECTIVES = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "img-src 'self' data: https:",
        "font-src 'self' data:",
        "style-src 'self' 'unsafe-inline'",
        "script-src 'self'",
        "connect-src 'self'",
        "frame-src 'self' https://www.google.com https://www.youtube-nocookie.com",
        'upgrade-insecure-requests',
    ];

    public function apply(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Security-Policy: ' . implode('; ', self::CSP_DIRECTIVES));
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header_remove('X-Powered-By');
    }
}
