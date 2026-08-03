<?php

declare(strict_types=1);

namespace App\Core\Http;

/**
 * Gelen HTTP isteğinin değişmez (immutable) temsili.
 *
 * Süper küresel diziler yalnızca burada okunur; uygulamanın geri kalanı
 * $_GET/$_POST/$_SERVER'a doğrudan erişmez.
 */
final class Request
{
    /**
     * @param array<string, mixed> $query
     * @param array<string, mixed> $body
     * @param array<string, string> $headers
     */
    private function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly array $query,
        public readonly array $body,
        public readonly array $headers,
        public readonly string $host,
        public readonly bool $secure,
        public readonly string $clientIp,
    ) {
    }

    public static function fromGlobals(): self
    {
        $method = strtoupper((string) ($_SERVER['REQUEST_METHOD'] ?? 'GET'));
        $uri = (string) ($_SERVER['REQUEST_URI'] ?? '/');
        $path = parse_url($uri, PHP_URL_PATH);

        return new self(
            method: in_array($method, ['GET', 'POST', 'HEAD'], true) ? $method : 'GET',
            path: self::normalizePath(is_string($path) ? $path : '/'),
            query: is_array($_GET) ? $_GET : [],
            body: is_array($_POST) ? $_POST : [],
            headers: self::extractHeaders(),
            host: (string) ($_SERVER['HTTP_HOST'] ?? ''),
            secure: self::detectHttps(),
            clientIp: self::detectClientIp(),
        );
    }

    public function isMethod(string $method): bool
    {
        return $this->method === strtoupper($method);
    }

    public function header(string $name, string $default = ''): string
    {
        return $this->headers[strtolower($name)] ?? $default;
    }

    /**
     * Kök .htaccess isteği /public altına taşıdığı için oluşabilen
     * "/public" ön eki temizlenir ve yol tekilleştirilir.
     */
    private static function normalizePath(string $path): string
    {
        $path = '/' . trim($path, '/');

        if ($path === '/public') {
            return '/';
        }

        if (str_starts_with($path, '/public/')) {
            $path = substr($path, strlen('/public'));
        }

        $path = preg_replace('#/+#', '/', $path) ?? '/';
        $path = rtrim($path, '/');

        return $path === '' ? '/' : $path;
    }

    /**
     * @return array<string, string>
     */
    private static function extractHeaders(): array
    {
        $headers = [];

        foreach ($_SERVER as $key => $value) {
            if (!is_string($key) || !is_string($value)) {
                continue;
            }

            if (str_starts_with($key, 'HTTP_')) {
                $name = strtolower(str_replace('_', '-', substr($key, 5)));
                $headers[$name] = $value;
            }
        }

        return $headers;
    }

    private static function detectHttps(): bool
    {
        if (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off') {
            return true;
        }

        return ($_SERVER['HTTP_X_FORWARDED_PROTO'] ?? '') === 'https';
    }

    /**
     * Ters vekil (proxy) başlıklarına körü körüne güvenilmez; yalnızca
     * REMOTE_ADDR temel alınır. Oran sınırlama gibi işlevler için yeterlidir.
     */
    private static function detectClientIp(): string
    {
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '');

        return filter_var($ip, FILTER_VALIDATE_IP) === false ? '0.0.0.0' : $ip;
    }
}
