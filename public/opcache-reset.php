<?php
// Bu dosya deploy sonrası OPcache temizlemek için kullanılır.
// Kullandıktan sonra silin.
if (!defined('RESET_SECRET') && ($_GET['secret'] ?? '') !== getenv('APP_KEY')) {
    http_response_code(403);
    die('Forbidden');
}
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OPcache temizlendi.';
} else {
    echo 'OPcache aktif değil.';
}
