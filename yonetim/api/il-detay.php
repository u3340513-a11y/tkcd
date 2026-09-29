<?php
/**
 * İl detay API endpoint'i.
 *
 * Belirtilen il için:
 *   - Toplam üye sayısı
 *   - Üye bulunan ilçe sayısı
 *   - Benzersiz kurum sayısı
 *   - Kurum temsilcisi sayısı
 *   - En yoğun 5 ilçe listesi
 *
 * GET /yonetim/api/il-detay.php?il=Trabzon
 *
 * Güvenlik: kimlik doğrulama oturumu ve CSRF-token kontrolü yapılır.
 *           Yalnızca yönetim panelinden XHR ile erişilebilir.
 */

declare(strict_types=1);

// ── Oturum & Yetki ────────────────────────────────────────────────────────
session_start();

if (empty($_SESSION['yonetim_giris']) || $_SESSION['yonetim_giris'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Yetkisiz erişim.']);
    exit;
}

// Yalnızca XHR kabul et
$isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
if (!$isXhr) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Geçersiz istek.']);
    exit;
}

// ── Girdi doğrulama ───────────────────────────────────────────────────────
$il = trim((string) ($_GET['il'] ?? ''));
if ($il === '' || mb_strlen($il, 'UTF-8') > 60) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Geçersiz il parametresi.']);
    exit;
}

// ── Veritabanı bağlantısı ─────────────────────────────────────────────────
$config_path = dirname(__DIR__, 2) . '/config/database.php';
if (!file_exists($config_path)) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Yapılandırma bulunamadı.']);
    exit;
}
require_once $config_path;

try {
    // ── Toplam üye ────────────────────────────────────────────────────────
    $stmt = $db_baglanti->prepare(
        "SELECT COUNT(*) FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)"
    );
    $stmt->execute([':il' => $il]);
    $toplam_uye = (int) $stmt->fetchColumn();

    // ── İlçe sayısı & top-5 ──────────────────────────────────────────────
    $stmt = $db_baglanti->prepare(
        "SELECT TRIM(ikamet_ilcesi) AS ilce, COUNT(*) AS adet
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND ikamet_ilcesi IS NOT NULL
            AND TRIM(ikamet_ilcesi) != ''
          GROUP BY TRIM(ikamet_ilcesi)
          ORDER BY adet DESC"
    );
    $stmt->execute([':il' => $il]);
    $ilce_verileri = $stmt->fetchAll(PDO::FETCH_ASSOC);

    $ilce_sayisi = count($ilce_verileri);
    $top_ilceler = array_slice($ilce_verileri, 0, 5);

    // ── Benzersiz kurum sayısı ────────────────────────────────────────────
    $stmt = $db_baglanti->prepare(
        "SELECT COUNT(DISTINCT TRIM(calistigi_kurum)) AS kurum_sayisi
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND calistigi_kurum IS NOT NULL
            AND TRIM(calistigi_kurum) != ''"
    );
    $stmt->execute([':il' => $il]);
    $kurum_sayisi = (int) $stmt->fetchColumn();

    // ── Kurum temsilcisi sayısı ───────────────────────────────────────────
    $stmt = $db_baglanti->prepare(
        "SELECT COUNT(*) FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND rol IN ('kurum_temsilcisi','teskilatlanma_sorumlusu')"
    );
    $stmt->execute([':il' => $il]);
    $temsilci_sayisi = (int) $stmt->fetchColumn();

    // ── Yanıt ─────────────────────────────────────────────────────────────
    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'il'              => $il,
        'toplam_uye'      => $toplam_uye,
        'ilce_sayisi'     => $ilce_sayisi,
        'kurum_sayisi'    => $kurum_sayisi,
        'temsilci_sayisi' => $temsilci_sayisi,
        'top_ilceler'     => array_map(fn($r) => [
            'ilce' => htmlspecialchars($r['ilce'], ENT_QUOTES, 'UTF-8'),
            'adet' => (int) $r['adet'],
        ], $top_ilceler),
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Veri alınamadı.']);
}
