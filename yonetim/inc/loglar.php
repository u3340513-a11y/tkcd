<?php

declare(strict_types=1);

/**
 * Sistem Logları Sayfası
 *
 * Sadece `gelistirici` rolü erişebilir.
 * Tüm yönetim paneli kullanıcı işlemlerini detaylı olarak listeler.
 *
 * Özellikler:
 *   - Tarih aralığı filtresi
 *   - Kullanıcı ve işlem türü filtresi
 *   - Serbest metin araması
 *   - Sayfalama (50 kayıt/sayfa)
 *   - Özet istatistik kartları
 */

// Savunma derinliği — index.php'de de kontrol var
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die("Yetkisiz erişim!");
}
if (($_SESSION['rol'] ?? '') !== 'gelistirici') {
    die("Erişim Engellendi: Bu sayfa sadece geliştirici hesabına açıktır.");
}

// ─── FİLTRE PARAMETRELERİ ──────────────────────────────────────────────
$filtre_kullanici  = trim($_GET['kullanici'] ?? '');
$filtre_islem      = trim($_GET['islem'] ?? '');
$filtre_tarih_bas  = trim($_GET['tarih_bas'] ?? '');
$filtre_tarih_bit  = trim($_GET['tarih_bit'] ?? '');
$filtre_arama      = trim($_GET['arama'] ?? '');
$sayfa_no          = max(1, intval($_GET['log_sayfa'] ?? 1));
$limit             = 50;
$offset            = ($sayfa_no - 1) * $limit;

// ─── SORGU OLUŞTURMA ───────────────────────────────────────────────────
$where_kosullari = [];
$where_params    = [];

if ($filtre_kullanici !== '') {
    $where_kosullari[] = "l.kullanici_adi = ?";
    $where_params[]    = $filtre_kullanici;
}
if ($filtre_islem !== '') {
    $where_kosullari[] = "l.islem_turu = ?";
    $where_params[]    = $filtre_islem;
}
if ($filtre_tarih_bas !== '') {
    $where_kosullari[] = "l.tarih >= ?";
    $where_params[]    = $filtre_tarih_bas . ' 00:00:00';
}
if ($filtre_tarih_bit !== '') {
    $where_kosullari[] = "l.tarih <= ?";
    $where_params[]    = $filtre_tarih_bit . ' 23:59:59';
}
if ($filtre_arama !== '') {
    $where_kosullari[] = "l.islem_aciklama LIKE ?";
    $where_params[]    = '%' . $filtre_arama . '%';
}

$where_sql = count($where_kosullari) > 0
    ? 'WHERE ' . implode(' AND ', $where_kosullari)
    : '';

// ─── VERİ ÇEK ─────────────────────────────────────────────────────────
try {
    // Toplam kayıt sayısı
    $sayac_stmt = $db_baglanti->prepare("SELECT COUNT(*) FROM yonetim_log l {$where_sql}");
    $sayac_stmt->execute($where_params);
    $toplam_kayit = (int) $sayac_stmt->fetchColumn();

    // Log kayıtları
    $log_stmt = $db_baglanti->prepare(
        "SELECT l.id, l.yonetici_id, l.kullanici_adi, l.rol, l.islem_turu,
                l.islem_aciklama, l.hedef_tablo, l.hedef_id,
                l.ip_adresi, l.user_agent, l.tarih
           FROM yonetim_log l
           {$where_sql}
          ORDER BY l.tarih DESC
          LIMIT {$limit} OFFSET {$offset}"
    );
    $log_stmt->execute($where_params);
    $loglar = $log_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Özet kartlar
    $bugun = date('Y-m-d');
    $bugunki_islem = (int) $db_baglanti->prepare(
        "SELECT COUNT(*) FROM yonetim_log WHERE DATE(tarih) = ?"
    )->execute([$bugun]) ? $db_baglanti->query(
        "SELECT COUNT(*) FROM yonetim_log WHERE DATE(tarih) = '{$bugun}'"
    )->fetchColumn() : 0;

    $bugunki_login = (int) $db_baglanti->query(
        "SELECT COUNT(*) FROM yonetim_log WHERE islem_turu = 'giris' AND DATE(tarih) = '{$bugun}'"
    )->fetchColumn();

    $aktif_kullanicilar = (int) $db_baglanti->query(
        "SELECT COUNT(DISTINCT kullanici_adi) FROM yonetim_log WHERE DATE(tarih) = '{$bugun}'"
    )->fetchColumn();

    $son_24saat_islem = (int) $db_baglanti->query(
        "SELECT COUNT(*) FROM yonetim_log WHERE tarih >= DATE_SUB(NOW(), INTERVAL 24 HOUR)"
    )->fetchColumn();

    // Filtre dropdown verileri
    $kullanicilar = $db_baglanti->query(
        "SELECT DISTINCT kullanici_adi FROM yonetim_log ORDER BY kullanici_adi"
    )->fetchAll(PDO::FETCH_COLUMN);

    $islem_turleri = $db_baglanti->query(
        "SELECT DISTINCT islem_turu FROM yonetim_log ORDER BY islem_turu"
    )->fetchAll(PDO::FETCH_COLUMN);

} catch (\PDOException $e) {
    error_log('Log listeleme hatası: ' . $e->getMessage());
    $loglar              = [];
    $toplam_kayit        = 0;
    $bugunki_islem       = 0;
    $bugunki_login       = 0;
    $aktif_kullanicilar  = 0;
    $son_24saat_islem    = 0;
    $kullanicilar        = [];
    $islem_turleri       = [];
}

$toplam_sayfa = max(1, (int) ceil($toplam_kayit / $limit));

// İşlem türü → renk ve ikon eşlemesi
$islem_stilleri = [
    'giris'            => ['bg-success text-white',   'fa-right-to-bracket',  'Giriş'],
    'giris_basarisiz'  => ['bg-danger text-white',    'fa-xmark',             'Başarısız Giriş'],
    'cikis'            => ['bg-secondary text-white', 'fa-right-from-bracket','Çıkış'],
    'sayfa_goruntulem' => ['bg-dark text-white',       'fa-eye',               'Sayfa'],
    'uye_onayla'       => ['bg-success text-white',   'fa-check',             'Onay'],
    'uye_reddet'       => ['bg-danger text-white',    'fa-ban',               'Red'],
    'uye_sil'          => ['bg-danger text-white',    'fa-trash',             'Silme'],
    'uye_ekle'         => ['bg-primary text-white',   'fa-user-plus',         'Ekleme'],
    'uye_duzenle'      => ['bg-dark text-white',      'fa-pen',               'Düzenleme'],
    'temsilci_ata'     => ['bg-info text-white',      'fa-user-tag',          'Statü'],
    'hesap_olustur'    => ['bg-primary text-white',   'fa-user-plus',         'Hesap +'],
    'hesap_sil'        => ['bg-danger text-white',    'fa-user-minus',        'Hesap -'],
    'sifre_sifirla'    => ['bg-dark text-white',      'fa-key',               'Şifre'],
    'excel_indir'      => ['bg-success text-white',   'fa-file-excel',        'Excel'],
    'pdf_indir'        => ['bg-danger text-white',    'fa-file-pdf',          'PDF'],
];

/**
 * Kısaltılmış User-Agent döndürür.
 */
function kisalt_ua(?string $ua): string
{
    if ($ua === null || $ua === '') {
        return '-';
    }
    // Tarayıcı adını çıkarmaya çalış
    if (preg_match('/(?:Chrome|Firefox|Safari|Edge|Opera|MSIE|Trident)[\/\s][\d.]+/', $ua, $m)) {
        return $m[0];
    }
    return mb_substr($ua, 0, 40) . '…';
}
?>

<div class="container-fluid py-4 px-md-4">
    <!-- Başlık -->
    <div class="d-flex justify-content-between align-items-center mb-4 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-terminal me-2" style="color:#00c9a7;"></i>Sistem Logları</h2>
            <p class="text-muted mb-0 small">Tüm yönetim paneli kullanıcı işlemlerinin detaylı kayıtları.</p>
        </div>
        <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#0f3460;color:rgba(255,255,255,0.8);font-size:0.8rem;">
            <i class="fa-solid fa-database me-1"></i>
            Toplam <?= number_format($toplam_kayit); ?> Kayıt
        </span>
    </div>

    <!-- Özet Kartlar -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-lg-3">
            <div class="rounded-3 p-3 h-100" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(0,201,167,0.15);border:1px solid rgba(0,201,167,0.3);">
                        <i class="fa-solid fa-bolt" style="color:#00c9a7;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="color:#fff;font-size:1.3rem;"><?= number_format($bugunki_islem); ?></div>
                        <div class="small" style="color:rgba(255,255,255,0.45);">Bugünkü İşlem</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rounded-3 p-3 h-100" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(74,144,217,0.15);border:1px solid rgba(74,144,217,0.3);">
                        <i class="fa-solid fa-right-to-bracket" style="color:#4a90d9;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="color:#fff;font-size:1.3rem;"><?= number_format($bugunki_login); ?></div>
                        <div class="small" style="color:rgba(255,255,255,0.45);">Bugünkü Giriş</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rounded-3 p-3 h-100" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(224,85,85,0.15);border:1px solid rgba(224,85,85,0.3);">
                        <i class="fa-solid fa-users" style="color:#e05555;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="color:#fff;font-size:1.3rem;"><?= number_format($aktif_kullanicilar); ?></div>
                        <div class="small" style="color:rgba(255,255,255,0.45);">Aktif Kullanıcı</div>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-6 col-lg-3">
            <div class="rounded-3 p-3 h-100" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center gap-3">
                    <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:40px;height:40px;background:rgba(245,166,35,0.15);border:1px solid rgba(245,166,35,0.3);">
                        <i class="fa-solid fa-clock-rotate-left" style="color:#f5a623;"></i>
                    </div>
                    <div>
                        <div class="fw-bold" style="color:#fff;font-size:1.3rem;"><?= number_format($son_24saat_islem); ?></div>
                        <div class="small" style="color:rgba(255,255,255,0.45);">Son 24 Saat</div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Filtreler -->
    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" class="row g-2 align-items-end">
                <input type="hidden" name="sayfa" value="loglar">
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Kullanıcı</label>
                    <select name="kullanici" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <?php foreach ($kullanicilar as $k): ?>
                            <option value="<?= htmlspecialchars($k); ?>" <?= $filtre_kullanici === $k ? 'selected' : ''; ?>><?= htmlspecialchars($k); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">İşlem Türü</label>
                    <select name="islem" class="form-select form-select-sm">
                        <option value="">Tümü</option>
                        <?php foreach ($islem_turleri as $it): ?>
                            <?php $stil = $islem_stilleri[$it] ?? ['bg-secondary', 'fa-circle', $it]; ?>
                            <option value="<?= htmlspecialchars($it); ?>" <?= $filtre_islem === $it ? 'selected' : ''; ?>><?= htmlspecialchars($stil[2]); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Başlangıç</label>
                    <input type="date" name="tarih_bas" class="form-control form-control-sm" value="<?= htmlspecialchars($filtre_tarih_bas); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Bitiş</label>
                    <input type="date" name="tarih_bit" class="form-control form-control-sm" value="<?= htmlspecialchars($filtre_tarih_bit); ?>">
                </div>
                <div class="col-md-2">
                    <label class="form-label small fw-bold mb-1">Arama</label>
                    <input type="text" name="arama" class="form-control form-control-sm" placeholder="Açıklamada ara…" value="<?= htmlspecialchars($filtre_arama); ?>">
                </div>
                <div class="col-md-2 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm fw-bold flex-fill"><i class="fa-solid fa-filter me-1"></i>Filtrele</button>
                    <a href="index.php?sayfa=loglar" class="btn btn-outline-secondary btn-sm fw-bold"><i class="fa-solid fa-xmark"></i></a>
                </div>
            </form>
        </div>
    </div>

    <!-- Log Tablosu -->
    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" style="width:50px;">#</th>
                            <th style="width:140px;">Tarih</th>
                            <th>Kullanıcı</th>
                            <th class="text-center">Rol</th>
                            <th class="text-center">İşlem</th>
                            <th>Açıklama</th>
                            <th class="text-center">IP</th>
                            <th class="text-center pe-3">Tarayıcı</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($loglar) > 0): ?>
                            <?php foreach ($loglar as $log): ?>
                                <?php
                                $stil = $islem_stilleri[$log['islem_turu']] ?? ['bg-secondary', 'fa-circle', $log['islem_turu']];
                                $tarih = date('d.m.Y H:i:s', strtotime($log['tarih']));

                                $rol_etiketleri_log = [
                                    'admin'            => ['Tam Yetkili', 'bg-dark'],
                                    'yonetim'          => ['Yönetim', 'bg-primary'],
                                    'gelistirici'      => ['Geliştirici', 'bg-info'],
                                    'il_baskani'       => ['İl Başkanı', 'bg-success'],
                                    'ilce_baskani'     => ['İlçe Başkanı', ''],
                                    'kurum_temsilcisi'  => ['Kurum Temsilcisi', 'bg-warning text-dark'],
                                ];
                                $rol_stil = $rol_etiketleri_log[$log['rol'] ?? ''] ?? ['Bilinmeyen', 'bg-secondary'];
                                ?>
                                <tr>
                                    <td class="ps-3 text-muted"><?= $log['id']; ?></td>
                                    <td><small class="text-muted"><?= $tarih; ?></small></td>
                                    <td class="fw-semibold">
                                        <i class="fa-solid fa-user-shield me-1 text-primary" style="font-size:0.75rem;"></i>
                                        <?= htmlspecialchars($log['kullanici_adi']); ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $rol_stil[1]; ?> px-2 py-1" style="font-size:0.7rem;<?= ($log['rol'] ?? '') === 'ilce_baskani' ? 'background-color:#6a1b9a!important;' : ''; ?>">
                                            <?= $rol_stil[0]; ?>
                                        </span>
                                    </td>
                                    <td class="text-center" style="white-space:nowrap;">
                                        <span class="badge <?= $stil[0]; ?> px-2 py-1" style="font-size:0.75rem;">
                                            <i class="fa-solid <?= $stil[1]; ?> me-1"></i><?= $stil[2]; ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $aciklama_tam  = htmlspecialchars($log['islem_aciklama']);
                                        $kisaltilmis   = mb_strpos($log['islem_aciklama'], '…') !== false;
                                        ?>
                                        <span class="text-dark<?= $kisaltilmis ? ' log-tooltip' : ''; ?>"
                                              <?= $kisaltilmis ? 'data-bs-toggle="tooltip" data-bs-placement="top" data-bs-custom-class="log-tip" title="' . $aciklama_tam . '"' : ''; ?>
                                              style="<?= $kisaltilmis ? 'cursor:help;border-bottom:1px dotted rgba(0,0,0,0.25);' : ''; ?>"
                                        ><?= $aciklama_tam; ?></span>
                                        <?php if ($log['hedef_tablo']): ?>
                                            <br><small class="text-muted"><i class="fa-solid fa-table me-1"></i><?= htmlspecialchars($log['hedef_tablo']); ?><?= $log['hedef_id'] ? ' #' . $log['hedef_id'] : ''; ?></small>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center"><small class="text-muted"><?= htmlspecialchars($log['ip_adresi'] ?? '-'); ?></small></td>
                                    <td class="text-center pe-3"><small class="text-muted" title="<?= htmlspecialchars($log['user_agent'] ?? ''); ?>"><?= htmlspecialchars(kisalt_ua($log['user_agent'])); ?></small></td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="8" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-terminal fa-3x mb-3 d-block text-secondary" style="opacity:0.3;"></i>
                                    <?= count($where_kosullari) > 0 ? 'Filtreye uygun log kaydı bulunamadı.' : 'Henüz log kaydı bulunmuyor.'; ?>
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Sayfalama -->
    <?php if ($toplam_sayfa > 1): ?>
    <nav class="mt-4 d-flex justify-content-center" aria-label="Log sayfalama">
        <ul class="pagination pagination-sm">
            <li class="page-item <?= $sayfa_no <= 1 ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['log_sayfa' => $sayfa_no - 1])); ?>">
                    <i class="fa-solid fa-chevron-left"></i>
                </a>
            </li>
            <?php
            $baslangic = max(1, $sayfa_no - 2);
            $bitis     = min($toplam_sayfa, $sayfa_no + 2);
            if ($baslangic > 1) {
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['log_sayfa' => 1])) . '">1</a></li>';
                if ($baslangic > 2) {
                    echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                }
            }
            for ($i = $baslangic; $i <= $bitis; $i++):
            ?>
                <li class="page-item <?= $i === $sayfa_no ? 'active' : ''; ?>">
                    <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['log_sayfa' => $i])); ?>"><?= $i; ?></a>
                </li>
            <?php
            endfor;
            if ($bitis < $toplam_sayfa) {
                if ($bitis < $toplam_sayfa - 1) {
                    echo '<li class="page-item disabled"><span class="page-link">…</span></li>';
                }
                echo '<li class="page-item"><a class="page-link" href="?' . http_build_query(array_merge($_GET, ['log_sayfa' => $toplam_sayfa])) . '">' . $toplam_sayfa . '</a></li>';
            }
            ?>
            <li class="page-item <?= $sayfa_no >= $toplam_sayfa ? 'disabled' : ''; ?>">
                <a class="page-link" href="<?= '?' . http_build_query(array_merge($_GET, ['log_sayfa' => $sayfa_no + 1])); ?>">
                    <i class="fa-solid fa-chevron-right"></i>
                </a>
            </li>
        </ul>
    </nav>
    <?php endif; ?>

    <!-- Bilgi Notu -->
    <div class="mt-3 rounded-3 px-3 py-2 d-flex align-items-center gap-2" style="background:rgba(15,52,96,0.06);border:1px solid rgba(15,52,96,0.1);">
        <i class="fa-solid fa-circle-info small text-muted"></i>
        <span class="small text-muted">Log kayıtları yönetim panelindeki tüm kullanıcı işlemlerini kapsar. Kayıtlar silinemez ve değiştirilemez.</span>
    </div>
</div>

<style>
.log-tip .tooltip-inner {
    max-width: 420px;
    text-align: left;
    padding: 10px 14px;
    font-size: 0.82rem;
    line-height: 1.5;
    background: #0f1528;
    border: 1px solid rgba(255,255,255,0.1);
    border-radius: 8px;
    box-shadow: 0 8px 24px rgba(0,0,0,0.35);
}
.log-tip .tooltip-arrow::before {
    border-top-color: #0f1528;
}
</style>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var tooltipEls = document.querySelectorAll('[data-bs-toggle="tooltip"]');
    tooltipEls.forEach(function(el) {
        new bootstrap.Tooltip(el);
    });
});
</script>
