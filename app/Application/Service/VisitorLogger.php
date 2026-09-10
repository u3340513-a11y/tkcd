<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Http\Request;

/**
 * Ziyaretçi kayıt servisi.
 *
 * Güvenlik: IP son okteti maskelenir (GDPR). Bot UA'ları atlanır.
 * Performans: Hata fırlatmaz — takip başarısız olsa da sayfa yüklenir.
 */
final class VisitorLogger
{
    private const SAKLAMA_GUNU = 90;

    private const BOT_IMZALAR = [
        'bot', 'crawl', 'spider', 'slurp', 'wget', 'curl', 'python',
        'java/', 'go-http', 'libwww', 'lighthouse', 'pagespeed',
        'facebookexternalhit', 'twitterbot', 'linkedinbot', 'whatsapp',
        'googlebot', 'bingbot', 'yandex', 'baidu', 'duckduck',
        'semrush', 'ahrefs', 'moz/', 'screaming', 'dataprovider',
    ];

    public function __construct(private readonly \Closure $pdoFactory)
    {
    }

    public function log(Request $request): void
    {
        if (!$request->isMethod('GET')) {
            return;
        }

        $ua = $request->header('user-agent');

        if ($this->isBot($ua)) {
            return;
        }

        $path = $request->path;
        if (str_starts_with($path, '/yonetim') || str_starts_with($path, '/api')) {
            return;
        }

        try {
            $pdo = ($this->pdoFactory)();

            $stmt = $pdo->prepare(
                'INSERT INTO ziyaretci_log
                    (oturum_id, sayfa, referrer, ip, user_agent, tarayici, cihaz)
                 VALUES (?, ?, ?, ?, ?, ?, ?)'
            );

            $stmt->execute([
                $this->oturumId(),
                substr($path, 0, 512),
                substr($request->header('referer'), 0, 512) ?: null,
                $this->maskeleIp($request->clientIp),
                substr($ua, 0, 512) ?: null,
                $this->tarayiciTespit($ua),
                $this->cihazTespit($ua),
            ]);

            if (random_int(1, 1000) === 1) {
                $pdo->exec(
                    'DELETE FROM ziyaretci_log
                      WHERE ziyaret_zamani < NOW() - INTERVAL ' . self::SAKLAMA_GUNU . ' DAY
                      LIMIT 500'
                );
            }
        } catch (\Throwable) {
            // Takip hatası hiçbir zaman sayfa yüklenmesini engellemez
        }
    }

    private function isBot(string $ua): bool
    {
        if ($ua === '') {
            return true;
        }
        $uaLower = strtolower($ua);
        foreach (self::BOT_IMZALAR as $imza) {
            if (str_contains($uaLower, $imza)) {
                return true;
            }
        }
        return false;
    }

    private function maskeleIp(string $ip): string
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4) !== false) {
            $parts    = explode('.', $ip);
            $parts[3] = '0';
            return implode('.', $parts);
        }
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6) !== false) {
            $parts = explode(':', $ip);
            return implode(':', array_slice($parts, 0, 3)) . '::/48';
        }
        return '0.0.0.0';
    }

    private function oturumId(): string
    {
        if (session_status() !== PHP_SESSION_NONE) {
            return substr(session_id(), 0, 64);
        }
        return substr(sha1(
            ($_SERVER['REMOTE_ADDR'] ?? '') .
            ($_SERVER['HTTP_USER_AGENT'] ?? '') .
            (string) (int) (time() / 1800)
        ), 0, 64);
    }

    private function cihazTespit(string $ua): string
    {
        $l = strtolower($ua);
        if (str_contains($l, 'ipad') || (str_contains($l, 'android') && !str_contains($l, 'mobile'))) {
            return 'tablet';
        }
        if (str_contains($l, 'mobile') || str_contains($l, 'android') || str_contains($l, 'iphone')) {
            return 'mobil';
        }
        return 'masaustu';
    }

    private function tarayiciTespit(string $ua): string
    {
        $l = strtolower($ua);
        if (str_contains($l, 'edg/'))      return 'Edge';
        if (str_contains($l, 'opr/'))      return 'Opera';
        if (str_contains($l, 'firefox/'))  return 'Firefox';
        if (str_contains($l, 'chrome/'))   return 'Chrome';
        if (str_contains($l, 'safari/') && !str_contains($l, 'chrome')) return 'Safari';
        if (str_contains($l, 'trident/'))  return 'Internet Explorer';
        return 'Diger';
    }
}
