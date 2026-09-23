<?php
/**
 * Duyurular CRUD — Sadece Geliştirici rolüne açık.
 *
 * İşlemler:
 *   - Listeleme (varsayılan)
 *   - Yeni duyuru ekleme
 *   - Silme
 *   - Aktif/Pasif toggle
 *
 * @var PDO  $db_baglanti
 * @var bool $is_gelistirici
 */

if (!$is_gelistirici) {
    echo '<div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi.</div>';
    return;
}

$mesaj = '';
$mesaj_tur = 'success';

// ── Yeni Duyuru Ekle ─────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['duyuru_ekle'])) {
    // CSRF kontrol
    if (!isset($_POST['csrf_token']) || !hash_equals(csrf_token_al(), $_POST['csrf_token'])) {
        $mesaj = 'Güvenlik doğrulaması başarısız.';
        $mesaj_tur = 'danger';
    } else {
        $baslik = trim($_POST['baslik'] ?? '');
        $icerik = trim($_POST['icerik'] ?? '');

        if ($baslik === '') {
            $mesaj = 'Duyuru başlığı boş bırakılamaz.';
            $mesaj_tur = 'warning';
        } else {
            try {
                $ekle = $db_baglanti->prepare(
                    "INSERT INTO duyurular (baslik, icerik, olusturan, aktif) VALUES (?, ?, ?, 1)"
                );
                $ekle->execute([$baslik, $icerik, $_SESSION['kullanici_adi'] ?? '']);
                $mesaj = 'Duyuru başarıyla eklendi.';
                log_kaydet($db_baglanti, 'duyuru_ekle', 'Yeni duyuru: ' . mb_strimwidth($baslik, 0, 50, '…'));
            } catch (\PDOException $e) {
                error_log('Duyuru ekleme hatası: ' . $e->getMessage());
                $mesaj = 'Duyuru eklenirken bir hata oluştu.';
                $mesaj_tur = 'danger';
            }
        }
    }
}

// ── Sil ──────────────────────────────────────────────────────────────
if (isset($_GET['sil']) && is_numeric($_GET['sil'])) {
    try {
        $sil = $db_baglanti->prepare("DELETE FROM duyurular WHERE id = ?");
        $sil->execute([(int) $_GET['sil']]);
        $mesaj = 'Duyuru silindi.';
        log_kaydet($db_baglanti, 'duyuru_sil', 'Duyuru #' . (int) $_GET['sil'] . ' silindi.');
    } catch (\PDOException $e) {
        $mesaj = 'Silme hatası.';
        $mesaj_tur = 'danger';
    }
}

// ── Aktif/Pasif Toggle ───────────────────────────────────────────────
if (isset($_GET['toggle']) && is_numeric($_GET['toggle'])) {
    try {
        $db_baglanti->prepare("UPDATE duyurular SET aktif = NOT aktif WHERE id = ?")->execute([(int) $_GET['toggle']]);
        $mesaj = 'Duyuru durumu güncellendi.';
    } catch (\PDOException $e) {
        $mesaj = 'Güncelleme hatası.';
        $mesaj_tur = 'danger';
    }
}

// ── Listeleme ────────────────────────────────────────────────────────
$duyuru_listesi = [];
try {
    $duyuru_listesi = $db_baglanti->query("SELECT id, baslik, icerik, aktif, tarih, olusturan FROM duyurular ORDER BY tarih DESC LIMIT 50")->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $duyuru_listesi = [];
}
?>

<div class="container-fluid py-3">

    <div class="d-flex align-items-center justify-content-between mb-4">
        <h4 class="fw-bold mb-0"><i class="fa-solid fa-bullhorn me-2 text-info"></i>Duyuru Yönetimi</h4>
    </div>

    <?php if ($mesaj): ?>
    <div class="alert alert-<?= $mesaj_tur ?> alert-dismissible fade show" role="alert">
        <?= htmlspecialchars($mesaj) ?>
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <!-- Yeni Duyuru Formu -->
    <div class="dash-card mb-4">
        <div class="dash-card__header">
            <h5 class="dash-card__title"><i class="fa-solid fa-plus text-success"></i> Yeni Duyuru Ekle</h5>
        </div>
        <div class="dash-card__body">
            <form method="POST" action="index.php?sayfa=duyurular">
                <?= csrf_token_html() ?>
                <input type="hidden" name="duyuru_ekle" value="1">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">Başlık</label>
                        <input type="text" name="baslik" class="form-control" required maxlength="255" placeholder="Duyuru başlığı...">
                    </div>
                    <div class="col-md-6">
                        <label class="form-label fw-semibold small">İçerik (opsiyonel)</label>
                        <input type="text" name="icerik" class="form-control" maxlength="1000" placeholder="Kısa açıklama...">
                    </div>
                </div>
                <button type="submit" class="btn btn-primary mt-3 px-4">
                    <i class="fa-solid fa-paper-plane me-1"></i> Yayınla
                </button>
            </form>
        </div>
    </div>

    <!-- Duyuru Listesi -->
    <div class="dash-card">
        <div class="dash-card__header">
            <h5 class="dash-card__title"><i class="fa-solid fa-list text-secondary"></i> Mevcut Duyurular</h5>
            <span class="badge bg-secondary"><?= count($duyuru_listesi) ?></span>
        </div>
        <div class="dash-card__body p-0">
            <?php if (empty($duyuru_listesi)): ?>
            <div class="text-center text-muted py-5">
                <i class="fa-solid fa-bullhorn fa-3x mb-3 d-block" style="opacity:0.15;"></i>
                <p>Henüz duyuru eklenmemiş.</p>
            </div>
            <?php else: ?>
            <div class="table-responsive">
                <table class="table table-hover mb-0 align-middle">
                    <thead class="table-light">
                        <tr>
                            <th style="width:40px;">#</th>
                            <th>Başlık</th>
                            <th style="width:120px;">Durum</th>
                            <th style="width:140px;">Tarih</th>
                            <th style="width:120px;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php foreach ($duyuru_listesi as $d): ?>
                        <tr>
                            <td class="text-muted small"><?= $d['id'] ?></td>
                            <td>
                                <div class="fw-semibold"><?= htmlspecialchars($d['baslik']) ?></div>
                                <?php if (!empty($d['icerik'])): ?>
                                <div class="text-muted small"><?= htmlspecialchars(mb_strimwidth($d['icerik'], 0, 80, '…')) ?></div>
                                <?php endif; ?>
                            </td>
                            <td>
                                <?php if ($d['aktif']): ?>
                                <span class="badge bg-success bg-opacity-10 text-success">Aktif</span>
                                <?php else: ?>
                                <span class="badge bg-secondary bg-opacity-10 text-secondary">Pasif</span>
                                <?php endif; ?>
                            </td>
                            <td class="small text-muted"><?= date('d.m.Y H:i', strtotime($d['tarih'])) ?></td>
                            <td>
                                <a href="index.php?sayfa=duyurular&toggle=<?= $d['id'] ?>" class="btn btn-sm btn-outline-secondary me-1" title="Aktif/Pasif">
                                    <i class="fa-solid fa-toggle-<?= $d['aktif'] ? 'on' : 'off' ?>"></i>
                                </a>
                                <a href="index.php?sayfa=duyurular&sil=<?= $d['id'] ?>" class="btn btn-sm btn-outline-danger"
                                   onclick="return confirm('Bu duyuruyu silmek istediğinize emin misiniz?')" title="Sil">
                                    <i class="fa-solid fa-trash"></i>
                                </a>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>
