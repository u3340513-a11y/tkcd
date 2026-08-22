<?php
/**
 * Hesap Yönetimi Sayfası
 * 
 * Sadece admin rolü erişebilir. Panel kullanıcı hesaplarını
 * listeleme, oluşturma, şifre sıfırlama ve silme işlemlerini yönetir.
 * 
 * Güvenlik: Tüm girdiler sanitize edilir, şifreler bcrypt ile hash'lenir,
 * admin61 hesabı silinemez.
 */

// Güvenlik: Sadece admin erişebilir (index.php'de de kontrol var, savunma derinliği)
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die("Yetkisiz erişim!");
}
$kullanici_rolu = $_SESSION['rol'] ?? 'admin';
if ($kullanici_rolu !== 'admin') {
    die("Erişim Engellendi: Bu sayfa sadece tam yetkili yöneticilere açıktır.");
}

$mesaj = "";
$mesaj_turu = "";

// ─── YENİ HESAP OLUŞTURMA ──────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hesap_olustur'])) {
    $yeni_kullanici = trim($_POST['yeni_kullanici_adi'] ?? '');
    $yeni_sifre     = trim($_POST['sifre'] ?? '');
    $yeni_rol       = trim($_POST['rol'] ?? '');
    $yeni_il        = trim($_POST['sorumlu_il'] ?? '') ?: null;
    $yeni_ilce      = trim($_POST['sorumlu_ilce'] ?? '') ?: null;
    $yeni_kurum     = trim($_POST['sorumlu_kurum'] ?? '') ?: null;

    $gecerli_roller = ['yonetim', 'il_baskani', 'ilce_baskani', 'kurum_temsilcisi'];

    if (empty($yeni_kullanici) || empty($yeni_sifre) || empty($yeni_rol)) {
        $mesaj = "Kullanıcı adı, şifre ve rol alanları zorunludur!";
        $mesaj_turu = "danger";
    } elseif (!in_array($yeni_rol, $gecerli_roller, true)) {
        $mesaj = "Geçersiz rol seçimi!";
        $mesaj_turu = "danger";
    } elseif (mb_strlen($yeni_sifre, 'UTF-8') < 6) {
        $mesaj = "Şifre en az 6 karakter olmalıdır!";
        $mesaj_turu = "danger";
    } elseif ($yeni_rol === 'il_baskani' && empty($yeni_il)) {
        $mesaj = "İl Başkanı rolü için sorumlu il seçimi zorunludur!";
        $mesaj_turu = "danger";
    } elseif ($yeni_rol === 'ilce_baskani' && empty($yeni_ilce)) {
        $mesaj = "İlçe Başkanı rolü için sorumlu ilçe seçimi zorunludur!";
        $mesaj_turu = "danger";
    } elseif ($yeni_rol === 'kurum_temsilcisi' && empty($yeni_kurum)) {
        $mesaj = "Kurum Temsilcisi rolü için sorumlu kurum seçimi zorunludur!";
        $mesaj_turu = "danger";
    } else {
        try {
            // Kullanıcı adı benzersizlik kontrolü
            $kontrol = $db_baglanti->prepare("SELECT COUNT(*) FROM dernek_yoneticiler WHERE kullanici_adi = ?");
            $kontrol->execute([$yeni_kullanici]);
            if ($kontrol->fetchColumn() > 0) {
                $mesaj = "Bu kullanıcı adı zaten kullanımda!";
                $mesaj_turu = "danger";
            } else {
                $hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
                $ekle = $db_baglanti->prepare(
                    "INSERT INTO dernek_yoneticiler (kullanici_adi, sifre, rol, sorumlu_il, sorumlu_ilce, sorumlu_kurum) 
                     VALUES (?, ?, ?, ?, ?, ?)"
                );
                $ekle->execute([$yeni_kullanici, $hash, $yeni_rol, $yeni_il, $yeni_ilce, $yeni_kurum]);
                $mesaj = "Hesap başarıyla oluşturuldu: " . htmlspecialchars($yeni_kullanici);
                $mesaj_turu = "success";
            }
        } catch (\PDOException $e) {
            error_log('Hesap oluşturma hatası: ' . $e->getMessage());
            $mesaj = "Hesap oluşturulurken bir hata oluştu!";
            $mesaj_turu = "danger";
        }
    }
}

// ─── ŞİFRE SIFIRLAMA ───────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['sifre_sifirla'])) {
    $hedef_id   = intval($_POST['hesap_id'] ?? 0);
    $yeni_sifre = trim($_POST['yeni_sifre'] ?? '');

    if ($hedef_id <= 0 || empty($yeni_sifre)) {
        $mesaj = "Geçersiz hesap veya şifre!";
        $mesaj_turu = "danger";
    } elseif (mb_strlen($yeni_sifre, 'UTF-8') < 6) {
        $mesaj = "Şifre en az 6 karakter olmalıdır!";
        $mesaj_turu = "danger";
    } else {
        try {
            $hash = password_hash($yeni_sifre, PASSWORD_DEFAULT);
            $guncelle = $db_baglanti->prepare("UPDATE dernek_yoneticiler SET sifre = ? WHERE id = ?");
            $guncelle->execute([$hash, $hedef_id]);
            $mesaj = "Şifre başarıyla güncellendi!";
            $mesaj_turu = "success";
        } catch (\PDOException $e) {
            error_log('Şifre sıfırlama hatası: ' . $e->getMessage());
            $mesaj = "Şifre güncellenirken bir hata oluştu!";
            $mesaj_turu = "danger";
        }
    }
}

// ─── HESAP SİLME ────────────────────────────────────────────────────────
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'hesap_sil' && isset($_GET['id'])) {
    $silinecek_id = intval($_GET['id']);
    try {
        // admin61 koruması
        $koruma = $db_baglanti->prepare("SELECT kullanici_adi FROM dernek_yoneticiler WHERE id = ?");
        $koruma->execute([$silinecek_id]);
        $silinecek = $koruma->fetchColumn();

        if ($silinecek === 'admin61') {
            $mesaj = "Ana yönetici hesabı (admin61) silinemez!";
            $mesaj_turu = "danger";
        } else {
            $sil = $db_baglanti->prepare("DELETE FROM dernek_yoneticiler WHERE id = ?");
            $sil->execute([$silinecek_id]);
            $mesaj = "Hesap başarıyla silindi: " . htmlspecialchars($silinecek);
            $mesaj_turu = "success";
        }
    } catch (\PDOException $e) {
        error_log('Hesap silme hatası: ' . $e->getMessage());
        $mesaj = "Hesap silinirken bir hata oluştu!";
        $mesaj_turu = "danger";
    }
}

// ─── MEVCUT HESAPLARI LİSTELE ───────────────────────────────────────────
try {
    $hesaplar_sorgu = $db_baglanti->query(
        "SELECT id, kullanici_adi, rol, sorumlu_il, sorumlu_ilce, sorumlu_kurum, olusturma_tarihi 
         FROM dernek_yoneticiler 
         ORDER BY id ASC"
    );
    $hesaplar = $hesaplar_sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('Hesap listeleme hatası: ' . $e->getMessage());
    $hesaplar = [];
}

// ─── DROPDOWN VERİLERİ (İl, İlçe, Kurum listeleri) ─────────────────────
try {
    $iller = $db_baglanti->query(
        "SELECT DISTINCT ikamet_ili FROM dernek_uyeler 
         WHERE onay_durumu = 'onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != '' 
         ORDER BY ikamet_ili ASC"
    )->fetchAll(PDO::FETCH_COLUMN);

    // İlçe verileri statik dosyadan yüklenir (81 il, tüm ilçeler)
    $turkiye_il_ilce = include __DIR__ . '/turkiye-ilce-verileri.php';
    $turkiye_illeri = array_keys($turkiye_il_ilce);

    $kurumlar = $db_baglanti->query(
        "SELECT DISTINCT kurum FROM dernek_uyeler 
         WHERE onay_durumu = 'onayli' AND kurum IS NOT NULL AND kurum != '' 
         ORDER BY kurum ASC"
    )->fetchAll(PDO::FETCH_COLUMN);
} catch (\PDOException $e) {
    error_log('Dropdown veri hatası: ' . $e->getMessage());
    $iller = [];
    $ilceler = [];
    $kurumlar = [];
}

$rol_etiketleri = [
    'admin'              => ['Tam Yetkili', 'bg-dark text-white'],
    'yonetim'            => ['Yönetim', 'bg-primary text-white'],
    'il_baskani'         => ['İl Başkanı', 'bg-success text-white'],
    'ilce_baskani'       => ['İlçe Başkanı', 'text-white'],
    'kurum_temsilcisi'   => ['Kurum Temsilcisi', 'bg-warning text-dark'],
];
?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-users-gear me-2 text-primary"></i>Hesap Yönetimi</h2>
            <p class="text-muted mb-0 small">Panel kullanıcı hesaplarını oluşturun, düzenleyin ve yönetin.</p>
        </div>
        <button type="button" class="btn btn-dark fw-bold px-4 shadow-sm" data-bs-toggle="modal" data-bs-target="#yeniHesapModal">
            <i class="fa-solid fa-user-plus me-2"></i>Yeni Hesap Oluştur
        </button>
    </div>

    <?php if (!empty($mesaj)): ?>
        <div class="alert alert-<?= $mesaj_turu; ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
            <i class="fa-solid fa-<?= $mesaj_turu === 'success' ? 'circle-check' : 'circle-exclamation'; ?> me-2"></i>
            <strong><?= $mesaj; ?></strong>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-4" style="width: 50px;">#</th>
                            <th>Kullanıcı Adı</th>
                            <th class="text-center">Rol</th>
                            <th>Sorumluluk Alanı</th>
                            <th class="text-center">Oluşturma Tarihi</th>
                            <th class="text-center pe-4" style="width: 200px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($hesaplar) > 0): ?>
                            <?php foreach ($hesaplar as $hesap): ?>
                                <?php
                                $etiket = $rol_etiketleri[$hesap['rol']] ?? ['Bilinmeyen', 'bg-secondary text-white'];
                                $sorumluluk = '-';
                                if (!empty($hesap['sorumlu_il'])) {
                                    $sorumluluk = '<i class="fa-solid fa-building-flag text-success me-1"></i>' . htmlspecialchars($hesap['sorumlu_il']);
                                } elseif (!empty($hesap['sorumlu_ilce'])) {
                                    $sorumluluk = '<i class="fa-solid fa-map-location-dot me-1" style="color:#6a1b9a;"></i>' . htmlspecialchars($hesap['sorumlu_ilce']);
                                } elseif (!empty($hesap['sorumlu_kurum'])) {
                                    $sorumluluk = '<i class="fa-solid fa-building-user text-warning me-1"></i>' . htmlspecialchars($hesap['sorumlu_kurum']);
                                }
                                $tarih = !empty($hesap['olusturma_tarihi']) ? date('d.m.Y H:i', strtotime($hesap['olusturma_tarihi'])) : '-';
                                ?>
                                <tr>
                                    <td class="ps-4 text-muted fw-bold"><?= $hesap['id']; ?></td>
                                    <td class="fw-bold">
                                        <i class="fa-solid fa-user-shield me-2 text-primary"></i>
                                        <?= htmlspecialchars($hesap['kullanici_adi']); ?>
                                        <?php if ($hesap['kullanici_adi'] === 'admin61'): ?>
                                            <span class="badge bg-danger ms-1">Korumalı</span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="text-center">
                                        <span class="badge <?= $etiket[1]; ?> px-2 py-1 fw-semibold" <?= $hesap['rol'] === 'ilce_baskani' ? 'style="background-color: #6a1b9a !important;"' : ''; ?>>
                                            <?= $etiket[0]; ?>
                                        </span>
                                    </td>
                                    <td><?= $sorumluluk; ?></td>
                                    <td class="text-center"><small class="text-muted"><?= $tarih; ?></small></td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <button type="button" class="btn btn-outline-primary btn-sm fw-bold px-2" 
                                                    onclick="sifreSifirlaModal(<?= $hesap['id']; ?>, '<?= htmlspecialchars($hesap['kullanici_adi']); ?>')">
                                                <i class="fa-solid fa-key me-1"></i>Şifre
                                            </button>
                                            <?php if ($hesap['kullanici_adi'] !== 'admin61'): ?>
                                                <a href="index.php?sayfa=hesap-yonetimi&aksiyon=hesap_sil&id=<?= $hesap['id']; ?>" 
                                                   class="btn btn-outline-danger btn-sm fw-bold px-2"
                                                   onclick="return confirm('<?= htmlspecialchars($hesap['kullanici_adi']); ?> hesabını tamamen silmek istediğinize emin misiniz?');">
                                                    <i class="fa-solid fa-trash-can me-1"></i>Sil
                                                </a>
                                            <?php else: ?>
                                                <span class="badge bg-light text-muted border px-2 py-1"><i class="fa-solid fa-shield-halved me-1"></i>Silinemez</span>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="6" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-users-slash fa-3x mb-3 d-block text-secondary"></i>
                                    Henüz kayıtlı hesap bulunmuyor.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- YENİ HESAP OLUŞTURMA MODALİ -->
<div class="modal fade" id="yeniHesapModal" tabindex="-1" aria-labelledby="yeniHesapModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered modal-lg">
        <div class="modal-content border-0 shadow-lg">
            <form action="index.php?sayfa=hesap-yonetimi" method="POST">
                <div class="modal-header bg-dark text-white">
                    <h5 class="modal-title fw-bold" id="yeniHesapModalLabel">
                        <i class="fa-solid fa-user-plus me-2 text-success"></i>Yeni Panel Hesabı Oluştur
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <div class="row g-3">
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Kullanıcı Adı <span class="text-danger">*</span></label>
                            <input type="text" name="yeni_kullanici_adi" class="form-control" required autocomplete="off" 
                                   placeholder="Örn: il_istanbul" pattern="[a-zA-Z0-9_]+" title="Sadece harf, rakam ve alt çizgi kullanılabilir">
                            <small class="text-muted">Sadece harf, rakam ve alt çizgi (_) kullanın.</small>
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Şifre <span class="text-danger">*</span></label>
                            <input type="text" name="sifre" class="form-control" required autocomplete="new-password" 
                                   placeholder="En az 6 karakter" minlength="6">
                        </div>
                        <div class="col-md-6">
                            <label class="form-label fw-bold">Rol <span class="text-danger">*</span></label>
                            <select name="rol" id="rolSecimi" class="form-select" required onchange="rolDegisti(this.value)">
                                <option value="">— Rol Seçin —</option>
                                <option value="yonetim">Yönetim (Excel/PDF hariç her şey)</option>
                                <option value="il_baskani">İl Başkanı (Sadece kendi ili)</option>
                                <option value="ilce_baskani">İlçe Başkanı (Sadece kendi ilçesi)</option>
                                <option value="kurum_temsilcisi">Kurum Temsilcisi (Sadece kendi kurumu)</option>
                            </select>
                        </div>
                        
                        <!-- Dinamik alanlar -->
                        <div class="col-md-6" id="ilAlani" style="display: none;">
                            <label class="form-label fw-bold">Sorumlu İl <span class="text-danger">*</span></label>
                            <select name="sorumlu_il" class="form-select">
                                <option value="">— İl Seçin —</option>
                                <?php foreach ($iller as $il): ?>
                                    <option value="<?= htmlspecialchars($il); ?>"><?= htmlspecialchars($il); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="ilceIlAlani" style="display: none;">
                            <label class="form-label fw-bold">Önce İl Seçin <span class="text-danger">*</span></label>
                            <select id="ilceIcinIlSec" class="form-select" onchange="ilceListesiDoldur(this.value)">
                                <option value="">— İl Seçin —</option>
                                <?php foreach ($turkiye_illeri as $il): ?>
                                    <option value="<?= htmlspecialchars($il); ?>"><?= htmlspecialchars($il); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-6" id="ilceAlani" style="display: none;">
                            <label class="form-label fw-bold">Sorumlu İlçe <span class="text-danger">*</span></label>
                            <select name="sorumlu_ilce" id="ilceSecimi" class="form-select">
                                <option value="">— Önce il seçin —</option>
                            </select>
                        </div>
                        <div class="col-md-6" id="kurumAlani" style="display: none;">
                            <label class="form-label fw-bold">Sorumlu Kurum <span class="text-danger">*</span></label>
                            <select name="sorumlu_kurum" class="form-select">
                                <option value="">— Kurum Seçin —</option>
                                <?php foreach ($kurumlar as $kurum): ?>
                                    <option value="<?= htmlspecialchars($kurum); ?>"><?= htmlspecialchars($kurum); ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary fw-bold px-3" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="hesap_olustur" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="fa-solid fa-check me-1"></i>Hesabı Oluştur
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<!-- ŞİFRE SIFIRLAMA MODALİ -->
<div class="modal fade" id="sifreSifirlaModal" tabindex="-1" aria-labelledby="sifreSifirlaModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content border-0 shadow-lg">
            <form action="index.php?sayfa=hesap-yonetimi" method="POST">
                <div class="modal-header bg-primary text-white">
                    <h5 class="modal-title fw-bold" id="sifreSifirlaModalLabel">
                        <i class="fa-solid fa-key me-2 text-warning"></i>Şifre Sıfırlama
                    </h5>
                    <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body p-4">
                    <input type="hidden" name="hesap_id" id="sifreHesapId" value="">
                    <p class="text-muted mb-3">
                        <strong id="sifreKullaniciAdi" class="text-dark"></strong> hesabının şifresini değiştirin:
                    </p>
                    <div class="form-group">
                        <label class="form-label fw-bold">Yeni Şifre <span class="text-danger">*</span></label>
                        <input type="text" name="yeni_sifre" class="form-control" required autocomplete="new-password" 
                               placeholder="En az 6 karakter" minlength="6">
                    </div>
                </div>
                <div class="modal-footer bg-light border-top">
                    <button type="button" class="btn btn-secondary fw-bold px-3" data-bs-dismiss="modal">İptal</button>
                    <button type="submit" name="sifre_sifirla" class="btn btn-primary fw-bold px-4 shadow-sm">
                        <i class="fa-solid fa-save me-1"></i>Şifreyi Güncelle
                    </button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
/**
 * Rol seçimine göre dinamik alanları göster/gizle.
 */
// Türkiye il-ilçe verileri (PHP'den aktarılıyor)
var turkiyeIlIlce = <?= json_encode($turkiye_il_ilce, JSON_UNESCAPED_UNICODE); ?>;

function rolDegisti(rol) {
    document.getElementById('ilAlani').style.display = (rol === 'il_baskani') ? 'block' : 'none';
    document.getElementById('ilceIlAlani').style.display = (rol === 'ilce_baskani') ? 'block' : 'none';
    document.getElementById('ilceAlani').style.display = (rol === 'ilce_baskani') ? 'block' : 'none';
    document.getElementById('kurumAlani').style.display = (rol === 'kurum_temsilcisi') ? 'block' : 'none';
    
    // Temizle
    if (rol !== 'ilce_baskani') {
        document.getElementById('ilceIcinIlSec').value = '';
        document.getElementById('ilceSecimi').innerHTML = '<option value="">— Önce il seçin —</option>';
    }
}

/**
 * Seçilen ile göre ilçe dropdown'ını dinamik doldurur.
 */
function ilceListesiDoldur(il) {
    var select = document.getElementById('ilceSecimi');
    select.innerHTML = '<option value="">— İlçe Seçin —</option>';
    
    if (il && turkiyeIlIlce[il]) {
        turkiyeIlIlce[il].forEach(function(ilce) {
            var opt = document.createElement('option');
            opt.value = ilce;
            opt.textContent = ilce;
            select.appendChild(opt);
        });
    }
}

/**
 * Şifre sıfırlama modalını açar.
 */
function sifreSifirlaModal(hesapId, kullaniciAdi) {
    document.getElementById('sifreHesapId').value = hesapId;
    document.getElementById('sifreKullaniciAdi').textContent = kullaniciAdi;
    var modal = new bootstrap.Modal(document.getElementById('sifreSifirlaModal'));
    modal.show();
}
</script>
