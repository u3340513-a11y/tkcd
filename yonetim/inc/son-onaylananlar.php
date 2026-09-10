<?php
declare(strict_types=1);

/**
 * Son Onaylananlar Filtresi — Yönetim ve Geliştirici Rolüne Açıktır.
 *
 * Onaylı üyeleri üyelik tarihine göre en yeniden en eskiye listeler.
 * Tarih aralığı, il, statü ve cinsiyet filtreleri desteklenir.
 */

if (!$is_yetki_var) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold">
          <i class="fa-solid fa-lock me-2"></i>Erişim Engellendi.</div></div>';
    return;
}

// ─── FİLTRE PARAMETRELERİ ────────────────────────────────────────────────
$f_baslangic = trim($_GET['baslangic'] ?? '');
$f_bitis     = trim($_GET['bitis'] ?? '');
$f_il        = trim($_GET['il'] ?? '');
$f_statu     = trim($_GET['statu'] ?? '');
$f_cinsiyet  = trim($_GET['cinsiyet'] ?? '');
$f_adet      = isset($_GET['adet']) && ctype_digit((string)$_GET['adet'])
    ? min((int)$_GET['adet'], 500) : 50;

// Tarih doğrulama
if ($f_baslangic !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_baslangic)) {
    $f_baslangic = '';
}
if ($f_bitis !== '' && !preg_match('/^\d{4}-\d{2}-\d{2}$/', $f_bitis)) {
    $f_bitis = '';
}
if (!in_array($f_cinsiyet, ['Erkek', 'Kadın', ''], true)) {
    $f_cinsiyet = '';
}

$filtre_gonderildi = isset($_GET['filtrele']);
$uyeler   = [];
$toplam   = 0;
$hata_msg = '';

// ─── VERİ SORGUSU ────────────────────────────────────────────────────────
if ($filtre_gonderildi) {
    try {
        $where  = ["onay_durumu = 'onayli'",
                   "uyelik_tarihi IS NOT NULL",
                   "uyelik_tarihi != '0000-00-00'",
                   "uyelik_tarihi != ''"];
        $params = [];

        if ($f_baslangic !== '') {
            $where[]  = "uyelik_tarihi >= ?";
            $params[] = $f_baslangic;
        }
        if ($f_bitis !== '') {
            $where[]  = "uyelik_tarihi <= ?";
            $params[] = $f_bitis;
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
        if ($f_cinsiyet !== '') {
            $where[]  = "cinsiyet = ?";
            $params[] = $f_cinsiyet;
        }

        $sql = "SELECT id, adi_soyadi, telefon, eposta, dogum_tarihi, kan_grubu,
                       ikamet_ili, trabzon_ilcesi, kurum, gorev_unvan, calisma_sekli,
                       cinsiyet, temsilci_turu, ek_gorev, sorumlu_bolge,
                       kayit_tarihi, uyelik_tarihi
                  FROM dernek_uyeler
                 WHERE " . implode(" AND ", $where) . "
                 ORDER BY uyelik_tarihi DESC, kayit_tarihi DESC
                 LIMIT ?";
        $params[] = $f_adet;

        $sorgu = $db_baglanti->prepare($sql);
        $sorgu->execute($params);
        $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
        $toplam = count($uyeler);

        log_kaydet($db_baglanti, 'son_onaylananlar',
            "Son onaylananlar sorgulandı: {$toplam} üye" .
            ($f_baslangic ? ", {$f_baslangic}" : '') .
            ($f_bitis ? " — {$f_bitis}" : ''));

    } catch (\PDOException $e) {
        error_log('Son onaylananlar hata: ' . $e->getMessage());
        $hata_msg = 'Sorgu sırasında bir hata oluştu.';
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

$adet_secenekleri = [25, 50, 100, 200, 500];

// PDF URL
$pdf_params = http_build_query([
    'baslangic' => $f_baslangic,
    'bitis'     => $f_bitis,
    'il'        => $f_il,
    'statu'     => $f_statu,
    'cinsiyet'  => $f_cinsiyet,
    'adet'      => $f_adet,
]);
$pdf_url = '/yonetim/inc/son-onaylananlar-pdf.php?' . $pdf_params;
?>

<div class="container-fluid py-4 px-md-4">

    <!-- Başlık -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-3 d-flex align-items-center justify-content-center"
             style="width:48px;height:48px;background:rgba(25,135,84,0.1);border:1px solid rgba(25,135,84,0.3);">
            <i class="fa-solid fa-user-check" style="color:#198754;font-size:1.3rem;"></i>
        </div>
        <div>
            <h2 class="fw-bold text-dark mb-0">Son Onaylananlar</h2>
            <p class="text-muted mb-0 small">Üyelik tarihi en yeni olandan başlayarak listele — PDF çıktı alabilirsiniz.</p>
        </div>
    </div>

    <!-- Filtre Kartı -->
    <div class="card border-0 shadow-sm rounded-4 mb-4"
         style="border:1px solid rgba(25,135,84,0.2) !important;">
        <div class="card-body p-4">
            <form method="GET" action="index.php">
                <input type="hidden" name="sayfa" value="son-onaylananlar">
                <input type="hidden" name="filtrele" value="1">
                <div class="row g-3 align-items-end">

                    <!-- Başlangıç Tarihi -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            <i class="fa-solid fa-calendar-day me-1"></i>Başlangıç
                        </label>
                        <input type="date" name="baslangic" class="form-control"
                               value="<?= htmlspecialchars($f_baslangic); ?>">
                    </div>

                    <!-- Bitiş Tarihi -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">
                            <i class="fa-solid fa-calendar-check me-1"></i>Bitiş
                        </label>
                        <input type="date" name="bitis" class="form-control"
                               value="<?= htmlspecialchars($f_bitis); ?>">
                    </div>

                    <!-- İl -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">İl</label>
                        <select name="il" class="form-select">
                            <option value="">Tüm İller</option>
                            <?php foreach ($il_listesi as $il): ?>
                                <option value="<?= htmlspecialchars($il); ?>"
                                    <?= $f_il === $il ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($il); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Statü -->
                    <div class="col-12 col-md-2">
                        <label class="form-label fw-semibold small text-muted mb-1">Statü</label>
                        <select name="statu" class="form-select">
                            <option value="">Tüm Statüler</option>
                            <?php foreach ($statu_listesi as $s): ?>
                                <option value="<?= htmlspecialchars($s); ?>"
                                    <?= $f_statu === $s ? 'selected' : ''; ?>>
                                    <?= htmlspecialchars($s); ?>
                                </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Cinsiyet -->
                    <div class="col-6 col-md-1">
                        <label class="form-label fw-semibold small text-muted mb-1">Cinsiyet</label>
                        <select name="cinsiyet" class="form-select">
                            <option value="">Tümü</option>
                            <option value="Erkek" <?= $f_cinsiyet === 'Erkek' ? 'selected' : ''; ?>>Erkek</option>
                            <option value="Kadın" <?= $f_cinsiyet === 'Kadın' ? 'selected' : ''; ?>>Kadın</option>
                        </select>
                    </div>

                    <!-- Kayıt Adedi -->
                    <div class="col-6 col-md-1">
                        <label class="form-label fw-semibold small text-muted mb-1">Göster</label>
                        <select name="adet" class="form-select">
                            <?php foreach ($adet_secenekleri as $a): ?>
                                <option value="<?= $a; ?>" <?= $f_adet === $a ? 'selected' : ''; ?>><?= $a; ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <!-- Sorgula -->
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

    <!-- Sonuç -->
    <?php if ($filtre_gonderildi): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 px-4 pt-4 pb-3"
             style="border-bottom:1px solid rgba(0,0,0,0.07) !important;">
            <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                <div>
                    <h5 class="fw-bold mb-0 text-dark">
                        Son Onaylananlar
                        <span class="badge ms-2 fw-bold"
                              style="background:rgba(25,135,84,0.12);color:#198754;font-size:0.8rem;">
                            <?= $toplam; ?> üye
                        </span>
                    </h5>
                    <p class="text-muted small mb-0 mt-1">
                        <?php
                        $aciklama = [];
                        if ($f_baslangic !== '' || $f_bitis !== '') {
                            $aciklama[] = ($f_baslangic ?: '—') . ' ile ' . ($f_bitis ?: 'bugün') . ' arası';
                        }
                        if ($f_il !== '')      $aciklama[] = htmlspecialchars($f_il);
                        if ($f_statu !== '')   $aciklama[] = htmlspecialchars($f_statu);
                        if ($f_cinsiyet !== '') $aciklama[] = htmlspecialchars($f_cinsiyet);
                        echo implode(' &mdash; ', $aciklama) ?: 'Tüm onaylı üyeler, en son onaylananlar üstte';
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
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Üyelik Tarihi</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Telefon</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">İl / İlçe</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Kurum</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Statü</th>
                            <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Cinsiyet</th>
                            <th class="py-3"></th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php
                        $bugun = new DateTimeImmutable();
                        foreach ($uyeler as $i => $u):
                            // Üyelik tarihinden kaç gün önce onaylandı
                            $gun_farki = null;
                            if (!empty($u['uyelik_tarihi']) && $u['uyelik_tarihi'] !== '0000-00-00') {
                                try {
                                    $uyelik_dt  = new DateTimeImmutable($u['uyelik_tarihi']);
                                    $gun_farki  = (int) $bugun->diff($uyelik_dt)->days;
                                    $fark_metin = $gun_farki === 0 ? 'Bugün' :
                                        ($gun_farki === 1 ? '1 gün önce' : $gun_farki . ' gün önce');
                                    // Renk: 0-7 gün = yeşil, 8-30 = mavi, 31+ = gri
                                    if ($gun_farki <= 7)       { $fark_renk = '#198754'; $fark_bg = 'rgba(25,135,84,0.1)'; }
                                    elseif ($gun_farki <= 30)  { $fark_renk = '#0d6efd'; $fark_bg = 'rgba(13,110,253,0.1)'; }
                                    else                       { $fark_renk = '#6c757d'; $fark_bg = 'rgba(108,117,125,0.1)'; }
                                } catch (\Exception $e) {
                                    $fark_metin = ''; $fark_renk = '#6c757d'; $fark_bg = 'transparent';
                                }
                            } else {
                                $fark_metin = ''; $fark_renk = '#6c757d'; $fark_bg = 'transparent';
                            }
                            $uyelik_gosterim = (!empty($u['uyelik_tarihi']) && $u['uyelik_tarihi'] !== '0000-00-00')
                                ? date('d.m.Y', strtotime($u['uyelik_tarihi'])) : '—';
                            $statu_val = trim($u['temsilci_turu'] ?: '');
                            $ek_gorev  = trim($u['ek_gorev'] ?: '');
                            $cinsiyet_val = $u['cinsiyet'] ?? '';
                        ?>
                        <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
                            <td class="px-4 text-muted" style="font-size:0.8rem;"><?= $i + 1; ?></td>
                            <td>
                                <a href="index.php?sayfa=uye-detay&id=<?= $u['id']; ?>"
                                   class="fw-semibold text-decoration-none text-dark">
                                    <?= htmlspecialchars($u['adi_soyadi']); ?>
                                </a>
                            </td>
                            <td>
                                <div class="fw-semibold" style="font-size:0.83rem;"><?= $uyelik_gosterim; ?></div>
                                <?php if ($fark_metin !== ''): ?>
                                <span class="badge rounded-pill" style="background:<?= $fark_bg; ?>;color:<?= $fark_renk; ?>;font-size:0.72rem;">
                                    <?= $fark_metin; ?>
                                </span>
                                <?php endif; ?>
                            </td>
                            <td><?= htmlspecialchars($u['telefon'] ?: '-'); ?></td>
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
                                <?php if ($cinsiyet_val === 'Erkek'): ?>
                                <span class="badge" style="background:rgba(13,110,253,0.12);color:#0d6efd;font-size:0.75rem;">
                                    <i class="fa-solid fa-mars me-1"></i>E
                                </span>
                                <?php elseif ($cinsiyet_val === 'Kadın'): ?>
                                <span class="badge" style="background:rgba(214,51,132,0.12);color:#d63384;font-size:0.75rem;">
                                    <i class="fa-solid fa-venus me-1"></i>K
                                </span>
                                <?php else: ?>
                                <span class="text-muted" style="font-size:0.8rem;">—</span>
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
