<?php
// GEÇICI - Kullandıktan sonra silin!
if (function_exists('opcache_reset')) {
    opcache_reset();
    echo 'OK';
} else {
    echo 'OPcache yok';
}
