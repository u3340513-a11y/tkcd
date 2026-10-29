<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Tarayıcı seviyesindeki savunma başlıklarını uygular.
 *
 * Neden: XSS, clickjacking, MIME sniffing ve referrer sızıntısına karşı
 * ilk savunma hattıdır. CSP nonce her istekte değiştiğinden statik bir
 * Apache başlığıyla ifade edilemez; bu yüzden tek kaynak burasıdır.
 */
final class SecurityHeaders
{
    private const SCRIPT_HOSTS = [
        'https://www.googletagmanager.com',
        'https://www.google-analytics.com',
        'https://www.google.com',
        'https://www.gstatic.com',
        'https://cdn.jsdelivr.net',
        'https://cdnjs.cloudflare.com',
        'https://www.youtube.com',
        'https://www.youtube-nocookie.com',
    ];

    private const STATIC_DIRECTIVES = [
        "default-src 'self'",
        "base-uri 'self'",
        "form-action 'self'",
        "frame-ancestors 'self'",
        "object-src 'none'",
        "img-src 'self' data: https: blob:",
        "font-src 'self' data: https://fonts.gstatic.com https://cdnjs.cloudflare.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com https://fonts.googleapis.com",
        "connect-src 'self' https://www.google-analytics.com https://analytics.google.com https://www.googletagmanager.com https://www.youtube-nocookie.com https://www.youtube.com",
        "frame-src 'self' https://www.google.com https://www.youtube.com https://www.youtube-nocookie.com",
        "media-src 'self' blob: https://www.youtube-nocookie.com https://www.youtube.com https://*.googlevideo.com",
        'upgrade-insecure-requests',
    ];

    public function __construct(private readonly CspNonce $nonce)
    {
    }

    public function apply(): void
    {
        if (headers_sent()) {
            return;
        }

        header('Content-Security-Policy: ' . $this->contentSecurityPolicy());
        header('X-Content-Type-Options: nosniff');
        header('X-Frame-Options: SAMEORIGIN');
        header('Referrer-Policy: strict-origin-when-cross-origin');
        header('Permissions-Policy: geolocation=(), microphone=(), camera=(), payment=(), usb=()');
        header('Cross-Origin-Opener-Policy: same-origin');
        header_remove('X-Powered-By');
    }

    /**
     * Statik direktifleri ve nonce içeren script-src direktifini birleştirir.
     * 'unsafe-inline' bilerek yoktur: yalnızca nonce taşıyan scriptler çalışır.
     */
    private function contentSecurityPolicy(): string
    {
        $scriptSrc = sprintf(
            "script-src 'self' 'nonce-%s' %s",
            $this->nonce->value(),
            implode(' ', self::SCRIPT_HOSTS),
        );

        return implode('; ', [...self::STATIC_DIRECTIVES, $scriptSrc]);
    }
}
