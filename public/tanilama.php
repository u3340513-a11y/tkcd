<?php
/**
 * Tek kullanımlık tanılama scripti.
 * Çalıştırdıktan sonra silin!
 */

// Sadece public/ dizininden çalıştırıldığında erişilebilir
define('BASE_PATH', dirname(__DIR__));

require BASE_PATH . '/app/Core/Autoloader.php';
$autoloader = new \App\Core\Autoloader();
$autoloader->addNamespace('App', BASE_PATH . '/app');
$autoloader->register();

\App\Core\Env::load(BASE_PATH . '/.env');

header('Content-Type: text/plain; charset=utf-8');

echo "=== TANILAMА RAPORU ===\n\n";
echo "PHP Sürümü: " . phpversion() . "\n";
echo "BASE_PATH: " . BASE_PATH . "\n";
echo ".env yolu: " . BASE_PATH . "/.env\n";
echo ".env mevcut mu: " . (is_file(BASE_PATH . "/.env") ? "EVET" : "HAYIR") . "\n";
echo ".env okunabilir mi: " . (is_readable(BASE_PATH . "/.env") ? "EVET" : "HAYIR") . "\n\n";

echo "--- ENV DEĞERLERİ ---\n";
echo "DB_DATABASE: " . \App\Core\Env::string('DB_DATABASE', '(bos)') . "\n";
echo "DB_USERNAME: " . \App\Core\Env::string('DB_USERNAME', '(bos)') . "\n";
echo "DB_PASSWORD: " . (strlen(\App\Core\Env::string('DB_PASSWORD', '')) > 0 ? '(dolu - ' . strlen(\App\Core\Env::string('DB_PASSWORD')) . ' karakter)' : '(BOŞ!)') . "\n";
echo "RECAPTCHA_SITE_KEY: " . (\App\Core\Env::string('RECAPTCHA_SITE_KEY', '') !== '' ? substr(\App\Core\Env::string('RECAPTCHA_SITE_KEY'), 0, 10) . '...' : '(BOŞ!)') . "\n";
echo "APP_ENV: " . \App\Core\Env::string('APP_ENV', '(bos)') . "\n\n";

echo "--- shell_exec TEST ---\n";
$php = shell_exec('php -v 2>&1');
echo "shell_exec: " . ($php !== null ? "AÇIK" : "KAPALI (devre dışı)") . "\n\n";

echo "=== Bu dosyayı sunucudan hemen silin! ===\n";

// Kendini sil
@unlink(__FILE__);
echo "Dosya silindi.\n";
