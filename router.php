<?php

declare(strict_types=1);

/**
 * PHP geliştirme sunucusu için yönlendirici.
 *
 * Neden: `php -S` her isteği tek bir betiğe yönlendirdiği için CSS, JS ve
 * görsel varlıklarını sunamaz. Bu router, dosya yolunda gerçek bir statik
 * varlık varsa onu doğrudan sunar; aksi takdirde isteği FrontController'a
 * (index.php) iletir. Bu sayede geliştirme sırasında .htaccess'e gerek
 * kalmadan tüm site çalışır.
 */

/**
 * Uzantıya göre bilinen statik varlık MIME tipleri.
 *
 * Neden: PHP'nin `mime_content_type()` fonksiyonu, çalıştığı sistemdeki
 * fileinfo/magic veritabanına bağlıdır ve metin tabanlı biçimleri (özellikle
 * .css) ayırt edici "sihirli baytları" olmadığı için yanlışlıkla
 * `text/plain` olarak algılayabilir. Tarayıcılar `text/plain` ile gelen bir
 * stylesheet'i asla uygulamaz; bu da yalnızca bu geliştirme sunucusunda
 * (canlıda Apache + `.htaccess` kullanılır) tüm tasarımın kaybolmuş gibi
 * görünmesine yol açar. Bilinen uzantılar için sabit bir eşleme kullanmak,
 * üretimdeki `mod_mime` davranışını yerelde de garanti eder.
 */
const ROUTER_STATIC_MIME_TYPES = [
    'css' => 'text/css; charset=UTF-8',
    'js' => 'application/javascript; charset=UTF-8',
    'json' => 'application/json; charset=UTF-8',
    'svg' => 'image/svg+xml',
    'xml' => 'application/xml; charset=UTF-8',
    'txt' => 'text/plain; charset=UTF-8',
    'webmanifest' => 'application/manifest+json',
    'ico' => 'image/x-icon',
    'png' => 'image/png',
    'jpg' => 'image/jpeg',
    'jpeg' => 'image/jpeg',
    'webp' => 'image/webp',
    'gif' => 'image/gif',
    'avif' => 'image/avif',
    'woff' => 'font/woff',
    'woff2' => 'font/woff2',
    'ttf' => 'font/ttf',
    'mp4' => 'video/mp4',
    'webm' => 'video/webm',
];

/**
 * Neden: Statik varlık MIME tipini önce bilinen uzantı tablosundan, yoksa
 * `mime_content_type()` çıktısından çözer.
 *
 * Girdi: Dosyanın mutlak yolu.
 * Çıktı: `Content-Type` başlığında kullanılacak MIME dizesi.
 */
function resolveStaticAssetMimeType(string $filePath): string
{
    $extension = strtolower(pathinfo($filePath, PATHINFO_EXTENSION));

    if (isset(ROUTER_STATIC_MIME_TYPES[$extension])) {
        return ROUTER_STATIC_MIME_TYPES[$extension];
    }

    return (string) (mime_content_type($filePath) ?: 'application/octet-stream');
}

$publicDir = __DIR__ . '/public';
$requestPath = parse_url($_SERVER['REQUEST_URI'] ?? '/', PHP_URL_PATH) ?: '/';
$path = rawurldecode($requestPath);
$file = $publicDir . $path;

if ($path !== '/' && is_file($file)) {
    header('Content-Type: ' . resolveStaticAssetMimeType($file));
    header('Content-Length: ' . (string) filesize($file));
    readfile($file);

    return true;
}

require $publicDir . '/index.php';

return true;
