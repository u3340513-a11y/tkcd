<?php

declare(strict_types=1);

/**
 * Notu Olmayan Üyeler — Excel (CSV) indirme.
 *
 * index.php içinden, HTML çıktısından önce çalışır.
 * GET: cinsiyet = Kadın | Erkek | hepsi
 *
 * @var PDO $db_baglanti
 */

require_once __DIR__ . '/baglan.php';
require_once __DIR__ . '/notsuz-uyeler-sorgu.php';

const NOTSUZ_RAPOR_IZINLI_ROL = 'gelistirici';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(403);
    die('Yetkisiz erişim!');
}
if (($_SESSION['rol'] ?? '') !== NOTSUZ_RAPOR_IZINLI_ROL) {
    http_response_code(403);
    die('Bu raporu indirme yetkiniz yok.');
}

$cinsiyet = NotsuzUyeRaporu::cinsiyetDogrula(trim((string) ($_GET['cinsiyet'] ?? '')));
if ($cinsiyet === null) {
    http_response_code(400);
    die('Geçersiz cinsiyet filtresi.');
}

try {
    $uyeler = (new NotsuzUyeRaporu($db_baglanti))->uyeler($cinsiyet);
} catch (PDOException $e) {
    error_log('Notsuz üye raporu hatası: ' . $e->getMessage());
    http_response_code(500);
    die('Rapor oluşturulurken bir hata oluştu.');
}

// Telefon ve e-posta yalnızca iletişim bilgisini görme yetkisi olanlara verilir
$iletisimDahil = kisi_bilgisi_gorebilir();

log_kaydet(
    $db_baglanti,
    'excel_indir',
    'Notsuz üyeler raporu indirildi (cinsiyet: ' . $cinsiyet . ', ' . count($uyeler) . ' kayıt).'
);

$dosyaEki = $cinsiyet === NotsuzUyeRaporu::CINSIYET_HER_IKISI
    ? 'tum'
    : ($cinsiyet === 'Kadın' ? 'kadin' : 'erkek');

header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="notsuz_uyeler_' . $dosyaEki . '_' . date('Ymd_His') . '.csv"');
header('Cache-Control: no-cache, no-store, must-revalidate');
header('Pragma: no-cache');
header('Expires: 0');

$cikti = fopen('php://output', 'w');
fwrite($cikti, "\xEF\xBB\xBF"); // UTF-8 BOM — Excel Türkçe karakterleri doğru okusun

$baslik = ['#', 'Adı Soyadı', 'Cinsiyet'];
if ($iletisimDahil) {
    array_push($baslik, 'Telefon', 'E-Posta');
}
array_push(
    $baslik,
    'İkamet İli',
    'İkamet İlçesi',
    'Trabzon İlçesi',
    'Kurum',
    'Görev / Ünvan',
    'Çalışma Şekli',
    'Statü',
    'Üyelik Tarihi'
);
fputcsv($cikti, $baslik, ';');

foreach ($uyeler as $sira => $uye) {
    $uyelikTarihi = '';
    if (!empty($uye['uyelik_tarihi']) && $uye['uyelik_tarihi'] !== '0000-00-00') {
        $uyelikTarihi = date('d.m.Y', strtotime((string) $uye['uyelik_tarihi']));
    }

    $satir = [$sira + 1, $uye['adi_soyadi'], $uye['cinsiyet']];
    if ($iletisimDahil) {
        array_push($satir, $uye['telefon'], $uye['eposta']);
    }
    array_push(
        $satir,
        $uye['ikamet_ili'],
        $uye['ikamet_ilcesi'],
        $uye['trabzon_ilcesi'],
        $uye['kurum'],
        $uye['gorev_unvan'],
        $uye['calisma_sekli'],
        $uye['temsilci_turu'] ?: 'Normal Üye',
        $uyelikTarihi
    );

    fputcsv($cikti, array_map([NotsuzUyeRaporu::class, 'csvHucre'], $satir), ';');
}

fclose($cikti);
exit;
