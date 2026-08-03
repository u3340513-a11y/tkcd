<?php

declare(strict_types=1);

/**
 * Uygulamanın tek giriş noktası (front controller).
 *
 * Tüm istekler .htaccess kuralları ile buraya yönlendirilir; böylece PHP
 * dosyalarına doğrudan erişim kapalıdır ve tek bir güvenlik/yönlendirme
 * akışı uygulanır.
 */

use App\Core\Application;
use App\Core\Autoloader;
use App\Core\Env;
use App\Core\Http\Request;

define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';

$autoloader = new Autoloader();
$autoloader->addNamespace('App', BASE_PATH . '/app');
$autoloader->register();

Env::load(BASE_PATH . '/.env');

(new Application(BASE_PATH))->run(Request::fromGlobals());
