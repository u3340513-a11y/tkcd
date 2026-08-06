<?php

/**
 * Yönetim paneli veritabanı bağlantısı ve oturum güvenliği.
 *
 * Neden tek dosyada: Yönetim paneli (/yonetim/) ana uygulamadan bağımsız
 * çalışan eski-tip bir PHP uygulaması. Tüm inc/ dosyaları bu dosyayı
 * require_once ile çağırır. Session başlatma, cookie güvenliği ve CSRF
 * token yönetimi burada merkezileştirilmiştir.
 *
 * DB bilgileri artık kodda değil, kök dizindeki .env dosyasından okunur.
 */

// ─── 1. SESSION GÜVENLİĞİ ─────────────────────────────────────────────
if (session_status() === PHP_SESSION_NONE) {
    $isHttps = (!empty($_SERVER['HTTPS']) && $_SERVER['HTTPS'] !== 'off')
        || (isset($_SERVER['HTTP_X_FORWARDED_PROTO']) && $_SERVER['HTTP_X_FORWARDED_PROTO'] === 'https')
        || (isset($_SERVER['SERVER_PORT']) && (int) $_SERVER['SERVER_PORT'] === 443);

    session_set_cookie_params([
        'lifetime' => 0,            // Tarayıcı kapanınca oturum biter
        'path'     => '/yonetim/',   // Sadece /yonetim/ altında geçerli
        'domain'   => '',            // Mevcut domain otomatik
        'secure'   => $isHttps,      // HTTPS varsa sadece güvenli bağlantıda gönder
        'httponly'  => true,         // JavaScript'ten erişilemez
        'samesite'  => 'Strict',    // CSRF koruma katmanı
    ]);

    session_name('TKCD_YONETIM');    // Varsayılan PHPSESSID yerine özel isim
    session_start();

    // Oturum zaman aşımı: 30 dakika inaktivite
    $sessionTimeout = 1800;
    if (isset($_SESSION['son_aktivite']) && (time() - $_SESSION['son_aktivite']) > $sessionTimeout) {
        $_SESSION = [];
        session_destroy();
        header('Location: /yonetim/');
        exit;
    }
    $_SESSION['son_aktivite'] = time();
}

// ─── 2. .ENV OKUYUCU ───────────────────────────────────────────────────
/**
 * Kök dizindeki .env dosyasından ortam değişkenlerini yükler.
 *
 * Neden kendi parser'ımız: Yönetim paneli Composer autoload kullanmıyor;
 * ana uygulamanın Env sınıfına erişimi yok. Basit bir key=value parser
 * yeterlidir ve harici bağımlılık gerektirmez.
 *
 * @param string $path .env dosyasının mutlak yolu
 */
function yonetim_env_yukle(string $path): void
{
    if (!is_file($path) || !is_readable($path)) {
        return;
    }

    $satirlar = file($path, FILE_IGNORE_NEW_LINES | FILE_SKIP_EMPTY_LINES);
    if ($satirlar === false) {
        return;
    }

    foreach ($satirlar as $satir) {
        $satir = trim($satir);
        // Yorum satırlarını atla
        if ($satir === '' || $satir[0] === '#') {
            continue;
        }
        // KEY=VALUE formatı
        $esitPos = strpos($satir, '=');
        if ($esitPos === false) {
            continue;
        }

        $anahtar = trim(substr($satir, 0, $esitPos));
        $deger   = trim(substr($satir, $esitPos + 1));

        // Çift veya tek tırnak varsa kaldır
        if (
            (str_starts_with($deger, '"') && str_ends_with($deger, '"'))
            || (str_starts_with($deger, "'") && str_ends_with($deger, "'"))
        ) {
            $deger = substr($deger, 1, -1);
        }

        // Sadece henüz tanımlanmamışsa ayarla (mevcut env değişkenlerini ezme)
        if (!array_key_exists($anahtar, $_ENV)) {
            $_ENV[$anahtar] = $deger;
            putenv("$anahtar=$deger");
        }
    }
}

// .env dosyası kök dizinde (yonetim/../.env olarak erişilir)
$envDosyasi = dirname(__DIR__, 2) . '/.env';
yonetim_env_yukle($envDosyasi);

/**
 * Ortam değişkenini güvenli şekilde okur.
 *
 * @param string $anahtar  .env'deki değişken adı
 * @param string $varsayilan Değer bulunamazsa dönülecek varsayılan
 * @return string
 */
function env_al(string $anahtar, string $varsayilan = ''): string
{
    return $_ENV[$anahtar] ?? (getenv($anahtar) ?: $varsayilan);
}

// ─── 3. VERİTABANI BAĞLANTISI ─────────────────────────────────────────
$host     = env_al('DB_HOST', 'localhost');
$db       = env_al('DB_DATABASE', '');
$user     = env_al('DB_USERNAME', '');
$password = env_al('DB_PASSWORD', '');
$charset  = env_al('DB_CHARSET', 'utf8mb4');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES   => false,
];

try {
    $db_baglanti = new PDO($dsn, $user, $password, $options);
} catch (\PDOException $e) {
    // Üretim ortamında detay sızdırma; genel mesaj göster, detayı logla
    error_log('Yönetim DB bağlantı hatası: ' . $e->getMessage());
    die('Veritabanı bağlantısı kurulamadı. Lütfen sistem yöneticisiyle iletişime geçin.');
}

// ─── 4. CSRF TOKEN YÖNETİMİ ────────────────────────────────────────────
/**
 * Oturumdaki CSRF token'ı döner; yoksa yeni üretir.
 *
 * Neden: GET ile yapılan üye silme, statü değiştirme gibi işlemler
 * CSRF saldırısına açıktır. Token, formlarla birlikte gönderilip
 * sunucu tarafında doğrulanarak bu riski ortadan kaldırır.
 *
 * @return string 64 karakter hex token
 */
function csrf_token_al(): string
{
    if (empty($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Gelen CSRF token'ı oturumdakiyle karşılaştırır.
 *
 * @param string $token İstemciden gelen token
 * @return bool Geçerli mi
 */
function csrf_token_dogrula(string $token): bool
{
    if (empty($_SESSION['csrf_token'])) {
        return false;
    }
    return hash_equals($_SESSION['csrf_token'], $token);
}

/**
 * Gizli form alanı olarak CSRF token HTML'i döner.
 *
 * Kullanım: <?= csrf_hidden_alan() ?> şeklinde form içine eklenecek.
 *
 * @return string <input type="hidden"> HTML'i
 */
function csrf_hidden_alan(): string
{
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars(csrf_token_al()) . '">';
}
