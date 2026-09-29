<?php
/**
 * İl detay API endpoint'i.
 *
 * Belirtilen il için:
 *   - Toplam üye sayısı
 *   - Üye bulunan ilçe sayısı
 *   - Benzersiz kurum sayısı
 *   - Kurum temsilcisi / teşkilatlanma sorumlusu sayısı
 *   - En yoğun 5 ilçe listesi
 *
 * GET /yonetim/api/il-detay.php?il=Trabzon
 *
 * Güvenlik:
 *   - baglan.php üzerinden oturum + DB bağlantısı
 *   - XHR başlık kontrolü (CSRF katmanı)
 *   - Prepared statements (SQL injection koruması)
 */

declare(strict_types=1);

// baglan.php hem session'ı başlatır hem DB bağlantısını ($db_baglanti) oluşturur
require_once dirname(__DIR__) . '/inc/baglan.php';

// ── Yalnızca XHR kabul et ────────────────────────────────────────────────
$isXhr = ($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') === 'XMLHttpRequest';
if (!$isXhr) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Geçersiz istek.']);
    exit;
}

// ── Oturum kontrolü (baglan.php session'ı zaten başlattı) ────────────────
if (empty($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Yetkisiz erişim.']);
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

    // ── Temsilci sayısı ───────────────────────────────────────────────────
    $stmt = $db_baglanti->prepare(
        "SELECT COUNT(*) FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND (
                temsilci_turu IS NOT NULL AND TRIM(temsilci_turu) != ''
                OR ek_gorev   IS NOT NULL AND TRIM(ek_gorev)     != ''
            )"
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
            'ilce' => htmlspecialchars((string) $r['ilce'], ENT_QUOTES, 'UTF-8'),
            'adet' => (int) $r['adet'],
        ], $top_ilceler),
    ], JSON_UNESCAPED_UNICODE);

} catch (\Throwable $e) {
    error_log('il-detay API hatası: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Veri alınamadı.']);
}
