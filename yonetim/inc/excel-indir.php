<?php
// Bu dosya yonetim/inc/excel-indir.php olarak kaydedilecektir.
require_once __DIR__ . '/../inc/baglan.php';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die("Yetkisiz erişim!");
}

$arama_kelimesi = isset($_GET['arama']) ? trim($_GET['arama']) : '';
$aktif_filtre   = isset($_GET['filtre']) ? trim($_GET['filtre']) : '';

try {
    $where_sartlari = ["onay_durumu = 'onayli'"];
    $parametreler   = [];

    if ($aktif_filtre === 'kurum_temsilcisi') {
        $where_sartlari[] = "(temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')";
    } elseif ($aktif_filtre === 'yonetim_kurulu') {
        $where_sartlari[] = "(temsilci_turu LIKE '%Yönetim Kurulu%' OR temsilci_turu = 'Yönetici' OR ek_gorev LIKE '%Yönetim Kurulu%' OR ek_gorev = 'Yönetici')";
    } elseif ($aktif_filtre === 'bolge_koordinatoru') {
        $where_sartlari[] = "(temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')";
    } elseif ($aktif_filtre === 'il_baskani') {
        $where_sartlari[] = "(temsilci_turu LIKE '%İl Baş%' OR temsilci_turu LIKE '%İl Temp%' OR ek_gorev LIKE '%İl Baş%' OR ek_gorev LIKE '%İl Temp%')";
    } elseif ($aktif_filtre === 'ilce_baskani') {
        $where_sartlari[] = "(temsilci_turu LIKE '%İlçe Baş%' OR temsilci_turu LIKE '%İlçe Temp%' OR ek_gorev LIKE '%İlçe Baş%' OR ek_gorev LIKE '%İlçe Temp%')";
    } elseif ($aktif_filtre === 'aktif_iller') {
        $where_sartlari[] = "ikamet_ili IS NOT NULL AND ikamet_ili != ''";
    }

    if (!empty($arama_kelimesi)) {
        $where_sartlari[] = "(adi_soyadi LIKE ? OR ikamet_ili LIKE ? OR trabzon_ilcesi LIKE ? OR temsilci_turu LIKE ? OR kurum LIKE ? OR gorev_unvan LIKE ? OR ek_gorev LIKE ?)";
        $aranacak_deger = "%" . $arama_kelimesi . "%";
        for ($i = 0; $i < 7; $i++) {
            $parametreler[] = $aranacak_deger;
        }
    }

    $sql = "SELECT * FROM dernek_uyeler WHERE " . implode(" AND ", $where_sartlari) . " ORDER BY adi_soyadi ASC";
    
    $sorgu = $db_baglanti->prepare($sql);
    if (!empty($parametreler)) {
        $sorgu->execute($parametreler);
    } else {
        $sorgu->execute();
    }
    $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

} catch (\PDOException $e) {
    error_log('Yönetim Excel hata: ' . $e->getMessage());
    die('Veri aktarımı sırasında bir hata oluştu.');
}

$bugun = date('Y-m-d');
$dosya_adi = "Dernek_Uye_Listesi_" . $bugun . ".xls";

header("Content-Type: application/vnd.ms-excel; charset=UTF-8");
header("Content-Disposition: attachment; filename=\"$dosya_adi\"");
header("Pragma: no-cache");
header("Expires: 0");

echo "\xEF\xBB\xBF"; 
?>
<html>
<head>
<meta charset="utf-8">
<style>
    table { border-collapse: collapse; width: 100%; font-family: Calibri, sans-serif; }
    th { background-color: #212529 !important; color: #ffffff !important; font-weight: bold; border: 1px solid #343a40; text-align: left; padding: 10px; }
    td { border: 1px solid #dee2e6 !important; padding: 8px; text-align: left; vertical-align: middle; }
</style>
</head>
<body>
    <table border="1">
        <thead>
            <tr>
                <th>Adı Soyadı</th>
                <th>Telefon</th>
                <th>E-Posta</th>
                <th>Kan Grubu</th>
                <th>Doğum Tarihi / Yılı</th>
                <th>İkamet İli</th>
                <th>Trabzon İlçesi</th>
                <th>Kurum</th>
                <th>Ünvan</th>
                <th>Çalışma Şekli</th>
                <th>Üyelik Statüsü</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($uyeler as $u): ?>
                <?php 
                $satir_stili = "";
                $statü = trim($u['temsilci_turu']);

                if ($statü === 'Yönetim Kurulu Üyesi') {
                    $satir_stili = 'style="background-color: #CFE2FF !important; color: #084298;"';
                } elseif ($statü === 'Yönetim Kurulu Üyesi Yedek') {
                    $satir_stili = 'style="background-color: #CFF4FC !important; color: #055160;"';
                } elseif ($statü === 'İl Başkanı') {
                    $satir_stili = 'style="background-color: #D1E7DD !important; color: #0f5132;"';
                } elseif ($statü === 'İlçe Başkanı') {
                    $satir_stili = 'style="background-color: #E1BEE7 !important; color: #4A148C;"';
                } elseif ($statü === 'Kurum Temsilcisi') {
                    $satir_stili = 'style="background-color: #FFF3CD !important; color: #664d03;"';
                } elseif ($statü === 'Bölge Koordinatörü') {
                    $satir_stili = 'style="background-color: #E0F7FA !important; color: #006064;"'; 
                }
                
                // Statü ve Sorumlu Bölge Metni
                $unvan_adi = htmlspecialchars($u['temsilci_turu'] ?: '-');
                if (!empty($u['sorumlu_bolge'])) {
                    $unvan_adi .= " (" . htmlspecialchars($u['sorumlu_bolge']) . ")";
                }

                // EK GÖREV VARSA STATÜNÜN YANINA EKLE
                if (!empty($u['ek_gorev'])) {
                    $unvan_adi .= " + Ek Görev: " . htmlspecialchars($u['ek_gorev']);
                }

                $kan = !empty($u['kan_grubu']) ? $u['kan_grubu'] : '-';
                $dogum = !empty($u['dogum_tarihi']) ? date('d.m.Y', strtotime($u['dogum_tarihi'])) : (!empty($u['dogum_yili']) ? $u['dogum_yili'] : '-');
                ?>
                <tr <?= $satir_stili; ?>>
                    <td style="font-weight: bold; border: 1px solid #dee2e6;"><?= htmlspecialchars($u['adi_soyadi'] ?: '-'); ?></td>
                    <td style="border: 1px solid #dee2e6; mso-number-format:'\@';"><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['eposta'] ?: '-'); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($kan); ?></td>
                    <td style="text-align: center;"><?= htmlspecialchars($dogum); ?></td>
                    <td><?= htmlspecialchars($u['ikamet_ili'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['trabzon_ilcesi'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['kurum'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['gorev_unvan'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['calisma_sekli'] ?: '-'); ?></td>
                    <td style="font-weight: 500;"><?= $unvan_adi; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>
</body>
</html>