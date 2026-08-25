<?php
// Bu dosya inc/uye-detay.php olarak kaydedilecektir.

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo '<div class="alert alert-danger">Geçersiz üye ID parametresi!</div>';
    exit;
}

$uye_id = intval($_GET['id']);

$kullanici_rolu      = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin';
$is_admin            = ($kullanici_rolu === 'admin');

// Roller
$is_yonetim          = ($kullanici_rolu === 'yonetim');
$is_il_baskani       = ($kullanici_rolu === 'il_baskani');
$is_ilce_baskani     = ($kullanici_rolu === 'ilce_baskani');
$is_kurum_temsilcisi = ($kullanici_rolu === 'kurum_temsilcisi');
$is_kisitli_rol      = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi);

// --- GÜNCELLEME: SORUMLU BÖLGE / İLÇE EL İLE GÜNCELLEME MOTORU (SADECE ADMİN) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['bolge_guncelle'])) {
    if (!$is_admin) {
        die("Erişim Engellendi: Sorumlu bölge güncelleme yetkisi sadece sistem yöneticisine (Admin) aittir!");
    }
    $yeni_bolge = trim($_POST['sorumlu_bolge']);
    try {
        $bolge_sorgu = $db_baglanti->prepare("UPDATE dernek_uyeler SET sorumlu_bolge = ? WHERE id = ?");
        $bolge_sorgu->execute([$yeni_bolge ?: null, $uye_id]);
        log_kaydet($db_baglanti, 'uye_duzenle', 'Sorumlu bölge güncellendi: ' . ($yeni_bolge ?: 'boş') . ' (Üye #' . $uye_id . ')', 'dernek_uyeler', $uye_id);
        echo "<script>window.location.href='index.php?sayfa=uye-detay&id=".$uye_id."';</script>";
        exit;
    } catch (\PDOException $e) {
        echo '<div class="alert alert-danger">Bölge güncellenirken hata oluştu: ' . $e->getMessage() . '</div>';
    }
}

// --- ARKA PLANDA ÇALIŞAN SAF JAVASCRIPT / AJAX NOT SİLME MOTORU (DENETÇİYE KAPALI) ---
if (isset($_GET['ajax_islem']) && $_GET['ajax_islem'] === 'ajax_not_sil' && isset($_GET['silinecek_not_id'])) {
    if ($is_kisitli_rol) {
        echo "hata";
        exit;
    }
    if (ob_get_length()) ob_clean();
    header('Content-Type: text/plain; charset=utf-8');
    
    $not_id = intval($_GET['silinecek_not_id']);
    try {
        $not_sil_sorgu = $db_baglanti->prepare("DELETE FROM dernek_notlar WHERE id = ? AND uye_id = ?");
        $durum = $not_sil_sorgu->execute([$not_id, $uye_id]);
        
        if ($durum) {
            echo "ok"; 
        } else {
            echo "hata";
        }
    } catch (\PDOException $e) {
        echo "hata";
    }
    exit;
}

// --- NOT EKLEME MOTORU (DENETÇİYE KAPALI) ---
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['not_ekle'])) {
    if ($is_kisitli_rol) {
        die("Erişim Engellendi: Bu işlemi yapmaya yetkiniz yok!");
    }
    $not_icerik = trim($_POST['not_icerik']);
    if (!empty($not_icerik)) {
        try {
            // Üye adını çek
            $not_uye_sorgu = $db_baglanti->prepare("SELECT adi_soyadi FROM dernek_uyeler WHERE id = ?");
            $not_uye_sorgu->execute([$uye_id]);
            $not_uye_adi = $not_uye_sorgu->fetchColumn() ?: ('Bilinmeyen #' . $uye_id);

            $not_sorgu = $db_baglanti->prepare("INSERT INTO dernek_notlar (uye_id, not_icerik) VALUES (?, ?)");
            $not_sorgu->execute([$uye_id, $not_icerik]);

            $not_ozet = mb_strlen($not_icerik) > 100 ? mb_substr($not_icerik, 0, 100) . '…' : $not_icerik;
            log_kaydet($db_baglanti, 'uye_duzenle', $not_uye_adi . ' — Not eklendi: "' . $not_ozet . '"', 'dernek_notlar', (int) $db_baglanti->lastInsertId());
            echo "<script>window.location.href='index.php?sayfa=uye-detay&id=".$uye_id."';</script>";
            exit;
        } catch (\PDOException $e) {
            echo '<div class="alert alert-danger">Not eklenirken hata oluştu: ' . $e->getMessage() . '</div>';
        }
    }
}

// --- ÜYE BİLGİLERİNİ ÇEK ---
try {
    $uye_sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE id = ?");
    $uye_sorgu->execute([$uye_id]);
    $uye = $uye_sorgu->fetch();

    if (!$uye) {
        echo '<div class="alert alert-warning m-4">Böyle bir üye bulunamadı!</div>';
        exit;
    }

    // Kısıtlı roller için yetki alanı kontrolü
    if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
        if (trim($uye['ikamet_ili']) !== trim($_SESSION['sorumlu_il'])) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu üye sizin yetki alanınız dışındadır.</div></div>';
            exit;
        }
    } elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
        if (trim($uye['trabzon_ilcesi']) !== trim($_SESSION['sorumlu_ilce'])) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu üye sizin yetki alanınız dışındadır.</div></div>';
            exit;
        }
    } elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
        if (trim($uye['kurum']) !== trim($_SESSION['sorumlu_kurum'])) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu üye sizin yetki alanınız dışındadır.</div></div>';
            exit;
        }
    }

    // --- ÜYEYE AİT NOTLARI ÇEK (En yeni not en üstte) ---
    $notlar_sorgu = $db_baglanti->prepare("SELECT * FROM dernek_notlar WHERE uye_id = ? ORDER BY id DESC");
    $notlar_sorgu->execute([$uye_id]);
    $notlar = $notlar_sorgu->fetchAll();
} catch (\PDOException $e) {
    error_log('Yönetim üye detay hatası: ' . $e->getMessage());
    die('Üye bilgileri yüklenirken bir hata oluştu.');
}

// --- STATÜ RENK AYARLARI ---
$statü_ismi = trim($uye['temsilci_turu']);
$rozet_stili = "background-color: #6c757d !important; color: #ffffff !important;"; 

if ($statü_ismi === 'Yönetim Kurulu Üyesi') {
    $rozet_stili = "background-color: #CFE2FF !important; color: #084298 !important; border: 1px solid #b6d4fe;";
} elseif ($statü_ismi === 'İl Başkanı') {
    $rozet_stili = "background-color: #D1E7DD !important; color: #0f5132 !important; border: 1px solid #badbcc;";
} elseif ($statü_ismi === 'İlçe Başkanı') {
    $rozet_stili = "background-color: #f3e5f5 !important; color: #4a148c !important; border: 1px solid #e1bee7;";
} elseif ($statü_ismi === 'Kurum Temsilcisi') {
    $rozet_stili = "background-color: #FFF3CD !important; color: #664d03 !important; border: 1px solid #ffecb5;";
} elseif ($statü_ismi === 'Bölge Koordinatörü') {
    $rozet_stili = "background-color: #e0f7fa !important; color: #00838f !important; border: 1px solid #b2ebf2;";
}

// --- DOĞUM TARİHİ AYARI ---
$dogum_gosterim = "-";
if (!empty($uye['dogum_tarihi']) && $uye['dogum_tarihi'] !== '0000-00-00') {
    $dt = trim($uye['dogum_tarihi']);
    // DD/MM/YYYY veya DD.MM.YYYY → YYYY-MM-DD'ye çevir
    if (preg_match('/^(\d{2})[\/\.](\d{2})[\/\.](\d{4})$/', $dt, $m)) {
        $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
        $dogum_gosterim = $ts ? date('d.m.Y', $ts) : htmlspecialchars($dt);
    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
        $dogum_gosterim = date('d.m.Y', strtotime($dt));
    } else {
        $dogum_gosterim = htmlspecialchars($dt);
    }
} elseif (!empty($uye['dogum_yili'])) {
    $dogum_gosterim = htmlspecialchars($uye['dogum_yili']);
}

// --- ÜYELİK TARİHİ AYARI ---
$uyelik_tarihi_gosterim = "-";
if (!empty($uye['uyelik_tarihi']) && $uye['uyelik_tarihi'] !== '0000-00-00') {
    $uyelik_tarihi_gosterim = date('d.m.Y', strtotime($uye['uyelik_tarihi']));
}
?>

<div class="container py-4">
    
    <div class="d-flex justify-content-between align-items-center mb-4">
        <a href="index.php?sayfa=uyeler" class="btn btn-outline-secondary fw-bold shadow-sm btn-sm">
            <i class="fa-solid fa-arrow-left me-1"></i> Üye Listesine Dön
        </a>
        <span class="badge px-3 py-2 fs-6 shadow-sm rounded-pill" style="<?= $rozet_stili; ?>">
            <i class="fa-solid fa-shield-halved me-1"></i> Statü: <?= htmlspecialchars($uye['temsilci_turu']); ?>
            <?php if(!empty($uye['sorumlu_bolge'])): ?>
                (<?= htmlspecialchars($uye['sorumlu_bolge']); ?>)
            <?php endif; ?>
        </span>
    </div>

    <div class="row g-4">
        
        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100">
                <div class="card-header bg-dark text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-id-card-clip me-2 text-warning"></i>Üye Detay Künyesi</h5>
                </div>
                <div class="card-body p-4">
                    
                    <div class="text-center mb-4 border-bottom pb-3">
                        <div class="bg-light d-inline-flex p-3 rounded-circle border mb-2 text-secondary">
                            <i class="fa-solid fa-user-tie fa-3x"></i>
                        </div>
                        <h3 class="fw-bold text-dark mb-0"><?= htmlspecialchars($uye['adi_soyadi']); ?></h3>
                        <p class="text-muted small mt-1 mb-0">Sistem Kayıt ID: #<?= $uye['id']; ?></p>
                    </div>

                    <table class="table table-striped align-middle fs-6">
                        <tr>
                            <td class="fw-bold text-secondary" style="width: 40%;"><i class="fa-solid fa-phone me-2 text-muted"></i>Telefon:</td>
                            <td class="text-dark fw-semibold"><?= htmlspecialchars($uye['telefon'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-envelope me-2 text-muted"></i>E-Posta:</td>
                            <td><small class="text-dark"><?= htmlspecialchars($uye['eposta'] ?: '-'); ?></small></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-cake-candles me-2 text-muted"></i>Doğum Tarihi:</td>
                            <td class="text-dark fw-semibold"><?= $dogum_gosterim; ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-droplet me-2 text-danger"></i>Kan Grubu:</td>
                            <td class="text-danger fw-bold"><?= htmlspecialchars($uye['kan_grubu'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-map-pin me-2 text-muted"></i>İkamet Ettiği İl:</td>
                            <td class="text-dark fw-bold text-primary"><?= htmlspecialchars($uye['ikamet_ili'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-map-location-dot me-2 text-muted"></i>İkamet İlçesi:</td>
                            <td class="text-dark"><?= htmlspecialchars($uye['ikamet_ilcesi'] ?? '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-tree me-2 text-muted"></i>Trabzon İlçesi:</td>
                            <td class="text-dark"><?= htmlspecialchars($uye['trabzon_ilcesi'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-building me-2 text-muted"></i>Çalıştığı Kurum:</td>
                            <td class="text-dark fw-semibold"><?= htmlspecialchars($uye['kurum'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-user-gear me-2 text-muted"></i>Görev / Ünvan:</td>
                            <td class="text-dark"><?= htmlspecialchars($uye['gorev_unvan'] ?: '-'); ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-calendar-check me-2 text-muted"></i>Üyelik Tarihi:</td>
                            <td class="text-dark fw-bold text-success"><?= $uyelik_tarihi_gosterim; ?></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-business-time me-2 text-muted"></i>Çalışma Şekli:</td>
                            <td class="text-dark"><span class="badge bg-light text-dark border"><?= htmlspecialchars($uye['calisma_sekli'] ?: '-'); ?></span></td>
                        </tr>
                        <tr>
                            <td class="fw-bold text-secondary"><i class="fa-solid fa-location-dot me-2 text-purple"></i>Sorumlu Bölge / İlçe:</td>
                            <td class="text-dark">
                                <span class="fw-bold text-purple me-2" id="mevcutBolgeYazisi"><?= htmlspecialchars($uye['sorumlu_bolge'] ?: 'Atanmamış'); ?></span>
                                <?php if ($is_admin): ?>
                                    <button type="button" class="btn btn-outline-dark btn-sm py-0 px-1.5 fw-bold ms-1" style="font-size:12px;" data-bs-toggle="modal" data-bs-target="#elIleBolgeModal">
                                        <i class="fa-solid fa-pen-to-square"></i> Düzenle
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    </table>

                </div>
            </div>
        </div>

        <div class="col-lg-6">
            <div class="card border-0 shadow-sm rounded-3 h-100 d-flex flex-column">
                <div class="card-header bg-secondary text-white py-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-comment-medical me-2 text-warning"></i>Üye Özel Yönetici Notları</h5>
                </div>
                <div class="card-body p-4 d-flex flex-column flex-grow-1">
                    
                    <?php if (!$is_kisitli_rol): ?>
                        <form action="index.php?sayfa=uye-detay&id=<?= $uye_id; ?>" method="POST" class="mb-4">
                            <div class="input-group">
                                <textarea name="not_icerik" class="form-control" rows="2" placeholder="Üye hakkında bir not veya özel açıklama ekleyin..." required></textarea>
                                <button type="submit" name="not_ekle" class="btn btn-dark fw-bold px-3"><i class="fa-solid fa-plus d-block mb-1"></i>Ekle</button>
                            </div>
                        </form>
                    <?php endif; ?>

                    <h6 class="fw-bold text-dark border-bottom pb-2 mb-3"><i class="fa-solid fa-clock-rotate-left me-1 small text-muted"></i>Geçmiş Notlar (<?= count($notlar); ?>)</h6>
                    
                    <div class="not-akis-alani flex-grow-1" style="max-height: 330px; overflow-y: auto; padding-right: 5px;">
                        <?php if (count($notlar) > 0): ?>
                            <?php foreach ($notlar as $not): ?>
                                <div id="not-kapsayici-<?= $not['id']; ?>" class="bg-light p-3 rounded-3 border mb-3 shadow-sm position-relative transition-not">
                                    
                                    <?php if (!$is_kisitli_rol): ?>
                                        <a href="javascript:void(0);" onclick="notuGörünmezSil(<?= $not['id']; ?>)" class="position-absolute text-danger text-decoration-none btn-not-sil" title="Notu Sil" style="top: 10px; right: 15px; font-size: 1.4rem; font-weight: bold; line-height: 1; cursor: pointer;">
                                            &times;
                                        </a>
                                    <?php endif; ?>
                                    
                                    <p class="text-dark mb-2 pe-4 fs-6" style="white-space: pre-line; word-break: break-all;"><?= htmlspecialchars($not['not_icerik']); ?></p>
                                    
                                    <div class="text-end border-top pt-1">
                                        <small class="text-muted" style="font-size: 0.75rem;"><i class="fa-regular fa-clock me-1"></i><?= date('d.m.Y H:i', strtotime($not['kayit_tarihi'])); ?></small>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-pencil fa-2x mb-2 d-block text-secondary"></i>
                                Bu üyeye ait henüz bir not yazılmamış.
                            </div>
                        <?php endif; ?>
                    </div>

                </div>
            </div>
        </div>

    </div>
</div>

<?php if ($is_admin): ?>
<div class="modal fade" id="elIleBolgeModal" tabindex="-1" aria-labelledby="elIleBolgeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <form action="index.php?sayfa=uye-detay&id=<?= $uye_id; ?>" method="POST">
          <div class="modal-header bg-dark text-white">
            <h5 class="modal-title fw-bold" id="elIleBolgeModalLabel"><i class="fa-solid fa-map-location-dot me-2 text-warning"></i>Görev Bölgesi/İlçesi Ata</h5>
            <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
          </div>
          <div class="modal-body p-4">
            <p class="text-muted small mb-3">Lütfen bu üyenin (Örn: İlçe Başkanı veya Koordinatör ise) dernek adına sorumlu olduğu ilçe veya bölge ismini el ile yazınız:</p>
            <div class="form-group">
                <label class="fw-bold mb-1 text-dark">Sorumlu Olduğu Yer:</label>
                <input type="text" name="sorumlu_bolge" class="form-control" placeholder="Örn: Üsküdar, Kadıköy veya Marmara Bölgesi" value="<?= htmlspecialchars($uye['sorumlu_bolge']); ?>" autocomplete="off">
                <small class="text-muted mt-1 d-block">Eğer görevi iptal etmek veya temizlemek isterseniz kutuyu boş bırakıp güncelleyin.</small>
            </div>
          </div>
          <div class="modal-content-footer p-3 border-top text-end bg-light rounded-bottom">
            <button type="button" class="btn btn-secondary btn-sm fw-bold px-3" data-bs-dismiss="modal">İptal</button>
            <button type="submit" name="bolge_guncelle" class="btn btn-success btn-sm fw-bold px-3 shadow-sm">Kaydet ve Güncelle</button>
          </div>
      </form>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
function notuGörünmezSil(notId) {
    <?php if (!$is_kisitli_rol): ?>
    if (confirm('Bu notu tamamen silmek istediğinize emin misiniz?')) {
        let url = 'index.php?sayfa=uye-detay&id=<?= $uye_id; ?>&ajax_islem=ajax_not_sil&silinecek_not_id=' + notId;
        
        fetch(url)
            .then(response => response.text())
            .then(sonuc => {
                if (sonuc.trim().includes("ok")) {
                    let element = document.getElementById('not-kapsayici-' + notId);
                    if (element) {
                        element.style.transition = "all 0.3s ease";
                        element.style.opacity = "0";
                        element.style.transform = "translateX(50px)";
                        setTimeout(() => {
                            element.remove();
                            window.location.reload();
                        }, 300);
                    }
                } else {
                    alert('Not silinirken sistemsel bir hata oluştu!');
                }
            })
            .catch(error => {
                alert('Bağlantı hatası oluştu!');
            });
    }
    <?php endif; ?>
}
</script>

<style>
.not-akis-alani::-webkit-scrollbar { width: 5px; }
.not-akis-alani::-webkit-scrollbar-track { background: #f1f1f1; }
.not-akis-alani::-webkit-scrollbar-thumb { background: #ccc; border-radius: 10px; }
.btn-not-sil { transition: transform 0.1s, color 0.1s; display: inline-block; }
.btn-not-sil:hover { color: #b30000 !important; transform: scale(1.25); text-decoration: none !important; }
.transition-not { transition: all 0.2s; }
.transition-not:hover { background-color: #f8f9fa !important; border-color: #bbb !important; }
.text-purple { color: #6a1b9a !important; }
</style>