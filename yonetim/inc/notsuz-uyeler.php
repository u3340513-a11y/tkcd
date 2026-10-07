<?php

declare(strict_types=1);

/**
 * Notu Olmayan Üyeler — filtre ve önizleme sayfası.
 *
 * Cinsiyet seçilir; o cinsiyetteki onaylı üyelerden hiç notu bulunmayanlar
 * listelenir ve Excel (CSV) olarak indirilebilir.
 *
 * @var PDO  $db_baglanti
 * @var bool $is_gelistirici  index.php'den (yalnızca geliştirici rolü erişebilir)
 */

if (!$is_gelistirici) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu sayfa sadece geliştirici hesabına açıktır.</div></div>';
    return;
}

require_once __DIR__ . '/notsuz-uyeler-sorgu.php';

const NOTSUZ_ONIZLEME_SATIR = 100;

$cinsiyetSecenekleri = [
    'Kadın'                              => 'Kadın',
    'Erkek'                              => 'Erkek',
    NotsuzUyeRaporu::CINSIYET_HER_IKISI  => 'Kadın ve Erkek (cinsiyeti seçilmiş tümü)',
];

$seciliCinsiyet = NotsuzUyeRaporu::cinsiyetDogrula(trim((string) ($_GET['cinsiyet'] ?? '')));
$sorguYapildi   = $seciliCinsiyet !== null;
$uyeler         = [];
$hataMesaji     = '';

if ($sorguYapildi) {
    try {
        $uyeler = (new NotsuzUyeRaporu($db_baglanti))->uyeler($seciliCinsiyet);
    } catch (PDOException $e) {
        error_log('Notsuz üye raporu hatası: ' . $e->getMessage());
        $hataMesaji = 'Liste oluşturulurken bir hata oluştu.';
    }
}

$toplam        = count($uyeler);
$iletisimGoster = kisi_bilgisi_gorebilir();
$indirmeUrl    = $sorguYapildi
    ? 'index.php?' . http_build_query(['sayfa' => 'notsuz-uyeler-excel', 'cinsiyet' => $seciliCinsiyet])
    : '';
?>

<div class="container-fluid py-4 px-md-4">
    <div class="mb-4">
        <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-note-sticky me-2" style="color:#e94560;"></i>Notsuz Üyeler</h2>
        <p class="text-muted mb-0 small">Cinsiyet seçin; hakkında hiç not yazılmamış onaylı üyeleri listeleyin ve Excel olarak indirin.</p>
    </div>

    <div class="card border-0 shadow-sm rounded-3 mb-4">
        <div class="card-body p-3">
            <form method="GET" action="index.php" class="row g-2 align-items-end">
                <input type="hidden" name="sayfa" value="notsuz-uyeler">
                <div class="col-md-5">
                    <label for="notsuzCinsiyet" class="form-label small fw-bold mb-1">Cinsiyet</label>
                    <select id="notsuzCinsiyet" name="cinsiyet" class="form-select form-select-sm" required>
                        <option value="">Seçiniz…</option>
                        <?php foreach ($cinsiyetSecenekleri as $deger => $etiket): ?>
                            <option value="<?= htmlspecialchars($deger, ENT_QUOTES, 'UTF-8'); ?>" <?= $seciliCinsiyet === $deger ? 'selected' : ''; ?>>
                                <?= htmlspecialchars($etiket, ENT_QUOTES, 'UTF-8'); ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3 d-flex gap-2">
                    <button type="submit" class="btn btn-dark btn-sm fw-bold flex-fill">
                        <i class="fa-solid fa-filter me-1"></i>Listele
                    </button>
                    <a href="index.php?sayfa=notsuz-uyeler" class="btn btn-outline-secondary btn-sm fw-bold" aria-label="Filtreyi temizle">
                        <i class="fa-solid fa-xmark"></i>
                    </a>
                </div>
            </form>
        </div>
    </div>

    <?php if ($hataMesaji !== ''): ?>
        <div class="alert alert-danger py-2" role="alert"><?= htmlspecialchars($hataMesaji, ENT_QUOTES, 'UTF-8'); ?></div>
    <?php endif; ?>

    <?php if ($sorguYapildi && $hataMesaji === ''): ?>
        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
            <span class="badge rounded-pill px-3 py-2 fw-bold" style="background:#0f3460;color:rgba(255,255,255,0.85);font-size:0.85rem;">
                <i class="fa-solid fa-users me-1"></i><?= number_format($toplam); ?> notsuz üye
            </span>
            <?php if ($toplam > 0): ?>
                <a href="<?= htmlspecialchars($indirmeUrl, ENT_QUOTES, 'UTF-8'); ?>" class="btn btn-success btn-sm fw-bold">
                    <i class="fa-solid fa-file-excel me-1"></i>Excel İndir
                </a>
            <?php endif; ?>
        </div>

        <div class="card border-0 shadow-sm rounded-3">
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.85rem;">
                        <thead class="table-dark">
                            <tr>
                                <th class="ps-3">#</th>
                                <th>Adı Soyadı</th>
                                <th>Cinsiyet</th>
                                <?php if ($iletisimGoster): ?><th>Telefon</th><?php endif; ?>
                                <th>İkamet İli</th>
                                <th>Kurum</th>
                                <th class="pe-3">Statü</th>
                            </tr>
                        </thead>
                        <tbody>
                        <?php if ($toplam === 0): ?>
                            <tr>
                                <td colspan="7" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-circle-check fa-3x mb-3 d-block text-success" style="opacity:0.4;"></i>
                                    Bu filtreye uyan notsuz üye bulunmuyor.
                                </td>
                            </tr>
                        <?php endif; ?>
                        <?php foreach (array_slice($uyeler, 0, NOTSUZ_ONIZLEME_SATIR) as $sira => $uye): ?>
                            <tr>
                                <td class="ps-3 text-muted"><?= $sira + 1; ?></td>
                                <td class="fw-semibold">
                                    <a href="index.php?sayfa=uye-detay&amp;id=<?= (int) $uye['id']; ?>" class="text-decoration-none">
                                        <?= htmlspecialchars((string) $uye['adi_soyadi'], ENT_QUOTES, 'UTF-8'); ?>
                                    </a>
                                </td>
                                <td><?= htmlspecialchars((string) $uye['cinsiyet'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php if ($iletisimGoster): ?>
                                    <td><?= htmlspecialchars((string) $uye['telefon'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <?php endif; ?>
                                <td><?= htmlspecialchars((string) $uye['ikamet_ili'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td><?= htmlspecialchars((string) $uye['kurum'], ENT_QUOTES, 'UTF-8'); ?></td>
                                <td class="pe-3"><?= htmlspecialchars((string) ($uye['temsilci_turu'] ?: 'Normal Üye'), ENT_QUOTES, 'UTF-8'); ?></td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>

        <?php if ($toplam > NOTSUZ_ONIZLEME_SATIR): ?>
            <p class="text-muted small mt-2 mb-0">
                İlk <?= NOTSUZ_ONIZLEME_SATIR; ?> kayıt gösteriliyor. Tüm liste için Excel dosyasını indirin.
            </p>
        <?php endif; ?>
    <?php endif; ?>
</div>
