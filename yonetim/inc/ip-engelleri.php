<?php

declare(strict_types=1);

/**
 * Engelli IP Adresleri sayfası.
 *
 * Sadece `gelistirici` rolü erişebilir. Giriş denemeleri nedeniyle engellenen
 * IP'leri, denenen kullanıcı adı ve şifreyle birlikte listeler; engeli kaldırma
 * işlemi index.php'de (HTML çıktısından önce, CSRF korumalı POST) yapılır.
 *
 * @var PDO            $db_baglanti
 * @var IpEngelServisi $ipEngel
 */

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die('Yetkisiz erişim!');
}
if (($_SESSION['rol'] ?? '') !== 'gelistirici') {
    die('Erişim Engellendi: Bu sayfa sadece geliştirici hesabına açıktır.');
}

const IP_ENGEL_SAYFA_BOYUTU = 50;

$ipSayfaNo   = max(1, (int) ($_GET['ip_sayfa'] ?? 1));
$toplamEngel = 0;
$engelliler  = [];
try {
    $toplamEngel = $ipEngel->toplamEngelSayisi();
    $engelliler  = $ipEngel->listele(IP_ENGEL_SAYFA_BOYUTU, ($ipSayfaNo - 1) * IP_ENGEL_SAYFA_BOYUTU);
} catch (PDOException $e) {
    error_log('[IP_ENGEL] Liste hatası: ' . $e->getMessage());
}
$toplamSayfa = max(1, (int) ceil($toplamEngel / IP_ENGEL_SAYFA_BOYUTU));

$sebepEtiketleri = [
    IpEngelServisi::SEBEP_BILINMEYEN_KULLANICI => ['Kayıtsız kullanıcı adı', 'bg-danger'],
    IpEngelServisi::SEBEP_YAKIN_KULLANICI      => ['Yakın kullanıcı adı (2. deneme)', 'bg-warning text-dark'],
];

$bildirim = ($_GET['mesaj'] ?? '') === 'kaldirildi'
    ? ['success', 'IP engeli kaldırıldı.']
    : (($_GET['mesaj'] ?? '') === 'hata' ? ['danger', 'İşlem gerçekleştirilemedi.'] : null);
?>

<div class="container-fluid py-4 px-md-4">
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-ban me-2" style="color:#e05555;"></i>Engelli IP Adresleri</h2>
            <p class="text-muted mb-0 small">Kayıtsız veya yakın kullanıcı adıyla giriş deneyen IP adresleri.</p>
        </div>
        <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#0f3460;color:rgba(255,255,255,0.8);font-size:0.8rem;">
            <i class="fa-solid fa-shield-halved me-1"></i><?= number_format($toplamEngel); ?> Engelli IP
        </span>
    </div>

    <?php if ($bildirim !== null): ?>
        <div class="alert alert-<?= $bildirim[0]; ?> py-2" role="status"><?= htmlspecialchars($bildirim[1], ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3">IP Adresi</th>
                            <th>Denenen Kullanıcı Adı</th>
                            <th style="color:#ffd700;"><i class="fa-solid fa-key me-1"></i>Denenen Şifre</th>
                            <th>Sebep</th>
                            <th>Tarih</th>
                            <th>Tarayıcı</th>
                            <th class="text-center pe-3">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                    <?php if ($engelliler === []): ?>
                        <tr>
                            <td colspan="7" class="text-center py-5 text-muted">
                                <i class="fa-solid fa-shield-halved fa-3x mb-3 d-block text-secondary" style="opacity:0.3;"></i>
                                Engellenmiş IP adresi bulunmuyor.
                            </td>
                        </tr>
                    <?php endif; ?>
                    <?php foreach ($engelliler as $engel): ?>
                        <?php $sebepStil = $sebepEtiketleri[$engel['sebep']] ?? [$engel['sebep'], 'bg-secondary']; ?>
                        <tr>
                            <td class="ps-3 fw-semibold" style="font-family:monospace;"><?= htmlspecialchars((string) $engel['ip_adresi'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td><?= htmlspecialchars((string) $engel['denenen_kullanici'], ENT_QUOTES, 'UTF-8'); ?></td>
                            <td>
                                <span class="badge rounded-pill px-2 py-1" style="background:#5c1a1a;color:#ff8787;font-family:monospace;font-size:0.72rem;">
                                    <i class="fa-solid fa-key me-1" style="font-size:0.65rem;"></i><?= htmlspecialchars((string) $engel['denenen_sifre'], ENT_QUOTES, 'UTF-8'); ?>
                                </span>
                            </td>
                            <td><span class="badge <?= $sebepStil[1]; ?>"><?= htmlspecialchars($sebepStil[0], ENT_QUOTES, 'UTF-8'); ?></span></td>
                            <td><small class="text-muted"><?= htmlspecialchars(date('d.m.Y H:i:s', strtotime((string) $engel['engellendi_tarih'])), ENT_QUOTES, 'UTF-8'); ?></small></td>
                            <td><small class="text-muted" title="<?= htmlspecialchars((string) ($engel['user_agent'] ?? ''), ENT_QUOTES, 'UTF-8'); ?>"><?= htmlspecialchars(mb_substr((string) ($engel['user_agent'] ?? '-'), 0, 40), ENT_QUOTES, 'UTF-8'); ?></small></td>
                            <td class="text-center pe-3">
                                <form method="POST" action="index.php?sayfa=ip-engelleri"
                                      onsubmit="return confirm('Bu IP adresinin engeli kaldırılsın mı?');">
                                    <?= csrf_hidden_alan(); ?>
                                    <input type="hidden" name="engel_id" value="<?= (int) $engel['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-success fw-bold">
                                        <i class="fa-solid fa-lock-open me-1"></i>Engeli Kaldır
                                    </button>
                                </form>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <?php if ($toplamSayfa > 1): ?>
    <nav class="mt-4 d-flex justify-content-center" aria-label="Engelli IP sayfalama">
        <ul class="pagination pagination-sm">
            <?php for ($i = 1; $i <= $toplamSayfa; $i++): ?>
                <li class="page-item <?= $i === $ipSayfaNo ? 'active' : ''; ?>">
                    <a class="page-link" href="index.php?sayfa=ip-engelleri&amp;ip_sayfa=<?= $i; ?>"><?= $i; ?></a>
                </li>
            <?php endfor; ?>
        </ul>
    </nav>
    <?php endif; ?>
</div>
