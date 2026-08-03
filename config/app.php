<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Uygulama seviyesi yapılandırma.
 *
 * Neden: Ortam bağımlı her değer tek noktadan, .env üzerinden okunur;
 * kod içinde hiçbir yerde sabit (hardcoded) ortam değeri bulunmaz.
 */
return [
    'env' => Env::string('APP_ENV', 'production'),
    'debug' => Env::bool('APP_DEBUG', false),
    'url' => rtrim(Env::string('APP_URL', 'https://trabzonlukamucalisanlaridernegi.com'), '/'),
    'timezone' => Env::string('APP_TIMEZONE', 'Europe/Istanbul'),
    'locale' => Env::string('APP_LOCALE', 'tr_TR'),
    'language' => 'tr',

    'paths' => [
        'views' => BASE_PATH . '/resources/views',
        'data' => BASE_PATH . '/resources/data',
        'public' => BASE_PATH . '/public',
        'logs' => BASE_PATH . '/storage/logs',
    ],

    'session' => [
        'name' => 'tkcd_session',
        'lifetime' => 7200,
        'same_site' => 'Lax',
    ],

    'analytics' => [
        'measurement_id' => Env::string('ANALYTICS_MEASUREMENT_ID', ''),
    ],
];
