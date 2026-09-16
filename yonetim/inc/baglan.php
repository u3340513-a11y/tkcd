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

        // Çift veya tek tırnak varsa kaldır (PHP 7.x uyumlu)
        $ilkKar = $deger[0] ?? '';
        $sonKar = substr($deger, -1);
        if (
            ($ilkKar === '"' && $sonKar === '"')
            || ($ilkKar === "'" && $sonKar === "'")
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

// ─── 5. OTOMATİK MIGRATION ────────────────────────────────────────────────
/**
 * Yeni kolon eklemelerini idempotent biçimde uygular.
 *
 * Neden: SQL dump'ı güncellemek yerine ALTER TABLE IF NOT EXISTS ile
 * mevcut DB'yi günceller. Her sayfa yüklemesinde değil, sadece
 * kolon yoksa çalışır (INFORMATION_SCHEMA sorgusu O(1)).
 */
(static function () use ($db_baglanti): void {
    $kolonlar = [
        ['cinsiyet', "VARCHAR(10) NULL DEFAULT NULL COMMENT 'Erkek veya Kadın'"],
    ];

    $db_adi_sorgu = $db_baglanti->query("SELECT DATABASE()");
    $db_adi = $db_adi_sorgu ? $db_adi_sorgu->fetchColumn() : '';

    foreach ($kolonlar as [$kolon, $tanim]) {
        try {
            $kontrol = $db_baglanti->prepare(
                "SELECT COUNT(*) FROM INFORMATION_SCHEMA.COLUMNS
                  WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dernek_uyeler' AND COLUMN_NAME = ?"
            );
            $kontrol->execute([$db_adi, $kolon]);
            if ((int) $kontrol->fetchColumn() === 0) {
                $db_baglanti->exec("ALTER TABLE `dernek_uyeler` ADD COLUMN `{$kolon}` {$tanim}");
            }
        } catch (\PDOException $e) {
            error_log("Migration hatası ({$kolon}): " . $e->getMessage());
        }
    }

    // dernek_yoneticiler.rol kolonu varchar(20) → varchar(50)
    // kadin_kollari_baskani (22 karakter) varchar(20)'ye sığmıyor; UPDATE sessizce kırpılıyordu.
    try {
        $rolKolon = $db_baglanti->prepare(
            "SELECT CHARACTER_MAXIMUM_LENGTH FROM INFORMATION_SCHEMA.COLUMNS
              WHERE TABLE_SCHEMA = ? AND TABLE_NAME = 'dernek_yoneticiler' AND COLUMN_NAME = 'rol'"
        );
        $rolKolon->execute([$db_adi]);
        $maxLen = (int) $rolKolon->fetchColumn();
        if ($maxLen > 0 && $maxLen < 50) {
            $db_baglanti->exec("ALTER TABLE `dernek_yoneticiler` MODIFY COLUMN `rol` VARCHAR(50) NOT NULL DEFAULT 'admin'");
        }
    } catch (\PDOException $e) {
        error_log("Migration hatası (rol kolonu genişletme): " . $e->getMessage());
    }

    // duyurular tablosu — Dashboard'da gösterilecek geliştirici duyuruları
    try {
        $db_baglanti->exec("
            CREATE TABLE IF NOT EXISTS `duyurular` (
                `id` INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
                `baslik` VARCHAR(255) NOT NULL,
                `icerik` TEXT NULL,
                `aktif` TINYINT(1) NOT NULL DEFAULT 1,
                `tarih` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
                `olusturan` VARCHAR(100) NULL,
                INDEX `idx_aktif_tarih` (`aktif`, `tarih` DESC)
            ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
        ");
    } catch (\PDOException $e) {
        error_log("Migration hatası (duyurular tablosu): " . $e->getMessage());
    }
})();

// ─── KİŞİSEL İLETİŞİM BİLGİSİ YETKİ KONTROLÜ ──────────────────────────
/**
 * Mevcut oturumdaki kullanıcının üye telefon numarasını ve
 * e-posta adresini görme yetkisine sahip olup olmadığını döner.
 *
 * İzinli hesaplar:
 *   - Kullanıcı adı: admin61, yonetim_hk  (rol bağımsız)
 *   - Rol: gelistirici
 *
 * Neden hesap adına da bakıyoruz: admin ve yonetim rolleri birden
 * fazla kullanıcı tarafından kullanılabilir; kısıtlama bu iki
 * spesifik hesaba özeldir.
 *
 * @return bool true ise görüntüleyebilir, false ise maskelenir
 */
function kisi_bilgisi_gorebilir(): bool
{
    static $sonuc = null;
    if ($sonuc !== null) {
        return $sonuc;
    }

    /** @var string[] İzin verilen kullanıcı adları (rol bağımsız) */
    $izinli_kullanicilar = ['admin61', 'yonetim_hk'];

    // Hesap geçişi (impersonation) modunda MEVCUT kullanıcının rolü kullanılır.
    // Geliştirici başka hesaba geçiş yaptığında, o hesabın kısıtlamaları geçerli olur.
    $rol  = $_SESSION['rol']           ?? '';
    $kadi = $_SESSION['kullanici_adi'] ?? '';

    $sonuc = $rol === 'gelistirici'
          || in_array($kadi, $izinli_kullanicilar, true);

    return $sonuc;
}

/**
 * Maskeleme yardımcısı: Yetkisi olmayan kullanıcılara
 * telefon/e-posta yerine gizleme simgesi döner.
 *
 * @param string $deger Gerçek değer
 * @return string Yetkiye göre gerçek değer veya '—'
 */
function gizli_alan(string $deger): string
{
    if (kisi_bilgisi_gorebilir()) {
        return $deger;
    }
    return '<span class="text-muted" title="Bu bilgiyi görüntüleme yetkiniz yok.">'
         . '<i class="fa-solid fa-lock fa-xs me-1"></i>Gizli</span>';
}
