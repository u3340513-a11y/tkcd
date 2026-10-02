<?php
/**
 * İlçe üyeleri API endpoint'i.
 *
 * Belirtilen il + ilçe için onaylı üye listesini döndürür.
 * GET /yonetim/api/ilce-uyeler.php?il=Sakarya&ilce=Kocaali
 *
 * Güvenlik:
 *   - baglan.php üzerinden oturum + DB bağlantısı
 *   - XHR başlık kontrolü
 *   - Yalnızca yetkili kullanıcılar (index.php ile aynı kural)
 *   - Prepared statements (SQL injection koruması)
 *   - Çıktı htmlspecialchars ile escape edilir
 */

declare(strict_types=1);

require_once dirname(__DIR__) . '/inc/baglan.php';

// ── Yalnızca XHR kabul et ────────────────────────────────────────────────
if (($_SERVER['HTTP_X_REQUESTED_WITH'] ?? '') !== 'XMLHttpRequest') {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Geçersiz istek.']);
    exit;
}

// ── Oturum kontrolü ──────────────────────────────────────────────────────
if (empty($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(401);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Yetkisiz erişim.']);
    exit;
}

// ── Rol / kullanıcı adı yetkisi ───────────────────────────────────────────
$kullanici_rolu = $_SESSION['rol'] ?? '';
$kullanici_adi  = $_SESSION['kullanici_adi'] ?? '';

$yetkili_roller    = ['admin', 'yonetim', 'gelistirici'];
$yetkili_kullanicilar = ['yonetim_oc', 'yonetim_kby', 'yonetim_ac'];

if (!in_array($kullanici_rolu, $yetkili_roller, true)
    && !in_array($kullanici_adi, $yetkili_kullanicilar, true)
) {
    http_response_code(403);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Bu işlem için yetkiniz bulunmuyor.']);
    exit;
}

// ── Girdi doğrulama ───────────────────────────────────────────────────────
$il   = trim((string) ($_GET['il']   ?? ''));
$ilce = trim((string) ($_GET['ilce'] ?? ''));

if ($il === '' || $ilce === ''
    || mb_strlen($il, 'UTF-8') > 60
    || mb_strlen($ilce, 'UTF-8') > 60
) {
    http_response_code(400);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Geçersiz il veya ilçe parametresi.']);
    exit;
}

try {
    // İkamet ilçesi veya Trabzon ilçesi eşleşmesi
    $stmt = $db_baglanti->prepare(
        "SELECT id, adi_soyadi, kurum, gorev_unvan,
                temsilci_turu, ek_gorev, sorumlu_bolge,
                ikamet_ili, ikamet_ilcesi, trabzon_ilcesi
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND (
                LOWER(TRIM(ikamet_ilcesi)) = LOWER(:ilce)
                OR LOWER(TRIM(trabzon_ilcesi)) = LOWER(:ilce2)
            )
          ORDER BY adi_soyadi ASC"
    );
    $stmt->execute([':il' => $il, ':ilce' => $ilce, ':ilce2' => $ilce]);
    $uyeler = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Hassas alanları sil, çıktıyı escape et
    $temiz = array_map(static function (array $uye): array {
        $unvan = trim($uye['temsilci_turu'] ?: '');
        if ($unvan === '' || $unvan === 'Normal Üye') {
            $unvan = trim($uye['ek_gorev'] ?: '');
        }
        return [
            'id'        => (int) $uye['id'],
            'ad'        => htmlspecialchars($uye['adi_soyadi'],  ENT_QUOTES, 'UTF-8'),
            'kurum'     => htmlspecialchars($uye['kurum'] ?? '', ENT_QUOTES, 'UTF-8'),
            'unvan'     => htmlspecialchars($uye['gorev_unvan'] ?? '', ENT_QUOTES, 'UTF-8'),
            'statü'     => htmlspecialchars($unvan,              ENT_QUOTES, 'UTF-8'),
        ];
    }, $uyeler);

    header('Content-Type: application/json; charset=utf-8');
    header('Cache-Control: no-store');
    echo json_encode([
        'il'     => htmlspecialchars($il,   ENT_QUOTES, 'UTF-8'),
        'ilce'   => htmlspecialchars($ilce, ENT_QUOTES, 'UTF-8'),
        'toplam' => count($temiz),
        'uyeler' => $temiz,
    ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
    error_log('ilce-uyeler API hatası: ' . $e->getMessage());
    http_response_code(500);
    header('Content-Type: application/json; charset=utf-8');
    echo json_encode(['hata' => 'Veriler alınamadı.']);
}
