<?php
/**
 * Tek Kullanımlık Rapor: Notu Olmayan Kadın Üyeler
 *
 * Onaylı kadın üyeler arasında dernek_notlar tablosunda
 * hiçbir kaydı bulunmayanları Excel (CSV) olarak indirir.
 *
 * Kullanım: index.php?sayfa=kadin-notsuz-excel
 * Kullanıldıktan sonra bu dosya ve router kaydı silinebilir.
 */

require_once __DIR__ . '/../inc/baglan.php';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(403);
    die('Yetkisiz erişim!');
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'gelistirici'], true)) {
    http_response_code(403);
    die('Bu raporu yalnızca Admin ve Geliştirici hesapları indirebilir.');
}

try {
    /*
     * Onaylı + Kadın + dernek_notlar tablosunda hiç kaydı olmayan üyeler.
     * LEFT JOIN + NULL kontrolü ile N+1 sorgudan kaçınılıyor.
     */
    $sorgu = $db_baglanti->prepare("
        SELECT
            u.id,
            u.adi_soyadi,
            u.telefon,
            u.eposta,
            u.ikamet_ili,
            u.ikamet_ilcesi,
            u.trabzon_ilcesi,
            u.kurum,
            u.gorev_unvan,
            u.calisma_sekli,
            u.temsilci_turu,
            u.uyelik_tarihi
        FROM dernek_uyeler u
        LEFT JOIN dernek_notlar n ON n.uye_id = u.id
        WHERE u.onay_durumu = 'onayli'
          AND u.cinsiyet    = 'Kadın'
        GROUP BY u.id
        HAVING COUNT(n.id) = 0
        ORDER BY u.adi_soyadi ASC
    ");
    $sorgu->execute();
    $veriler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    die('Sorgu hatası: ' . htmlspecialchars($e->getMessage()));
}

// ── Excel (UTF-8 BOM CSV) çıktısı ──────────────────────────────────────
$dosyaAdi = 'kadin_notsuz_uyeler_' . date('Ymd_His') . '.csv';

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$cikti = fopen('php://output', 'w');

// UTF-8 BOM — Excel'in Türkçe karakterleri doğru okuması için
fputs($cikti, "\xEF\xBB\xBF");

// Başlık satırı
fputcsv($cikti, [
    '#',
    'Adı Soyadı',
    'Telefon',
    'E-Posta',
    'İkamet İli',
    'İkamet İlçesi',
    'Trabzon İlçesi',
    'Kurum',
    'Görev / Ünvan',
    'Çalışma Şekli',
    'Statü',
    'Üyelik Tarihi',
], ';');

$sira = 1;
foreach ($veriler as $uye) {
    $uyelikTarihi = '';
    if (!empty($uye['uyelik_tarihi']) && $uye['uyelik_tarihi'] !== '0000-00-00') {
        $uyelikTarihi = date('d.m.Y', strtotime($uye['uyelik_tarihi']));
    }

    fputcsv($cikti, [
        $sira++,
        $uye['adi_soyadi'],
        $uye['telefon'],
        $uye['eposta'],
        $uye['ikamet_ili'],
        $uye['ikamet_ilcesi'],
        $uye['trabzon_ilcesi'],
        $uye['kurum'],
        $uye['gorev_unvan'],
        $uye['calisma_sekli'],
        $uye['temsilci_turu'] ?: 'Normal Üye',
        $uyelikTarihi,
    ], ';');
}

fclose($cikti);
exit;
