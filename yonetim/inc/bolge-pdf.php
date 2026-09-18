<?php
declare(strict_types=1);

/**
 * Bölge Bazlı PDF Raporu — Sadece Geliştirici Rolüne Açıktır.
 *
 * GET parametreleri:
 *   bolge : Bölge adı (Marmara Bölgesi, Ege Bölgesi vb.) veya 'Tümü'
 *   gizli : 1 = telefon/eposta gizli, 0 = açık
 */

require_once __DIR__ . '/../inc/baglan.php';
require_once __DIR__ . '/../inc/log-kayit.php';

// ─── YETKİ KONTROLÜ ──────────────────────────────────────────────────────────
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die('Yetkisiz erişim!');
}
$rol = $_SESSION['gercek_rol'] ?? $_SESSION['rol'] ?? '';
if ($rol !== 'gelistirici') {
    die('Bu sayfa sadece Geliştirici rolüne açıktır.');
}

// ─── BÖLGE HARİTASI ──────────────────────────────────────────────────────────
$bolge_harita = [
    'Marmara Bölgesi'           => ['İstanbul','Tekirdağ','Edirne','Kırklareli','Çanakkale','Balıkesir','Bursa','Yalova','Kocaeli','Sakarya','Düzce','Bolu','Bilecik'],
    'Ege Bölgesi'               => ['İzmir','Manisa','Aydın','Denizli','Muğla','Uşak','Afyonkarahisar','Kütahya'],
    'Akdeniz Bölgesi'           => ['Antalya','Isparta','Burdur','Mersin','Adana','Hatay','Osmaniye','Kahramanmaraş','Karaman'],
    'İç Anadolu Bölgesi'        => ['Ankara','Konya','Eskişehir','Kırıkkale','Kırşehir','Nevşehir','Aksaray','Niğde','Kayseri','Sivas','Yozgat','Çankırı'],
    'Karadeniz Bölgesi'         => ['Trabzon','Rize','Artvin','Giresun','Ordu','Samsun','Sinop','Kastamonu','Bartın','Zonguldak','Karabük','Amasya','Tokat','Gümüşhane','Bayburt'],
    'Doğu Anadolu Bölgesi'      => ['Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır','Muş','Bitlis','Van','Hakkari','Elazığ','Malatya','Bingöl','Tunceli'],
    'Güneydoğu Anadolu Bölgesi' => ['Gaziantep','Şanlıurfa','Diyarbakır','Mardin','Batman','Şırnak','Siirt','Adıyaman','Kilis'],
];

// ─── PARAMETRELER ─────────────────────────────────────────────────────────────
$f_bolge = trim($_GET['bolge'] ?? 'Tümü');
$f_gizli = (int)($_GET['gizli'] ?? 0) === 1;

// Bölge adı doğrulama
$gecerli_bolgeler = array_merge(['Tümü', 'Diğer'], array_keys($bolge_harita));
if (!in_array($f_bolge, $gecerli_bolgeler, true)) {
    die('Geçersiz bölge parametresi.');
}

// ─── VERİ SORGUSU ─────────────────────────────────────────────────────────────
try {
    $where  = ["onay_durumu = 'onayli'"];
    $params = [];

    if ($f_bolge === 'Tümü') {
        // Tüm bölgeler — il bazında gruplama yapılacak
    } elseif ($f_bolge === 'Diğer') {
        // Hiçbir bölgeye dahil olmayan iller
        $tum_iller = array_merge(...array_values($bolge_harita));
        $placeholders = implode(',', array_fill(0, count($tum_iller), '?'));
        $where[] = "ikamet_ili NOT IN ({$placeholders}) OR ikamet_ili IS NULL OR ikamet_ili = ''";
        $params  = array_merge($params, $tum_iller);
    } else {
        // Seçili bölgeye ait iller
        $iller = $bolge_harita[$f_bolge];
        $placeholders = implode(',', array_fill(0, count($iller), '?'));
        $where[] = "ikamet_ili IN ({$placeholders})";
        $params  = array_merge($params, $iller);
    }

    $sql = "SELECT id, adi_soyadi, telefon, eposta,
                   ikamet_ili, ikamet_ilcesi, trabzon_ilcesi, kurum,
                   gorev_unvan, temsilci_turu, ek_gorev, sorumlu_bolge, calisma_sekli
              FROM dernek_uyeler
             WHERE " . implode(" AND ", $where) . "
             ORDER BY ikamet_ili ASC, adi_soyadi ASC";

    $sorgu = $db_baglanti->prepare($sql);
    $sorgu->execute($params);
    $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);

    log_kaydet(
        $db_baglanti,
        'bolge_pdf_indir',
        'Bölge PDF indirildi: ' . $f_bolge . ' — ' . ($f_gizli ? 'Gizli' : 'Açık') . ' — ' . count($uyeler) . ' üye.'
    );

} catch (\PDOException $e) {
    error_log('Bölge PDF hata: ' . $e->getMessage());
    die('Veri aktarımı sırasında bir hata oluştu.');
}

// ─── İL'E GÖRE GRUPLAMA ──────────────────────────────────────────────────────
$il_gruplari = [];
foreach ($uyeler as $u) {
    $il = !empty($u['ikamet_ili']) ? $u['ikamet_ili'] : 'Belirtilmemiş';
    $il_gruplari[$il][] = $u;
}
ksort($il_gruplari);

// ─── BÖLGE RENKLERİ ──────────────────────────────────────────────────────────
$bolge_renk_harita = [
    'Marmara Bölgesi'           => '#3b82f6',
    'Ege Bölgesi'               => '#06b6d4',
    'Akdeniz Bölgesi'           => '#f97316',
    'İç Anadolu Bölgesi'        => '#f59e0b',
    'Karadeniz Bölgesi'         => '#10b981',
    'Doğu Anadolu Bölgesi'      => '#8b5cf6',
    'Güneydoğu Anadolu Bölgesi' => '#ef4444',
    'Tümü'                      => '#1a1a2e',
    'Diğer'                     => '#6b7280',
];
$baslik_renk = $bolge_renk_harita[$f_bolge] ?? '#1a1a2e';

?>
<!DOCTYPE html>
<html>
<head>
<meta charset="utf-8">
<title>T.K.Ç.D. — <?= htmlspecialchars($f_bolge); ?> PDF Raporu</title>
<style>
    @page { size: A4 landscape; margin: 10mm; }
    html, body {
        background: #fff; margin: 0; padding: 0;
        width: 1050px !important; height: auto !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }
    body { font-family: 'Helvetica Neue', Helvetica, Arial, sans-serif; font-size: 11px; color: #333; }

    .rapor-baslik {
        text-align: center; margin: 10px 0 15px; padding-bottom: 8px;
        border-bottom: 3px solid <?= $baslik_renk ?>; width: 1050px;
    }
    .rapor-baslik h2 { color: <?= $baslik_renk ?>; margin: 0; font-size: 19px; font-weight: bold; }
    .rapor-baslik p  { margin: 5px 0 0; font-size: 11px; color: #555; font-weight: bold; }

    .ozet-kutu {
        display: inline-block; margin: 0 6px 12px;
        padding: 5px 14px; border-radius: 20px;
        font-size: 10px; font-weight: bold;
    }
    .ozet-toplam  { background: #e8f5e9; color: #1b5e20; border: 1px solid #a5d6a7; }
    .ozet-bolge   { background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9; }
    .ozet-gizli   { background: #fff3e0; color: #e65100; border: 1px solid #ffcc80; }

    .il-baslik {
        background: <?= $baslik_renk ?>; color: #fff;
        padding: 5px 10px; font-size: 11px; font-weight: bold;
        margin-top: 12px; margin-bottom: 0;
        page-break-inside: avoid;
        width: 1050px; box-sizing: border-box;
    }

    table { border-collapse: collapse; width: 1050px !important; table-layout: fixed; page-break-inside: auto; }
    tr    { page-break-inside: avoid !important; }
    td, th { page-break-inside: avoid !important; word-wrap: break-word; }
    thead { display: table-header-group !important; }

    th {
        background: #1a1a2e !important; color: #fff !important;
        font-weight: bold; border: 1px solid #343a40;
        text-align: left; padding: 6px 5px;
        font-size: 10px; text-transform: uppercase;
    }
    td {
        border: 1px solid #dee2e6 !important;
        padding: 5px; vertical-align: middle; font-size: 10.5px;
    }
    tr:nth-child(even) td { background: #f9f9f9; }

    .gizli-alan { color: #aaa; font-style: italic; letter-spacing: 2px; }
    .statu-badge {
        display: inline-block; padding: 1px 6px; border-radius: 10px;
        font-size: 9px; font-weight: bold;
        background: #e3f2fd; color: #1565c0; border: 1px solid #90caf9;
    }

    @media print {
        html, body { width: 1050px !important; background: #fff; }
        table { width: 1050px !important; }
        th { background: #1a1a2e !important; color: #fff !important; }
        .il-baslik { background: <?= $baslik_renk ?> !important; color: #fff !important; }
    }
</style>
</head>
<body onload="window.print();">

<div class="rapor-baslik">
    <h2>T.K.Ç.D. — BÖLGE ÜYE RAPORU</h2>
    <p><?= htmlspecialchars($f_bolge) ?> &nbsp;|&nbsp; Tarih: <?= date('d.m.Y H:i') ?></p>
    <div style="margin-top:8px;">
        <span class="ozet-kutu ozet-toplam">Toplam: <?= count($uyeler) ?> üye</span>
        <span class="ozet-kutu ozet-bolge"><?= htmlspecialchars($f_bolge) ?></span>
        <?php if ($f_gizli): ?>
        <span class="ozet-kutu ozet-gizli">İletişim: Gizli</span>
        <?php else: ?>
        <span class="ozet-kutu ozet-toplam">İletişim: Açık</span>
        <?php endif; ?>
    </div>
</div>

<?php if (empty($uyeler)): ?>
<p style="text-align:center; color:#888; padding:40px 0;">Bu bölgede kayıtlı üye bulunamadı.</p>
<?php else: ?>

<?php
$sayac = 0;
foreach ($il_gruplari as $il => $il_uyeler):
?>

<div class="il-baslik">📍 <?= htmlspecialchars($il) ?> — <?= count($il_uyeler) ?> üye</div>
<table>
    <thead>
        <tr>
            <th style="width:3%;">#</th>
            <th style="width:15%;">Adı Soyadı</th>
            <?php if (!$f_gizli): ?>
            <th style="width:11%;">Telefon</th>
            <th style="width:14%;">E-Posta</th>
            <?php endif; ?>
            <th style="width:8%;">İlçe</th>
            <th style="width:14%;">Kurum</th>
            <th style="width:10%;">Görev/Unvan</th>
            <th style="width:14%;">Statü</th>
            <th style="width:8%;">Çalışma Şekli</th>
        </tr>
    </thead>
    <tbody>
    <?php foreach ($il_uyeler as $u):
        $sayac++;
        $statu = trim($u['temsilci_turu'] ?: '');
        if (!empty($u['sorumlu_bolge'])) $statu .= ' (' . $u['sorumlu_bolge'] . ')';
        if (!empty($u['ek_gorev']))      $statu .= ' | +' . $u['ek_gorev'];

        $ilce = $u['ikamet_ilcesi'] ?: ($u['trabzon_ilcesi'] ?: '-');
    ?>
    <tr>
        <td style="text-align:center; color:#888;"><?= $sayac ?></td>
        <td style="font-weight:bold;"><?= htmlspecialchars($u['adi_soyadi'] ?: '-') ?></td>
        <?php if (!$f_gizli): ?>
        <td><?= htmlspecialchars($u['telefon'] ?: '-') ?></td>
        <td><?= htmlspecialchars($u['eposta'] ?: '-') ?></td>
        <?php endif; ?>
        <td><?= htmlspecialchars($ilce) ?></td>
        <td><?= htmlspecialchars($u['kurum'] ?: '-') ?></td>
        <td><?= htmlspecialchars($u['gorev_unvan'] ?: '-') ?></td>
        <td>
            <?php if (!empty($statu)): ?>
            <span class="statu-badge"><?= htmlspecialchars($statu) ?></span>
            <?php else: ?>
            <span style="color:#aaa;">Üye</span>
            <?php endif; ?>
        </td>
        <td><?= htmlspecialchars($u['calisma_sekli'] ?: '-') ?></td>
    </tr>
    <?php endforeach; ?>
    </tbody>
</table>

<?php endforeach; ?>
<?php endif; ?>

</body>
</html>
