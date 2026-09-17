<?php
/**
 * TS Bilgi Yarışması — Quiz kayıt endpoint'i (AJAX).
 *
 * Session kontrolü, sunucu taraflı cevap doğrulama ve
 * günlük oynama limiti uygular.
 *
 * POST parametreleri:
 *   - soru_idleri  : JSON encoded soru index dizisi
 *   - cevaplar     : JSON encoded kullanıcı cevap dizisi
 *   - csrf_token   : CSRF doğrulama token'ı
 *
 * @var PDO $db_baglanti baglan.php'den gelen bağlantı
 */

require_once __DIR__ . '/baglan.php';
require_once __DIR__ . '/log-kayit.php';

header('Content-Type: application/json; charset=utf-8');

// ─── SADECE POST KABUL ET ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz istek metodu.']);
    exit;
}

// ─── SESSION KONTROLÜ ──────────────────────────────────────────────────
if (!isset($_SESSION['kullanici_adi'])) {
    http_response_code(401);
    echo json_encode(['ok' => false, 'mesaj' => 'Oturum açmanız gerekli.']);
    exit;
}

$kullanici_adi = $_SESSION['kullanici_adi'];
$ad_soyad      = $_SESSION['ad_soyad'] ?? $kullanici_adi;

// ─── CSRF KONTROLÜ ─────────────────────────────────────────────────────
$csrf = $_POST['csrf_token'] ?? '';
if (!isset($_SESSION['csrf_token']) || !hash_equals($_SESSION['csrf_token'], $csrf)) {
    http_response_code(403);
    echo json_encode(['ok' => false, 'mesaj' => 'Güvenlik doğrulaması başarısız.']);
    exit;
}

// ─── GİRDİLERİ DOĞRULA ─────────────────────────────────────────────────
$soru_idleri_raw = $_POST['soru_idleri'] ?? '';
$cevaplar_raw    = $_POST['cevaplar']    ?? '';

$soru_idleri = json_decode($soru_idleri_raw, true);
$cevaplar    = json_decode($cevaplar_raw, true);

if (!is_array($soru_idleri) || !is_array($cevaplar) || count($soru_idleri) !== 8 || count($cevaplar) !== 8) {
    http_response_code(400);
    echo json_encode(['ok' => false, 'mesaj' => '8 sorunun tamamı cevaplanmalıdır.']);
    exit;
}

// ─── GÜNLÜK OYNAMA LİMİTİ (10 kez/gün) ─────────────────────────────────
$bugun = date('Y-m-d');
$hafta_kodu = date('Y-W');

try {
    $limit_sorgu = $db_baglanti->prepare(
        "SELECT COUNT(*) FROM quiz_sonuclari WHERE kullanici_adi = ? AND DATE(oynama_tarihi) = ?"
    );
    $limit_sorgu->execute([$kullanici_adi, $bugun]);
    $gunluk_sayi = (int) $limit_sorgu->fetchColumn();

    if ($gunluk_sayi >= 10) {
        echo json_encode(['ok' => false, 'mesaj' => 'Günlük oynama limitine (10) ulaştınız. Yarın tekrar deneyin!']);
        exit;
    }
} catch (PDOException $e) {
    // Tablo yoksa devam et — ilk çalıştırmada sorun yaratmasın
}

// ─── SUNUCU TARAFINDA CEVAP DOĞRULAMA ────────────────────────────────────
$sorular = require __DIR__ . '/quiz-sorulari.php';
$toplam_soru = count($sorular);

$dogru_sayisi = 0;
$puan_per_soru = 100; // Her doğru 100 puan

foreach ($soru_idleri as $i => $soru_index) {
    // Geçerlilik kontrolü
    $soru_index = (int) $soru_index;
    if ($soru_index < 0 || $soru_index >= $toplam_soru) {
        http_response_code(400);
        echo json_encode(['ok' => false, 'mesaj' => 'Geçersiz soru referansı.']);
        exit;
    }

    $kullanici_cevap = (int) ($cevaplar[$i] ?? -1);
    $dogru_cevap     = (int) $sorular[$soru_index]['cevap'];

    if ($kullanici_cevap === $dogru_cevap) {
        $dogru_sayisi++;
    }
}

$toplam_puan = $dogru_sayisi * $puan_per_soru;

// ─── VERİTABANINA KAYDET ────────────────────────────────────────────────
try {
    $kayit = $db_baglanti->prepare(
        "INSERT INTO quiz_sonuclari (kullanici_adi, ad_soyad, puan, dogru_sayisi, hafta_kodu, oynama_tarihi)
         VALUES (?, ?, ?, ?, ?, NOW())"
    );
    $kayit->execute([
        $kullanici_adi,
        $ad_soyad,
        $toplam_puan,
        $dogru_sayisi,
        $hafta_kodu,
    ]);

    log_kaydet($db_baglanti, 'quiz_oynandi', "Bilgi yarışması: {$dogru_sayisi}/8 doğru, {$toplam_puan} puan.");

    echo json_encode([
        'ok'            => true,
        'dogru_sayisi'  => $dogru_sayisi,
        'toplam_puan'   => $toplam_puan,
        'mesaj'         => "Tebrikler! {$dogru_sayisi}/8 doğru cevap, {$toplam_puan} puan kazandınız!",
    ]);
} catch (PDOException $e) {
    http_response_code(500);
    echo json_encode(['ok' => false, 'mesaj' => 'Sonuç kaydedilemedi. Lütfen tekrar deneyin.']);
}
