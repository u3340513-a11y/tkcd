<?php
/**
 * İletişim Mesajları — Ana sitedeki /iletisim formundan gelen mesajlar.
 * Yalnızca geliştirici rolüne açık; yetkisiz erişim index.php'de engellenir.
 */

// ── Okundu işaretle (AJAX/POST) ───────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['okundu_id'])) {
    $oid = (int) $_POST['okundu_id'];
    if ($oid > 0) {
        try {
            $st = $db_baglanti->prepare(
                'UPDATE iletisim_mesajlari SET okundu = 1 WHERE id = :id'
            );
            $st->execute([':id' => $oid]);
        } catch (\PDOException $e) {
            error_log('iletisim okundu güncelleme hatası: ' . $e->getMessage());
        }
    }
    header('Content-Type: application/json');
    echo json_encode(['ok' => true]);
    exit;
}

// ── Mesaj silme ───────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sil_id'])) {
    $sid = (int) $_POST['sil_id'];
    if ($sid > 0) {
        try {
            $st = $db_baglanti->prepare('DELETE FROM iletisim_mesajlari WHERE id = :id');
            $st->execute([':id' => $sid]);
        } catch (\PDOException $e) {
            error_log('iletisim silme hatası: ' . $e->getMessage());
        }
    }
    header('Location: index.php?sayfa=iletisim-mesajlari&bilgi=silindi');
    exit;
}

// ── Sayfalama ─────────────────────────────────────────────────────────────
$limit   = 25;
$sayfa_n = max(1, (int) ($_GET['p'] ?? 1));
$offset  = ($sayfa_n - 1) * $limit;

$mesajlar   = [];
$toplam_m   = 0;
$okunmamis  = 0;

try {
    $toplam_m  = (int) $db_baglanti
        ->query('SELECT COUNT(*) FROM iletisim_mesajlari')
        ->fetchColumn();
    $okunmamis = (int) $db_baglanti
        ->query('SELECT COUNT(*) FROM iletisim_mesajlari WHERE okundu = 0')
        ->fetchColumn();

    $st = $db_baglanti->prepare(
        'SELECT id, ad, eposta, konu, mesaj, mail_durum, okundu, ip_adresi, olusturuldu
           FROM iletisim_mesajlari
          ORDER BY olusturuldu DESC
          LIMIT :limit OFFSET :offset'
    );
    $st->bindValue(':limit',  $limit,  PDO::PARAM_INT);
    $st->bindValue(':offset', $offset, PDO::PARAM_INT);
    $st->execute();
    $mesajlar = $st->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('iletisim_mesajlari sorgu hatası: ' . $e->getMessage());
}

$toplam_sayfa = $toplam_m > 0 ? (int) ceil($toplam_m / $limit) : 1;
?>

<div class="container-fluid py-4 px-md-4">

    <!-- ─── BAŞLIK ──────────────────────────────────────────────────────── -->
    <div class="d-flex align-items-center justify-content-between mb-4 flex-wrap gap-3">
        <div class="d-flex align-items-center gap-3">
            <div class="rounded-3 d-flex align-items-center justify-content-center"
                 style="width:52px;height:52px;background:linear-gradient(135deg,#c62828,#8b0000);">
                <i class="fa-solid fa-envelope-open-text text-white fa-lg"></i>
            </div>
            <div>
                <h1 class="fw-bold text-dark mb-0" style="font-size:1.5rem;">İletişim Mesajları</h1>
                <p class="text-muted mb-0 small">Ana siteden gelen form mesajları</p>
            </div>
        </div>
        <div class="d-flex gap-2 align-items-center">
            <?php if ($okunmamis > 0): ?>
            <span class="badge rounded-pill" style="background:#ef4444;font-size:.82rem;padding:6px 14px;">
                <i class="fa-solid fa-circle-exclamation me-1"></i><?= $okunmamis ?> Okunmamış
            </span>
            <?php endif; ?>
            <span class="badge rounded-pill" style="background:#e2e8f0;color:#475569;font-size:.82rem;padding:6px 14px;">
                Toplam <?= number_format($toplam_m) ?> Mesaj
            </span>
        </div>
    </div>

    <?php if (isset($_GET['bilgi']) && $_GET['bilgi'] === 'silindi'): ?>
    <div class="alert alert-success alert-dismissible fade show mb-4" role="alert">
        <i class="fa-solid fa-check-circle me-2"></i>Mesaj başarıyla silindi.
        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
    </div>
    <?php endif; ?>

    <?php if (empty($mesajlar)): ?>
    <!-- Boş durum -->
    <div class="text-center py-5" style="color:#94a3b8;">
        <i class="fa-solid fa-inbox fa-3x mb-3"></i>
        <p class="fs-5 mb-0">Henüz hiç mesaj yok.</p>
        <p class="small">Ana sitedeki iletişim formu üzerinden gelen mesajlar burada görünür.</p>
    </div>
    <?php else: ?>

    <!-- ─── MESAJ KARTLARI ────────────────────────────────────────────────── -->
    <div class="d-flex flex-column gap-3 mb-4" id="mesaj-listesi">
        <?php foreach ($mesajlar as $m): ?>
        <?php
            $okundu    = (int) $m['okundu'];
            $tarihStr  = date('d.m.Y H:i', strtotime($m['olusturuldu']));
            $mailOk    = $m['mail_durum'] === 'gonderildi';
            $cardBg    = $okundu ? '#ffffff' : '#fffbf0';
            $borderClr = $okundu ? '#e2e8f0' : '#f59e0b';
        ?>
        <div class="card shadow-sm iletisim-kart"
             id="kart-<?= (int) $m['id'] ?>"
             style="border:1px solid <?= $borderClr ?>;background:<?= $cardBg ?>;border-radius:12px;transition:all .2s;">
            <div class="card-body p-4">

                <!-- Üst satır: kimlik + tarih + rozetler -->
                <div class="d-flex align-items-start justify-content-between flex-wrap gap-2 mb-3">
                    <div class="d-flex align-items-center gap-3">
                        <!-- Avatar -->
                        <div class="rounded-circle d-flex align-items-center justify-content-center flex-shrink-0"
                             style="width:42px;height:42px;background:linear-gradient(135deg,#c62828,#8b0000);color:#fff;font-weight:700;font-size:.95rem;">
                            <?= mb_strtoupper(mb_substr($m['ad'], 0, 1)) ?>
                        </div>
                        <div>
                            <div class="fw-bold" style="font-size:.95rem;color:#1e293b;">
                                <?= htmlspecialchars($m['ad']) ?>
                            </div>
                            <a href="mailto:<?= htmlspecialchars($m['eposta']) ?>"
                               class="text-muted small text-decoration-none">
                                <?= htmlspecialchars($m['eposta']) ?>
                            </a>
                        </div>
                    </div>
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <?php if (!$okundu): ?>
                        <span class="badge" style="background:#fef3c7;color:#92400e;font-size:.72rem;padding:4px 10px;border-radius:20px;">
                            <i class="fa-solid fa-circle-dot me-1" style="font-size:.55rem;"></i>Okunmadı
                        </span>
                        <?php endif; ?>
                        <span class="badge"
                              style="background:<?= $mailOk ? '#dcfce7' : '#fee2e2' ?>;
                                     color:<?= $mailOk ? '#15803d' : '#b91c1c' ?>;
                                     font-size:.72rem;padding:4px 10px;border-radius:20px;">
                            <i class="fa-solid <?= $mailOk ? 'fa-circle-check' : 'fa-circle-xmark' ?> me-1"></i>
                            <?= $mailOk ? 'Mail Gönderildi' : 'Mail Hatası' ?>
                        </span>
                        <span class="text-muted" style="font-size:.78rem;">
                            <i class="fa-regular fa-clock me-1"></i><?= $tarihStr ?>
                        </span>
                    </div>
                </div>

                <!-- Konu -->
                <div class="mb-2">
                    <span class="badge" style="background:#f1f5f9;color:#475569;font-size:.78rem;padding:4px 10px;border-radius:6px;">
                        <i class="fa-solid fa-tag me-1"></i><?= htmlspecialchars($m['konu']) ?>
                    </span>
                </div>

                <!-- Mesaj metni -->
                <div class="p-3 rounded-3" style="background:#f8fafc;border-left:4px solid #c62828;font-size:.88rem;color:#334155;white-space:pre-wrap;line-height:1.6;">
                    <?= htmlspecialchars($m['mesaj']) ?>
                </div>

                <!-- Alt satır: IP + aksiyonlar -->
                <div class="d-flex align-items-center justify-content-between mt-3 flex-wrap gap-2">
                    <span class="text-muted" style="font-size:.72rem;">
                        <i class="fa-solid fa-location-dot me-1"></i>IP: <?= htmlspecialchars($m['ip_adresi'] ?? '—') ?>
                    </span>
                    <div class="d-flex gap-2">
                        <?php if (!$okundu): ?>
                        <button type="button"
                                class="btn btn-sm btn-outline-success okundu-btn"
                                data-id="<?= (int) $m['id'] ?>"
                                style="font-size:.78rem;border-radius:8px;">
                            <i class="fa-solid fa-check me-1"></i>Okundu İşaretle
                        </button>
                        <?php endif; ?>
                        <a href="mailto:<?= htmlspecialchars($m['eposta']) ?>?subject=Re: <?= rawurlencode($m['konu']) ?>"
                           class="btn btn-sm btn-outline-primary"
                           style="font-size:.78rem;border-radius:8px;">
                            <i class="fa-solid fa-reply me-1"></i>Yanıtla
                        </a>
                        <form method="POST" action="index.php?sayfa=iletisim-mesajlari"
                              onsubmit="return confirm('Bu mesajı kalıcı olarak silmek istediğinizden emin misiniz?');"
                              class="d-inline">
                            <input type="hidden" name="sil_id" value="<?= (int) $m['id'] ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"
                                    style="font-size:.78rem;border-radius:8px;">
                                <i class="fa-solid fa-trash me-1"></i>Sil
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- ─── SAYFALAMA ───────────────────────────────────────────────────── -->
    <?php if ($toplam_sayfa > 1): ?>
    <nav class="d-flex justify-content-center mt-4">
        <ul class="pagination pagination-sm gap-1">
            <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
            <li class="page-item <?= $i === $sayfa_n ? 'active' : '' ?>">
                <a class="page-link rounded-3"
                   href="index.php?sayfa=iletisim-mesajlari&p=<?= $i ?>"
                   style="<?= $i === $sayfa_n ? 'background:#c62828;border-color:#c62828;' : '' ?>">
                    <?= $i ?>
                </a>
            </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>

    <?php endif; ?>
</div>

<!-- ─── Okundu İşaretle — AJAX ──────────────────────────────────────────── -->
<script>
document.querySelectorAll('.okundu-btn').forEach(function(btn) {
    btn.addEventListener('click', function() {
        const id   = this.dataset.id;
        const kart = document.getElementById('kart-' + id);

        fetch('index.php?sayfa=iletisim-mesajlari', {
            method: 'POST',
            headers: {'Content-Type': 'application/x-www-form-urlencoded'},
            body: 'okundu_id=' + encodeURIComponent(id)
        })
        .then(r => r.json())
        .then(function(data) {
            if (data.ok && kart) {
                kart.style.background   = '#ffffff';
                kart.style.borderColor  = '#e2e8f0';
                btn.remove();
                const badge = kart.querySelector('.badge[style*="fef3c7"]');
                if (badge) badge.remove();
            }
        });
    });
});
</script>
