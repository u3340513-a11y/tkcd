<?php
declare(strict_types=1);

/**
 * Yaş Filtresi Sayfası — Sadece Geliştirici Rolüne Açıktır.
 */

if (!$is_gelistirici) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu sayfa sadece Geliştirici hesabına açıktır.</div></div>';
    return;
}

// ─── FİLTRE PARAMETRELERİ ────────────────────────────────────────────────
$f_yas_turu = in_array($_GET['yas_turu'] ?? '', ['altinda', 'ustunde', 'arasinda'], true)
    ? $_GET['yas_turu'] : 'altinda';

$f_yas1 = isset($_GET['yas1']) && ctype_digit((string)$_GET['yas1']) ? (int)$_GET['yas1'] : 35;
$f_yas2 = isset($_GET['yas2']) && ctype_digit((string)$_GET['yas2']) ? (int)$_GET['yas2'] : 45;
$f_yas1 = max(1, min(100, $f_yas1));
$f_yas2 = max(1, min(100, $f_yas2));

$f_il      = trim($_GET['il'] ?? '');
$f_statu   = trim($_GET['statu'] ?? '');
$f_calisma = trim($_GET['calisma'] ?? '');
$f_cinsiyet = trim($_GET['cinsiyet'] ?? '');
if (!in_array($f_cinsiyet, ['Erkek', 'Kadın', ''], true)) {
    $f_cinsiyet = '';
}
$filtre_gonderildi = isset($_GET['filtrele']);

$uyeler   = [];
$toplam   = 0;
$hata_msg = '';

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
if ($filtre_gonderildi) {
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
        if ($f_cinsiyet !== '') {
            $where[]  = "cinsiyet = ?";
            $params[] = $f_cinsiyet;
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
        $toplam = count($uyeler);

        log_kaydet($db_baglanti, 'yas_filtresi',
            'Yaş filtresi: ' . $f_yas_turu . ' ' . $f_yas1
            . ($f_yas_turu === 'arasinda' ? '-' . $f_yas2 : '')
            . ' yaş — ' . $toplam . ' sonuç.');

    } catch (\PDOException $e) {
        error_log('Yaş filtresi hatası: ' . $e->getMessage());
        $hata_msg = 'Sorgu sırasında bir hata oluştu: ' . $e->getMessage();
    }
}

// ─── DROPDOWN LİSTELERİ ──────────────────────────────────────────────────
$il_listesi = [];
try {
    $il_listesi = $db_baglanti->query(
        "SELECT DISTINCT ikamet_ili FROM dernek_uyeler
          WHERE onay_durumu='onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != ''
          ORDER BY ikamet_ili ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {}

$statu_listesi = ['Normal Üye','Kurum Temsilcisi','Yönetim Kurulu Üyesi',
    'Yönetim Kurulu Üyesi Yedek','İl Başkanı','İlçe Başkanı',
    'Bölge Koordinatörü','Teşkilatlanma Sorumlu Başkan'];

$pdf_params = http_build_query([
    'yas_turu'  => $f_yas_turu,
    'yas1'      => $f_yas1,
    'yas2'      => $f_yas2,
    'il'        => $f_il,
    'statu'     => $f_statu,
    'calisma'   => $f_calisma,
    'cinsiyet'  => $f_cinsiyet,
]);
$pdf_url = '/yonetim/inc/yas-filtresi-pdf.php?' . $pdf_params;
?>

<div class="container-fluid py-4 px-md-4">

    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:48px;height:48px;background:rgba(0,201,167,0.12);border:1px solid rgba(0,201,167,0.3);">
                    <i class="fa-solid fa-filter-circle-dollar" style="color:#00c9a7;font-size:1.3rem;"></i>
                </div>
                <div>
                    <h2 class="fw-bold text-dark mb-0">Yaş Filtresi</h2>
                    <p class="text-muted mb-0 small">Üyeleri yaş aralığına göre sorgula ve PDF olarak indir.</p>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtre Kartı -->
    <div class="card border-0 shadow-sm rounded-4 mb-4"
         style="border:1px solid rgba(0,201,167,0.2) !important;">
        <div class="card-body p-4">
            <form method="GET" action="index.php">
                <input type="hidden" name="sayfa" value="yas-filtresi">
                <input type="hidden" name="filtrele" value="1">
                <div class="row g-3 align-items-end">

                    <div class="col-12 col-md-3">
                        <label class="form-label fw-semibold small text-muted mb-1">Yaş Koşulu</label>
                        <select name="yas_turu" id="yas_turu" class="form-select" onchange="yasKosuluGuncelle()">
                            <option value="altinda"  <?= $f_yas_turu==='altinda'  ? 'selected' : ''; ?>>… yaşın altında</option>
                            <option value="ustunde"  <?= $f_yas_turu==='ustunde'  ? 'selected' : ''; ?>>… yaşın üstünde</option>
                            <option value="arasinda" <?= $f_yas_turu==='arasinda' ? 'selected' : ''; ?>>… ile … yaş arasında</option>
                        </select>
                    </div>

                    <div class="col-6 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1" id="yas1_label">Yaş Sınırı</label>
                        <div class="input-group">
                            <input type="number" name="yas1" id="yas1_input" class="form-control"
                                   min="1" max="100" value="<?= $f_yas1; ?>" required>
                            <span class="input-group-text text-muted small">yaş</span>
                        </div>
                    </div>

                    <div class="col-6 col-md-2" id="yas2_grup"
                         style="display:<?= $f_yas_turu==='arasinda' ? 'block' : 'none'; ?>;">
                        <label class="form-label fw-semibold small text-muted mb-1">Bitiş Yaşı</label>
                        <div class="input-group">
                            <input type="number" name="yas2" id="yas2_input" class="form-control"
                                   min="1" max="100" value="<?= $f_yas2; ?>">
                            <span class="input-group-text text-muted small">yaş</span>
                        </div>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">İl</label>
                        <select name="il" class="form-select">
                            <option value="">Tüm İller</option>
                            <?php foreach ($il_listesi as $il): ?>
                                <option value="<?= htmlspecialchars($il); ?>"
                                    <?= $f_il===$il ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($il); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">Statü</label>
                        <select name="statu" class="form-select">
                            <option value="">Tüm Statüler</option>
                            <?php foreach ($statu_listesi as $s): ?>
                                <option value="<?= htmlspecialchars($s); ?>"
                                    <?= $f_statu===$s ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">Cinsiyet</label>
                        <select name="cinsiyet" class="form-select">
                            <option value="">Tümü</option>
                            <option value="Erkek" <?= $f_cinsiyet==='Erkek' ? 'selected' : ''; ?>>Erkek</option>
                            <option value="Kadın" <?= $f_cinsiyet==='Kadın' ? 'selected' : ''; ?>>Kadın</option>
                        </select>
                    </div>

                    <div class="col-12 col-md-auto">
                        <button type="submit" class="btn btn-success fw-bold px-4 w-100">
                            <i class="fa-solid fa-magnifying-glass me-2"></i>Sorgula
                        </button>
                    </div>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hata_msg !== ''): ?>
    <div class="alert alert-danger rounded-3">
        <i class="fa-solid fa-triangle-exclamation me-2"></i><?= htmlspecialchars($hata_msg); ?>
    </div>
    <?php endif; ?>

    <?php if ($filtre_gonderildi): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 px-4 pt-4 pb-3"
             style="border-bottom:1px solid rgba(0,0,0,0.07) !important;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        Sorgu Sonuçları
                        <span class="badge ms-2 fw-bold"
                              style="background:rgba(0,201,167,0.15);color:#00a887;font-size:0.8rem;">
                            <?= $toplam; ?> üye
                        </span>
                    </h5>
                    <p class="text-muted small mb-0 mt-1">
                        <?php
                        if ($f_yas_turu === 'altinda')      echo $f_yas1 . ' yaş altı üyeler';
                        elseif ($f_yas_turu === 'ustunde')  echo $f_yas1 . ' yaş üstü üyeler';
                        else echo min($f_yas1,$f_yas2) . '–' . max($f_yas1,$f_yas2) . ' yaş arası üyeler';
                        if ($f_il !== '')     echo ' &mdash; ' . htmlspecialchars($f_il);
                        if ($f_statu !== '') echo ' &mdash; ' . htmlspecialchars($f_statu);
                        ?>
                    </p>
                </div>
                <?php if ($toplam > 0): ?>
                <a href="<?= htmlspecialchars($pdf_url); ?>" target="_blank"
                   class="btn btn-danger fw-bold px-4 shadow-sm">
                    <i class="fa-solid fa-file-pdf me-2"></i>PDF İndir
                </a>
                <?php endif; ?>
            </div>
        </div>

        <?php if ($toplam === 0): ?>
        <div class="card-body text-center py-5">
            <i class="fa-solid fa-users-slash fa-3x mb-3 d-block" style="color:rgba(0,0,0,0.1);"></i>
            <p class="text-muted mb-0">Bu kriterlere uygun üye bulunamadı.</p>
        </div>
        <?php else: ?>
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                    <thead style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;">
                        <tr>
                            <th class="px-4 py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">#</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Adı Soyadı</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Yaş</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Telefon</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">E-Posta</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">İl / İlçe</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Kurum</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Statü</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($uyeler as $i => $u):
                            $yas = (int)($u['hesap_yas'] ?? 0);
                            if ($yas < 25)      { $yrenk='#0d6efd'; $ybg='rgba(13,110,253,0.1)'; }
                            elseif ($yas < 35)  { $yrenk='#198754'; $ybg='rgba(25,135,84,0.1)'; }
                            elseif ($yas < 50)  { $yrenk='#e65100'; $ybg='rgba(230,81,0,0.1)'; }
                            else                { $yrenk='#6a1b9a'; $ybg='rgba(106,27,154,0.1)'; }
                            $statu_val = trim($u['temsilci_turu'] ?: '');
                            $ek_gorev  = trim($u['ek_gorev'] ?: '');
                        ?>
                        <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
                            <td class="px-4 text-muted" style="font-size:0.8rem;"><?= $i+1; ?></td>
                            <td>
                                <a href="index.php?sayfa=uye-detay&id=<?= $u['id']; ?>"
                                   class="fw-semibold text-decoration-none text-dark">
                                    <?= htmlspecialchars($u['adi_soyadi']); ?>
                                </a>
                            </td>
                            <td>
                                <span class="badge rounded-pill fw-bold px-3 py-2"
                                      style="background:<?= $ybg; ?>;color:<?= $yrenk; ?>;font-size:0.85rem;">
                                    <?= $yas > 0 ? $yas : '?'; ?>
                                </span>
                            </td>
                            <td><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
                            <td class="text-muted" style="font-size:0.82rem;"><?= htmlspecialchars($u['eposta'] ?: '-'); ?></td>
                            <td>
                                <div class="fw-semibold" style="font-size:0.82rem;"><?= htmlspecialchars($u['ikamet_ili'] ?: '-'); ?></div>
                                <?php if (!empty($u['trabzon_ilcesi'])): ?>
                                <div class="text-muted" style="font-size:0.75rem;"><?= htmlspecialchars($u['trabzon_ilcesi']); ?></div>
                                <?php endif; ?>
                            </td>
                            <td style="font-size:0.82rem;max-width:180px;" class="text-truncate">
                                <?= htmlspecialchars($u['kurum'] ?: '-'); ?>
                            </td>
                            <td>
                                <?php if ($statu_val && $statu_val !== 'Normal Üye'): ?>
                                <span class="badge bg-primary bg-opacity-10 text-primary fw-semibold" style="font-size:0.72rem;">
                                    <?= htmlspecialchars($statu_val); ?>
                                </span>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:0.8rem;">Üye</span>
                                <?php endif; ?>
                                <?php if ($ek_gorev): ?>
                                <div>
                                    <span class="badge bg-warning bg-opacity-15 text-warning fw-semibold mt-1" style="font-size:0.68rem;">
                                        +<?= htmlspecialchars($ek_gorev); ?>
                                    </span>
                                </div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <a href="index.php?sayfa=uye-detay&id=<?= $u['id']; ?>"
                                   class="btn btn-outline-secondary btn-sm px-2 py-1" style="font-size:0.75rem;">
                                    <i class="fa-solid fa-eye me-1"></i>Detay
                                </a>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
        <?php endif; ?>
    </div>
    <?php endif; ?>
</div>

<script>
function yasKosuluGuncelle() {
    var tur   = document.getElementById('yas_turu').value;
    var grup2 = document.getElementById('yas2_grup');
    var lbl1  = document.getElementById('yas1_label');
    if (tur === 'arasinda') {
        lbl1.textContent = 'Başlangıç Yaşı';
        grup2.style.display = 'block';
    } else {
        lbl1.textContent = 'Yaş Sınırı';
        grup2.style.display = 'none';
    }
}
yasKosuluGuncelle();
</script>
