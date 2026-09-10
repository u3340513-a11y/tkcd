<?php
declare(strict_types=1);

/**
 * Son Onaylananlar PDF Çıktısı — Yönetim ve Geliştirici Rolüne Açıktır.
 */

require_once __DIR__ . '/../inc/baglan.php';
require_once __DIR__ . '/../inc/log-kayit.php';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die('Yetkisiz erişim!');
}
$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'yonetim', 'gelistirici'], true)) {
    die('Bu raporu almaya yetkiniz bulunmamaktadır.');
}

// ─── FİLTRE PARAMETRELERİ ────────────────────────────────────────────────
$f_baslangic = trim($_GET['baslangic'] ?? '');
$f_bitis     = trim($_GET['bitis'] ?? '');
$f_il        = trim($_GET['il'] ?? '');
$f_statu     = trim($_GET['statu'] ?? '');
$f_cinsiyet  = trim($_GET['cinsiyet'] ?? '');
$f_adet      = isset($_GET['adet']) && ctype_digit((string)$_GET['adet'])
    ? min((int)$_GET['adet'], 500) : 50;

if ($f_baslangic !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_baslangic)) $f_baslangic = '';
if ($f_bitis !== ''     && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_bitis))     $f_bitis = '';
if (!in_array($f_cinsiyet, ['Erkek', 'Kadın', ''], true)) $f_cinsiyet = '';

// ─── SORGU ───────────────────────────────────────────────────────────────
try {
    $where  = ["onay_durumu = 'onayli'",
               "uyelik_tarihi IS NOT NULL",
               "uyelik_tarihi != '0000-00-00'",
               "uyelik_tarihi != ''"];
    $params = [];

    if ($f_baslangic !== '') { $where[] = "uyelik_tarihi >= ?"; $params[] = $f_baslangic; }
    if ($f_bitis !== '')     { $where[] = "uyelik_tarihi <= ?"; $params[] = $f_bitis; }
    if ($f_il !== '')        { $where[] = "ikamet_ili = ?";     $params[] = $f_il; }
    if ($f_statu !== '') {
        $where[] = "(temsilci_turu = ? OR ek_gorev = ?)";
        $params[] = $f_statu; $params[] = $f_statu;
    }
    if ($f_cinsiyet !== '') { $where[] = "cinsiyet = ?"; $params[] = $f_cinsiyet; }

    $sql = "SELECT id, adi_soyadi, telefon, eposta, dogum_tarihi, kan_grubu,
                   ikamet_ili, trabzon_ilcesi, kurum, gorev_unvan, calisma_sekli,
                   cinsiyet, temsilci_turu, ek_gorev, sorumlu_bolge, uyelik_tarihi
              FROM dernek_uyeler
             WHERE " . implode(" AND ", $where) . "
             ORDER BY uyelik_tarihi DESC, kayit_tarihi DESC
             LIMIT ?";
    $params[] = $f_adet;

    $sorgu = $db_baglanti->prepare($sql);
    $sorgu->execute($params);
    $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    log_kaydet($db_baglanti, 'son_onaylananlar_pdf',
        'Son onaylananlar PDF indirildi: ' . count($uyeler) . ' üye.');

} catch (\PDOException $e) {
    error_log('Son onaylananlar PDF hata: ' . $e->getMessage());
    die('Veri aktarımı sırasında bir hata oluştu.');
}

// ─── RAPOR BAŞLIĞI ───────────────────────────────────────────────────────
$baslik_parcalari = [];
if ($f_baslangic !== '' || $f_bitis !== '') {
    $baslik_parcalari[] = ($f_baslangic ? date('d.m.Y', strtotime($f_baslangic)) : '—')
        . ' — '
        . ($f_bitis ? date('d.m.Y', strtotime($f_bitis)) : 'Bugün');
}
if ($f_il !== '')      $baslik_parcalari[] = htmlspecialchars($f_il);
if ($f_statu !== '')   $baslik_parcalari[] = htmlspecialchars($f_statu);
if ($f_cinsiyet !== '') $baslik_parcalari[] = htmlspecialchars($f_cinsiyet);
$rapor_baslik = implode(' / ', $baslik_parcalari) ?: 'Tüm Onaylı Üyeler';
?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>T.K.Ç.D. Son Onaylananlar — <?= htmlspecialchars($rapor_baslik); ?></title>
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
        border-bottom:3px solid #198754; width:1050px;
    }
    .rapor-baslik h2 { color:#198754; margin:0; font-size:19px; font-weight:bold; }
    .rapor-baslik p  { margin:5px 0 0; font-size:11px; color:#555; font-weight:bold; }

    .ozet-kutu {
        display:inline-block; margin:0 6px 12px;
        padding:5px 14px; border-radius:20px;
        font-size:10px; font-weight:bold;
    }
    .ozet-toplam { background:#e8f5e9; color:#1b5e20; border:1px solid #a5d6a7; }
    .ozet-filtre { background:#fff3e0; color:#e65100; border:1px solid #ffcc80; }

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
    td { border:1px solid #dee2e6 !important; padding:5px; vertical-align:middle; font-size:10.5px; }

    .yeni-badge  { display:inline-block;padding:2px 7px;border-radius:20px;font-weight:bold;font-size:9px; }
    .renk-yeni   { background:#e8f5e9; color:#1b5e20; }
    .renk-yakin  { background:#e3f2fd; color:#1565c0; }
    .renk-eski   { background:#f5f5f5; color:#616161; }

    .cins-e { color:#1565c0; font-weight:bold; }
    .cins-k { color:#c2185b; font-weight:bold; }

    @media print {
        html,body { width:1050px !important; background:#fff; }
        table { width:1050px !important; }
        th { background:#1a1a2e !important; color:#fff !important; }
    }
</style>
</head>
<body onload="window.print();">

<div class="rapor-baslik">
    <h2>T.K.Ç.D. — SON ONAYLANANLAR RAPORU</h2>
    <p><?= htmlspecialchars($rapor_baslik); ?> &nbsp;|&nbsp; Oluşturulma: <?= date('d.m.Y H:i'); ?></p>
    <div style="margin-top:8px;">
        <span class="ozet-kutu ozet-toplam">Toplam: <?= count($uyeler); ?> üye</span>
        <?php if ($f_il !== ''): ?>
        <span class="ozet-kutu ozet-filtre">İl: <?= htmlspecialchars($f_il); ?></span>
        <?php endif; ?>
        <?php if ($f_statu !== ''): ?>
        <span class="ozet-kutu ozet-filtre">Statü: <?= htmlspecialchars($f_statu); ?></span>
        <?php endif; ?>
        <?php if ($f_cinsiyet !== ''): ?>
        <span class="ozet-kutu ozet-filtre">Cinsiyet: <?= htmlspecialchars($f_cinsiyet); ?></span>
        <?php endif; ?>
    </div>
</div>

<table>
    <thead>
        <tr>
            <th style="width:3%;">#</th>
            <th style="width:13%;">Adı Soyadı</th>
            <th style="width:9%;text-align:center;">Üyelik Tarihi</th>
            <th style="width:4%;text-align:center;">Cins.</th>
            <th style="width:5%;text-align:center;">Kan</th>
            <th style="width:11%;">Telefon</th>
            <th style="width:14%;">E-Posta</th>
            <th style="width:6%;">İl</th>
            <th style="width:7%;">İlçe</th>
            <th style="width:14%;">Kurum</th>
            <th style="width:14%;">Statü</th>
        </tr>
    </thead>
    <tbody>
    <?php
    $bugun = new DateTimeImmutable();
    foreach ($uyeler as $idx => $u):
        $uyelik_gosterim = '';
        $gun_farki = null;
        $renk_cls  = 'renk-eski';

        if (!empty($u['uyelik_tarihi']) && $u['uyelik_tarihi'] !== '0000-00-00') {
            try {
                $uyelik_dt  = new DateTimeImmutable($u['uyelik_tarihi']);
                $gun_farki  = (int) $bugun->diff($uyelik_dt)->days;
                $uyelik_gosterim = $uyelik_dt->format('d.m.Y');
                if ($gun_farki <= 7)      $renk_cls = 'renk-yeni';
                elseif ($gun_farki <= 30) $renk_cls = 'renk-yakin';
            } catch (\Exception $e) {
                $uyelik_gosterim = $u['uyelik_tarihi'];
            }
        }

        $statu = trim($u['temsilci_turu'] ?: 'Üye');
        if (!empty($u['sorumlu_bolge'])) $statu .= ' (' . $u['sorumlu_bolge'] . ')';
        if (!empty($u['ek_gorev']))      $statu .= ' +' . $u['ek_gorev'];

        $cinsiyet_kslt = '';
        if ($u['cinsiyet'] === 'Erkek')  $cinsiyet_kslt = '<span class="cins-e">E</span>';
        elseif ($u['cinsiyet'] === 'Kadın') $cinsiyet_kslt = '<span class="cins-k">K</span>';
    ?>
    <tr>
        <td style="text-align:center;color:#888;"><?= $idx + 1; ?></td>
        <td style="font-weight:bold;"><?= htmlspecialchars($u['adi_soyadi'] ?: '-'); ?></td>
        <td style="text-align:center;">
            <?php if ($uyelik_gosterim !== ''): ?>
            <span class="yeni-badge <?= $renk_cls; ?>"><?= $uyelik_gosterim; ?></span>
            <?php else: ?>
            <span style="color:#aaa;">—</span>
            <?php endif; ?>
        </td>
        <td style="text-align:center;"><?= $cinsiyet_kslt ?: '<span style="color:#ccc;">—</span>'; ?></td>
        <td style="text-align:center;font-weight:bold;color:#b30000;"><?= htmlspecialchars($u['kan_grubu'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['eposta'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['ikamet_ili'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['trabzon_ilcesi'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($u['kurum'] ?: '-'); ?></td>
        <td><?= htmlspecialchars($statu); ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

</body>
</html>
