<?php
declare(strict_types=1);

/**
 * Yaş Filtresi PDF Çıktısı — Sadece Geliştirici Rolüne Açıktır.
 *
 * Tarayıcıda otomatik yazdırma diyaloğu açılır.
 * Mevcut pdf-indir.php stiliyle tutarlı tasarım.
 */

require_once __DIR__ . '/../inc/baglan.php';
require_once __DIR__ . '/../inc/log-kayit.php';

// Oturum ve yetki kontrolü
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die('Yetkisiz erişim!');
}
$rol = $_SESSION['rol'] ?? '';
if ($rol !== 'gelistirici') {
    die('Yetkisiz erişim! Bu sayfa sadece geliştirici hesabına açıktır.');
}

// ─── FİLTRE PARAMETRELERİ ────────────────────────────────────────────────
$f_yas_turu = in_array($_GET['yas_turu'] ?? '', ['altinda','ustunde','arasinda'], true)
    ? $_GET['yas_turu'] : 'altinda';

$f_yas1 = isset($_GET['yas1']) && ctype_digit((string)$_GET['yas1']) ? (int)$_GET['yas1'] : 35;
$f_yas2 = isset($_GET['yas2']) && ctype_digit((string)$_GET['yas2']) ? (int)$_GET['yas2'] : 45;
$f_yas1 = max(1, min(100, $f_yas1));
$f_yas2 = max(1, min(100, $f_yas2));

$f_il      = trim($_GET['il'] ?? '');
$f_statu   = trim($_GET['statu'] ?? '');
$f_calisma = trim($_GET['calisma'] ?? '');

// ─── YAŞ HESAPLAMA EXPR ──────────────────────────────────────────────────
$yas_expr = "TIMESTAMPDIFF(YEAR,
    CASE
        WHEN dogum_tarihi REGEXP '^[0-9]{4}-[0-9]{2}-[0-9]{2}$'
            THEN STR_TO_DATE(dogum_tarihi, '%Y-%m-%d')
        WHEN dogum_tarihi REGEXP '^[0-9]{2}[./][0-9]{2}[./][0-9]{4}$'
            THEN STR_TO_DATE(REPLACE(dogum_tarihi, '.', '/'), '%d/%m/%Y')
        ELSE NULL
    END,
    CURDATE())";

// ─── VERİ SORGUSU ────────────────────────────────────────────────────────
try {
    $where  = ["onay_durumu = 'onayli'",
               "dogum_tarihi IS NOT NULL",
               "dogum_tarihi != ''",
               "dogum_tarihi != '0000-00-00'",
               "LENGTH(dogum_tarihi) >= 8"];
    $params = [];

    if ($f_yas_turu === 'altinda') {
        $where[]  = "({$yas_expr}) < ?";
        $params[] = $f_yas1;
    } elseif ($f_yas_turu === 'ustunde') {
        $where[]  = "({$yas_expr}) > ?";
        $params[] = $f_yas1;
    } else {
        $yas_min  = min($f_yas1, $f_yas2);
        $yas_max  = max($f_yas1, $f_yas2);
        $where[]  = "({$yas_expr}) BETWEEN ? AND ?";
        $params[] = $yas_min;
        $params[] = $yas_max;
    }

    if ($f_il !== '') {
        $where[]  = "ikamet_ili = ?";
        $params[] = $f_il;
    }
    if ($f_statu !== '') {
        $where[]  = "(temsilci_turu = ? OR ek_gorev = ?)";
        $params[] = $f_statu;
        $params[] = $f_statu;
    }
    if ($f_calisma !== '') {
        $where[]  = "calisma_sekli = ?";
        $params[] = $f_calisma;
    }

    $sql = "SELECT id, adi_soyadi, telefon, eposta, dogum_tarihi, kan_grubu,
                   ikamet_ili, trabzon_ilcesi, kurum, gorev_unvan, calisma_sekli,
                   temsilci_turu, ek_gorev, sorumlu_bolge,
                   ({$yas_expr}) AS hesap_yas
              FROM dernek_uyeler
             WHERE " . implode(" AND ", $where) . "
             ORDER BY hesap_yas ASC, adi_soyadi ASC";

    $sorgu = $db_baglanti->prepare($sql);
    $sorgu->execute($params);
    $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    log_kaydet($db_baglanti, 'yas_filtresi_pdf',
        'Yaş filtresi PDF indirildi: ' . $f_yas_turu . ' ' . $f_yas1
        . ($f_yas_turu === 'arasinda' ? '-'.$f_yas2 : '')
        . ' yaş — ' . count($uyeler) . ' üye.');

} catch (\PDOException $e) {
    error_log('Yaş filtresi PDF hata: ' . $e->getMessage());
    die('Veri aktarımı sırasında bir hata oluştu.');
}

// ─── FİLTRE BAŞLIĞI ──────────────────────────────────────────────────────
if ($f_yas_turu === 'altinda')     $filtre_baslik = $f_yas1 . ' Yaş Altı Üyeler';
elseif ($f_yas_turu === 'ustunde') $filtre_baslik = $f_yas1 . ' Yaş Üstü Üyeler';
else $filtre_baslik = min($f_yas1,$f_yas2) . '–' . max($f_yas1,$f_yas2) . ' Yaş Arası Üyeler';

if ($f_il !== '')     $filtre_baslik .= ' / ' . htmlspecialchars($f_il);
if ($f_statu !== '')  $filtre_baslik .= ' / ' . htmlspecialchars($f_statu);
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>T.K.Ç.D. Yaş Filtresi — <?= htmlspecialchars($filtre_baslik); ?></title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    html, body {
        background:#fff; margin:0; padding:0;
        width:1050px !important; height:auto !important;
        -webkit-print-color-adjust:exact !important;
        print-color-adjust:exact !important;
    }
    body { font-family:'Helvetica Neue',Helvetica,Arial,sans-serif; font-size:11px; color:#333; }

    .rapor-baslik {
        text-align:center; margin:10px 0 15px; padding-bottom:8px;
        border-bottom:3px solid #610012; width:1050px;
    }
    .rapor-baslik h2 { color:#610012; margin:0; font-size:19px; font-weight:bold; }
    .rapor-baslik p  { margin:5px 0 0; font-size:11px; color:#555; font-weight:bold; }

    .ozet-kutu {
        display:inline-block; margin:0 6px 12px;
        padding:5px 14px; border-radius:20px;
        font-size:10px; font-weight:bold;
    }
    .ozet-toplam   { background:#e8f5e9; color:#1b5e20; border:1px solid #a5d6a7; }
    .ozet-filtre   { background:#fff3e0; color:#e65100; border:1px solid #ffcc80; }

    table { border-collapse:collapse; width:1050px !important; table-layout:fixed; page-break-inside:auto; }
    tr    { page-break-inside:avoid !important; }
    td,th { page-break-inside:avoid !important; word-wrap:break-word; }
    thead { display:table-header-group !important; }

    th {
        background:#1a1a2e !important; color:#fff !important;
        font-weight:bold; border:1px solid #343a40;
        text-align:left; padding:6px 5px;
        font-size:10px; text-transform:uppercase;
    }
    td {
        border:1px solid #dee2e6 !important;
        padding:5px; vertical-align:middle; font-size:10.5px;
    }
    .yas-badge {
        display:inline-block; padding:2px 8px; border-radius:20px;
        font-weight:bold; font-size:10px;
    }
    .yas-genç    { background:#e3f2fd; color:#1565c0; }
    .yas-orta1   { background:#e8f5e9; color:#1b5e20; }
    .yas-orta2   { background:#fff3e0; color:#e65100; }
    .yas-yasli   { background:#f3e5f5; color:#4a148c; }

    @media print {
        html,body { width:1050px !important; background:#fff; }
        table { width:1050px !important; }
        th { background:#1a1a2e !important; color:#fff !important; }
    }
</style>
</head>
<body onload="window.print();">

<div class="rapor-baslik">
    <h2>T.K.Ç.D. — YAŞ FİLTRESİ RAPORU</h2>
    <p><?= htmlspecialchars($filtre_baslik); ?> &nbsp;|&nbsp; Tarih: <?= date('d.m.Y H:i'); ?></p>
    <div style="margin-top:8px;">
        <span class="ozet-kutu ozet-toplam">Toplam: <?= count($uyeler); ?> üye</span>
        <?php if ($f_il !== ''): ?>
        <span class="ozet-kutu ozet-filtre">İl: <?= htmlspecialchars($f_il); ?></span>
        <?php endif; ?>
        <?php if ($f_statu !== ''): ?>
        <span class="ozet-kutu ozet-filtre">Statü: <?= htmlspecialchars($f_statu); ?></span>
        <?php endif; ?>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:3%;">#</th>
            <th style="width:14%;">Adı Soyadı</th>
            <th style="width:5%;text-align:center;">Yaş</th>
            <th style="width:5%;text-align:center;">Kan</th>
            <th style="width:9%;text-align:center;">D.Tarihi</th>
            <th style="width:11%;">Telefon</th>
            <th style="width:14%;">E-Posta</th>
            <th style="width:7%;">İl</th>
            <th style="width:7%;">İlçe</th>
            <th style="width:13%;">Kurum</th>
            <th style="width:12%;">Statü</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($uyeler as $idx => $u):
        $yas = (int)($u['hesap_yas'] ?? 0);

        if ($yas < 25)     $yas_cls = 'yas-genç';
        elseif ($yas < 35) $yas_cls = 'yas-orta1';
        elseif ($yas < 50) $yas_cls = 'yas-orta2';
        else               $yas_cls = 'yas-yasli';

        $dogum = '';
        if (!empty($u['dogum_tarihi']) && $u['dogum_tarihi'] !== '0000-00-00') {
            $dt = trim($u['dogum_tarihi']);
            if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $dt, $m)) {
                $dogum = $m[3] . '.' . $m[2] . '.' . $m[1];
            } else {
                $dogum = str_replace('/', '.', $dt);
            }
        }

        $statu = trim($u['temsilci_turu'] ?: '');
        if (!empty($u['sorumlu_bolge'])) $statu .= ' (' . $u['sorumlu_bolge'] . ')';
        if (!empty($u['ek_gorev']))      $statu .= ' | +' . $u['ek_gorev'];
    ?>
    <tr>
        <td style="text-align:center;color:#888;"><?= $idx+1; ?></td>
        <td style="font-weight:bold;"><?= htmlspecialchars($u['adi_soyadi'] ?: '-'); ?></td>
        <td style="text-align:center;">
            <span class="yas-badge <?= $yas_cls; ?>"><?= $yas > 0 ? $yas : '?'; ?></span>
        </td>
        <td style="text-align:center;font-weight:bold;color:#b30000;"><?= htmlspecialchars($u['kan_grubu'] ?: '-'); ?></td>
        <td style="text-align:center;"><?= htmlspecialchars($dogum ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['eposta'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['ikamet_ili'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['trabzon_ilcesi'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['kurum'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($statu ?: 'Üye'); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
