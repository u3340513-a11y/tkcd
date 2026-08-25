<?php
// Geçici OPcache temizleme scripti — kullandıktan sonra silin.
$secret = $_GET['s'] ?? '';
$expected = substr(md5(getenv('DB_PASSWORD') ?: ''), 0, 12);
if (!hash_equals($expected, $secret)) {
    http_response_code(403);
    die('Forbidden');
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OK: OPcache temizlendi.';
} else {
    echo 'OPcache aktif degil veya PHP-CLI modunda calisiyor.';
}
