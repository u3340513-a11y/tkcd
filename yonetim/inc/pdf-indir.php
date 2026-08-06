<?php
// Bu dosya yonetim/inc/pdf-indir.php olarak kaydedilecektir.
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
    error_log('Yönetim PDF hata: ' . $e->getMessage());
    die('Veri aktarımı sırasında bir hata oluştu.');
}

$bugun = date('d.m.Y');
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>Dernek Üye Listesi Raporu - <?= $bugun; ?></title>
<style>
    @page { size: A4 landscape; margin: 10mm 10mm 10mm 10mm; }
    html, body { background-color: #ffffff; margin: 0; padding: 0; width: 1050px !important; height: auto !important; -webkit-print-color-adjust: exact !important; print-color-adjust: exact !important; }
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }
    .rapor-baslik { text-align: center; margin-top: 10px; margin-bottom: 15px; border-bottom: 3px solid #610012; padding-bottom: 8px; width: 1050px; }
    .rapor-baslik h2 { color: #610012; margin: 0; font-size: 19px; font-weight: bold; letter-spacing: 0.5px; }
    .rapor-baslik p { margin: 5px 0 0 0; font-size: 11px; color: #555; font-weight: bold; }
    table { border-collapse: collapse; width: 1050px !important; table-layout: fixed; page-break-inside: auto; }
    tr { page-break-inside: avoid !important; page-break-after: auto !important; }
    td, th { page-break-inside: avoid !important; word-wrap: break-word; }
    thead { display: table-header-group !important; } 
    th { background-color: #212529 !important; color: #ffffff !important; font-weight: bold; border: 1px solid #343a40; text-align: left; padding: 6px 4px; font-size: 10.5px; text-transform: uppercase; }
    td { border: 1px solid #dee2e6 !important; padding: 5px 4px; text-align: left; vertical-align: middle; font-size: 10.5px; }
    .fw-bold { font-weight: bold; }
    .text-center { text-align: center; }
    @media print {
        html, body { width: 1050px !important; background-color: #ffffff; height: auto !important; zoom: 100%; }
        table { width: 1050px !important; }
        th { background-color: #212529 !important; color: #ffffff !important; }
    }
</style>
</head>
<body onload="window.print();">

    <div class="rapor-baslik">
        <h2>T.K.Ç.D. AKTİF ÜYE LİSTESİ RAPORU</h2>
        <p>Raporlama Tarihi: <?= date('d.m.Y H:i'); ?> 
            <?php 
            if(!empty($aktif_filtre)) {
                echo ' | Kart: "' . htmlspecialchars(str_replace('_', ' ', $aktif_filtre)) . '"';
            }
            if(!empty($arama_kelimesi)) {
                echo ' | Filtre: "' . htmlspecialchars($arama_kelimesi) . '"'; 
            }
            ?>
        </p>
    </div>

    <table>
        <thead>
            <tr>
                <th style="width: 14%;">Adı Soyadı</th>
                <th style="width: 11%;">Telefon</th>
                <th style="width: 15%;">E-Posta</th>
                <th style="width: 5%; text-align: center;">Kan</th>
                <th style="width: 9%; text-align: center;">D.Tarihi</th>
                <th style="width: 7%;">İkamet</th>
                <th style="width: 8%;">İlçe</th>
                <th style="width: 11%;">Kurum</th>
                <th style="width: 10%;">Ünvan</th>
                <th style="width: 10%;">Statü</th>
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

                // EK GÖREV VARSA PDF ÇIKTISINA EKLE
                if (!empty($u['ek_gorev'])) {
                    $unvan_adi .= " <br><small style='color:#610012;'><b>(+Ek Görev: " . htmlspecialchars($u['ek_gorev']) . ")</b></small>";
                }

                $kan = !empty($u['kan_grubu']) ? $u['kan_grubu'] : '-';
                $dogum = !empty($u['dogum_tarihi']) ? date('d.m.Y', strtotime($u['dogum_tarihi'])) : (!empty($u['dogum_yili']) ? $u['dogum_yili'] : '-');
                ?>
                <tr <?= $satir_stili; ?>>
                    <td class="fw-bold"><?= htmlspecialchars($u['adi_soyadi'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['eposta'] ?: '-'); ?></td>
                    <td class="text-center fw-bold" style="color: #b30000;"><?= htmlspecialchars($kan); ?></td>
                    <td class="text-center"><?= htmlspecialchars($dogum); ?></td>
                    <td><?= htmlspecialchars($u['ikamet_ili'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['trabzon_ilcesi'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['kurum'] ?: '-'); ?></td>
                    <td><?= htmlspecialchars($u['gorev_unvan'] ?: '-'); ?></td>
                    <td class="fw-bold"><?= $unvan_adi; ?></td>
                </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>