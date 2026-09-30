<?php

declare(strict_types=1);

/**
 * Üyesi olmayan iller — Excel (XLS) dışa aktarımı.
 *
 * Mantık:
 *   Türkiye'nin 81 ilinin tamamından, dernek_uyeler tablosunda
 *   onaylı (onayli) üyesi bulunmayan illeri listeler ve Excel dosyası
 *   olarak indirir.
 *
 * Yetki: admin ve gelistirici
 */

require_once __DIR__ . '/../inc/baglan.php';
require_once __DIR__ . '/../inc/log-kayit.php';

// ─── YETKİ KONTROLÜ ────────────────────────────────────────────────────
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(403);
    die('Yetkisiz erişim!');
}

$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'gelistirici'], true)) {
    http_response_code(403);
    die('Bu rapor yalnızca tam yetkili hesaplara açıktır.');
}

// ─── TÜRKİYE 81 İL LİSTESİ ─────────────────────────────────────────────
$tumIller = [
    'Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara',
    'Antalya','Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman',
    'Bayburt','Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa',
    'Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne',
    'Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun',
    'Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul','İzmir',
    'Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri',
    'Kilis','Kırıkkale','Kırklareli','Kırşehir','Kocaeli','Konya',
    'Kütahya','Malatya','Manisa','Mardin','Mersin','Muğla','Muş',
    'Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun',
    'Siirt','Sinop','Sivas','Şanlıurfa','Şırnak','Tekirdağ','Tokat',
    'Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak',
];
sort($tumIller);

// ─── SİSTEMDE KAYITLI İLLERİ ÇEK ──────────────────────────────────────
try {
    $stmt = $db_baglanti->query(
        "SELECT DISTINCT ikamet_ili
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND ikamet_ili IS NOT NULL
            AND ikamet_ili != ''
          ORDER BY ikamet_ili ASC"
    );
    $kayitliIller = $stmt->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    error_log('bos-il-excel hata: ' . $e->getMessage());
    die('Veri okunurken bir hata oluştu.');
}

// ─── KAYDI OLMAYAN İLLERİ HESAPLA ───────────────────────────────────────
// Hem tam eşleşme hem de küçük harf toleransı (veri tutarsızlığı olabilir)
$kayitliNormalized = array_map('mb_strtolower', $kayitliIller);

$bosIller = [];
foreach ($tumIller as $il) {
    if (!in_array(mb_strtolower($il), $kayitliNormalized, true)) {
        $bosIller[] = $il;
    }
}

// ─── LOG ────────────────────────────────────────────────────────────────
log_kaydet(
    $db_baglanti,
    'excel_indir',
    sprintf(
        'Üyesi olmayan iller Excel\'i indirildi (%d il / %d il içinde kayıt yok)',
        count($bosIller),
        count($tumIller)
    ),
    'dernek_uyeler'
);

// ─── EXCEL ÇIKTISI ──────────────────────────────────────────────────────
$bugun    = date('Y-m-d');
$dosyaAdi = "Uyesi_Olmayan_Iller_{$bugun}.xls";

header('Content-Type: application/vnd.ms-excel; charset=UTF-8');
header("Content-Disposition: attachment; filename=\"{$dosyaAdi}\"");
header('Pragma: no-cache');
header('Expires: 0');

echo "\xEF\xBB\xBF"; // UTF-8 BOM
?>
<html>
<head>
<meta charset="utf-8">
<style>
    body  { font-family: Calibri, Arial, sans-serif; }
    table { border-collapse: collapse; width: 100%; }
    th    { background-color: #1a1a2e !important; color: #ffffff !important;
            font-weight: bold; border: 1px solid #343a40;
            text-align: left; padding: 10px; }
    td    { border: 1px solid #dee2e6 !important; padding: 8px;
            vertical-align: middle; }
    .ozet { background-color: #fff3cd !important; font-weight: bold; }
</style>
</head>
<body>

    <!-- Başlık / Meta bilgi -->
    <table border="0" style="margin-bottom:16px;">
        <tr>
            <td style="font-size:18px; font-weight:bold; color:#1a1a2e; border:none;">
                Trabzonlu Kamu Çalışanları Derneği
            </td>
        </tr>
        <tr>
            <td style="font-size:13px; color:#555; border:none;">
                Üyesi Olmayan İller Raporu — Oluşturulma: <?= date('d.m.Y H:i') ?>
            </td>
        </tr>
        <tr>
            <td style="border:none;">&nbsp;</td>
        </tr>
        <tr>
            <td class="ozet" style="border:1px solid #ffc107; padding:8px; border-radius:4px;">
                Toplam: <strong><?= count($tumIller) ?></strong> il &nbsp;|&nbsp;
                Üye kaydı olan: <strong><?= count($kayitliIller) ?></strong> il &nbsp;|&nbsp;
                <span style="color:#c0392b;">Üye kaydı olmayan: <strong><?= count($bosIller) ?></strong> il</span>
            </td>
        </tr>
    </table>

    <!-- Ana Tablo: Kayıtsız İller -->
    <table border="1">
        <thead>
            <tr>
                <th style="width:50px;">#</th>
                <th>İl Adı</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php if (empty($bosIller)): ?>
            <tr>
                <td colspan="3" style="text-align:center; color:#0f5132; font-weight:bold; background:#d1e7dd !important;">
                    Tüm illerde en az bir onaylı üye bulunmaktadır. 🎉
                </td>
            </tr>
            <?php else: ?>
            <?php foreach ($bosIller as $sira => $il): ?>
            <tr>
                <td style="text-align:center; color:#888;"><?= $sira + 1 ?></td>
                <td style="font-weight:600;"><?= htmlspecialchars($il) ?></td>
                <td style="color:#c0392b; font-weight:bold;">Üye kaydı yok</td>
            </tr>
            <?php endforeach; ?>
            <?php endif; ?>
        </tbody>
    </table>

    <!-- Referans: Kayıtlı İller -->
    <br>
    <table border="1" style="margin-top:16px;">
        <thead>
            <tr>
                <th colspan="3" style="background:#1a472a !important; color:#fff !important;">
                    Referans — Üyesi Olan İller (<?= count($kayitliIller) ?> il)
                </th>
            </tr>
            <tr>
                <th style="width:50px;">#</th>
                <th>İl Adı</th>
                <th>Durum</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($kayitliIller as $sira => $il): ?>
            <tr>
                <td style="text-align:center; color:#888;"><?= $sira + 1 ?></td>
                <td><?= htmlspecialchars($il) ?></td>
                <td style="color:#0f5132; font-weight:bold;">✓ Üye mevcut</td>
            </tr>
            <?php endforeach; ?>
        </tbody>
    </table>

</body>
</html>
