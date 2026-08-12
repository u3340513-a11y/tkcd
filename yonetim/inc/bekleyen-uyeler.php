<?php
// Bu dosya inc/bekleyen-uyeler.php olarak kaydedilecektir.

$mesaj = "";
$mesaj_turu = "";

$kullanici_rolu = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin';
$is_moderator = ($kullanici_rolu === 'moderator');

// Yeni roller
$is_il_baskani        = ($kullanici_rolu === 'il_baskani');
$is_ilce_baskani      = ($kullanici_rolu === 'ilce_baskani');
$is_kurum_temsilcisi  = ($kullanici_rolu === 'kurum_temsilcisi');
$is_kisitli_rol       = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi);

// --- BAŞVURU ONAYLAMA MOTORU ---
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'basvuru_onayla' && isset($_GET['id'])) {
    $uye_id = intval($_GET['id']);
    try {
        // 1. ADIM: Onaylanacak üyenin telefon numarasını bekleyen kayıttan çekiyoruz
        $uye_bul = $db_baglanti->prepare("SELECT telefon FROM dernek_uyeler WHERE id = ?");
        $uye_bul->execute([$uye_id]);
        $mevcut_basvuru = $uye_bul->fetch(PDO::FETCH_ASSOC);

        if ($mevcut_basvuru) {
            $telefon = trim($mevcut_basvuru['telefon']);

            // 2. ADIM: Eğer telefon numarası girilmişse, AKTİF/ONAYLI üyeler arasında var mı kontrol ediyoruz
            if (!empty($telefon)) {
                $kontrol_sorgu = $db_baglanti->prepare("SELECT COUNT(*) FROM dernek_uyeler WHERE telefon = ? AND onay_durumu = 'onayli' AND id != ?");
                $kontrol_sorgu->execute([$telefon, $uye_id]);
                $var_mi = $kontrol_sorgu->fetchColumn();

                if ($var_mi > 0) {
                    // MÜKERRER KİLİDİ: Hatayı URL üzerinden sayfaya basıyoruz.
                    echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=mukerrer_hata&hata_tel=".$telefon."';</script>";
                    exit;
                }
            }

            // GÜNCELLEME: Eğer üyenin üyelik tarihi boş veya 0000-00-00 ise, onaylandığı anın tarihini (CURDATE()) yazar.
            $sql_onay = "UPDATE dernek_uyeler SET 
                         onay_durumu = 'onayli',
                         uyelik_tarihi = IF(uyelik_tarihi IS NULL OR uyelik_tarihi = '0000-00-00', CURDATE(), uyelik_tarihi)
                         WHERE id = ?";
                         
            $onay_sorgu = $db_baglanti->prepare($sql_onay);
            $durum = $onay_sorgu->execute([$uye_id]);
            if ($durum) {
                echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=onaylandi';</script>";
                exit;
            }
        }
    } catch (\PDOException $e) {
        $mesaj = "Onaylama Hatası: " . $e->getMessage();
        $mesaj_turu = "danger";
    }
}

// --- BAŞVURU REDDETME / SİLME MOTORU (MODERATÖRE KAPALI) ---
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'basvuru_reddet' && isset($_GET['id'])) {
    if ($is_moderator) {
        die("Erişim Engellendi: Moderatör rolü ile başvuru reddetme yetkiniz bulunmamaktadır.");
    }
    $uye_id = intval($_GET['id']);
    try {
        $red_sorgu = $db_baglanti->prepare("DELETE FROM dernek_uyeler WHERE id = ? AND onay_durumu = 'bekleyen'");
        $durum = $red_sorgu->execute([$uye_id]);
        if ($durum) {
            echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=reddedildi';</script>";
            exit;
        }
    } catch (\PDOException $e) {
        $mesaj = "Reddetme Hatası: " . $e->getMessage();
        $mesaj_turu = "danger";
    }
}

// URL'den gelen başarı veya hata bildirim mesajları
if (isset($_GET['mesaj_durum'])) {
    if ($_GET['mesaj_durum'] === 'onaylandi') {
        $mesaj = "Başvuru başarıyla onaylandı ve aktif üye listesine eklendi!";
        $mesaj_turu = "success";
    } elseif ($_GET['mesaj_durum'] === 'reddedildi') {
        $mesaj = "Başvuru başarıyla reddedildi ve sistemden silindi.";
        $mesaj_turu = "warning";
    } elseif ($_GET['mesaj_durum'] === 'mukerrer_hata') {
        $hata_tel = isset($_GET['hata_tel']) ? htmlspecialchars($_GET['hata_tel']) : '';
        $mesaj = "HATA: [" . $hata_tel . "] telefon numarası ile zaten aktif bir üye sistemde kayıtlıdır! Bu başvuru onaylanamaz.";
        $mesaj_turu = "danger";
    }
}

// Bekleyen başvuruları çekiyoruz
try {
    $sorgu = $db_baglanti->query("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'bekleyen' ORDER BY id DESC");
    $bekleyenler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('Yönetim bekleyen üyeler hatası: ' . $e->getMessage());
    die('Bekleyen başvuru verileri yüklenirken bir hata oluştu.');
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start">
            <h2 class="fw-bold text-danger mb-1"><i class="fa-solid fa-user-clock me-2"></i>Bekleyen Üyelik Başvuruları</h2>
            <p class="text-muted mb-1">Web sitesindeki form üzerinden derneğe başvuru yapmış ancak henüz onaylanmamış adaylar.</p>
            
            <!-- GÜNCELLEME: İnce kırmızı çizgili, soluk kırmızı arka planlı pürüzsüz uyarı kutusu alanı -->
            <?php if (!empty($mesaj) && $mesaj_turu === 'danger'): ?>
                <div class="mt-3 p-3 rounded-3 d-inline-block animate__animated animate__headShake" style="background-color: #f8d7da; border: 2px solid #dc3545; color: #842029; font-size: 14px; font-weight: bold; box-shadow: 0 .125rem .25rem rgba(0,0,0,.075) !important;">
                    <i class="fa-solid fa-circle-exclamation me-2 fs-5" style="vertical-align: middle;"></i> <?= $mesaj; ?>
                </div>
            <?php endif; ?>
        </div>
    </div>

    <!-- Başarı veya Silme Bildirimleri (Yeşil ve Turuncu Şeritler) -->
    <?php if (!empty($mesaj) && $mesaj_turu !== 'danger'): ?>
        <div class="alert alert-<?= $mesaj_turu; ?> alert-dismissible fade show shadow-sm mb-4" role="alert" style="font-size: 14px; padding: 10px 15px;">
            <strong><i class="fa-solid fa-circle-info me-2"></i></strong> <?= $mesaj; ?>
            <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close" style="padding: 12px;"></button>
        </div>
    <?php endif; ?>

    <div class="card border-0 shadow-sm rounded-3">
        <div class="card-body p-0">
            <div class="table-responsive">
                <table class="table table-hover align-middle mb-0" style="min-width: 1100px;">
                    <thead class="table-danger">
                        <tr>
                            <th class="ps-4">Adı Soyadı</th>
                            <th>Telefon</th>
                            <th>E-Posta</th>
                            <th>Kan Grubu</th>
                            <th>Doğum Tarihi</th>
                            <th>İl / İlçe</th>
                            <th>Kurum / Ünvan</th>
                            <th>Çalışma Şekli</th>
                            <th class="text-center" style="width: 180px;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($bekleyenler) > 0): ?>
                            <?php foreach ($bekleyenler as $b): ?>
                                <?php 
                                // --- Gelişmiş Kadın İsim Filtreleme Motoru (Semra ve Diğer İsimler Eklendi) ---
                                $parcalar = explode(' ', trim($b['adi_soyadi']));
                                $ilk_isim = mb_strtoupper($parcalar[0], 'UTF-8');
                                
                                $kadin_isimleri = ['SEMRA', 'AYŞEGÜL', 'BEGÜM', 'HATİCE', 'FATMA', 'AYŞE', 'EMİNE', 'ZEYNEP', 'MERYEM', 'ELİF', 'HÜLYA', 'GAMZE', 'MERVE', 'BÜŞRA', 'ESRA', 'SEDA', 'DERYA', 'KÜBRA', 'ASLI', 'PELİN', 'TUĞBA', 'DEMET', 'ÖZLEM', 'SİNEM', 'GÜL', 'NUR', 'MELİS', 'DİLAN', 'BURCU', 'CANAN', 'SULTAN', 'MELİKE', 'YASEMİN', 'EDA', 'BERNA', 'SELEN', 'PINAR', 'BANU', 'YEŞİM', 'EBRU', 'FADİME', 'NURAN', 'SELMA', 'DİLEK', 'FİLİZ', 'ARZU', 'LEYLA', 'SİBEL', 'HALE', 'JALE', 'GONCA', 'MÜGE', 'NESLİHAN', 'NAZLI', 'MİNE', 'SELİN', 'ESMA', 'FAZİLET', 'NESRİN', 'REYHAN', 'AHSEN', 'İPEK', 'ÖZGE', 'GÜLAY', 'SÜREYYA', 'DİDEM', 'Handan', 'NURTEN', 'ŞERİFE', 'SABİHA', 'ZEHRA', 'ÜMMÜHAN', 'RABİA', 'BÜŞRANUR', 'FATMANUR', 'GÜLSÜM', 'KÜBRANUR', 'ŞEYMA', 'BETÜL', 'SÜMEYYE', 'KADRİYE', 'HAVVA', 'SONGÜL', 'DÖNDÜ', 'NURAY', 'FİRDEVS', 'AYTEN', 'AYSEL', 'GÜLER', 'NURSEL', 'NURCAN', 'MELEK', 'FİLİZ', 'NURHAN', 'PERİHAN', 'SUZAN', 'SUNA', 'ŞENNUR', 'İLKAY', 'GÜLDEN', 'İLK_NUR', 'GÜLŞAH', 'AŞKIN', 'SEVAL', 'SEVİL', 'SEVİM', 'NİHAL', 'NİLÜFER', 'NİLAY', 'NURSEl', 'MELTEM'];
                                
                                if (in_array($ilk_isim, $kadin_isimleri)) {
                                    $ikon_style = 'color: #e83e8c !important;'; 
                                    $ikon_sekil = 'fa-user-nurse';
                                } else {
                                    $ikon_style = 'color: #007bff !important;'; 
                                    $ikon_sekil = 'fa-user';
                                }

                                $kan = !empty($b['kan_grubu']) ? $b['kan_grubu'] : '-';
                                $dogum = !empty($b['dogum_tarihi']) ? $b['dogum_tarihi'] : (!empty($b['dogum_yili']) ? $b['dogum_yili'] : '-');
                                ?>
                                <tr>
                                    <td class="ps-4 fw-bold">
                                        <i class="fa-solid <?= $ikon_sekil; ?> me-2" style="<?= $ikon_style; ?>"></i>
                                        <?= htmlspecialchars($b['adi_soyadi']); ?>
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 500;"><?= htmlspecialchars($b['telefon'] ?: '-'); ?></td>
                                    <td><small><?= htmlspecialchars($b['eposta'] ?: '-'); ?></small></td>
                                    <td><span class="badge bg-danger text-white"><?= htmlspecialchars($kan); ?></span></td>
                                    <td><small><?= htmlspecialchars($dogum); ?></small></td>
                                    <td>
                                        <strong><?= htmlspecialchars($b['ikamet_ili'] ?: '-'); ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars($b['trabzon_ilcesi'] ?: '-'); ?></small>
                                    </td>
                                    <td>
                                        <?= htmlspecialchars($b['kurum'] ?: '-'); ?>
                                        <br><small class="text-muted"><?= htmlspecialchars($b['gorev_unvan'] ?: '-'); ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($b['calisma_sekli'] ?: '-'); ?></small></td>
                                    <td class="text-center pe-4">
                                        <div class="d-flex gap-2 justify-content-center">
                                            <a href="index.php?sayfa=bekleyen-uyeler&aksiyon=basvuru_onayla&id=<?= $b['id']; ?>" class="btn btn-success btn-sm fw-bold px-2.5 shadow-sm" onclick="return confirm('<?= htmlspecialchars($b['adi_soyadi']); ?> isimli adayı derneğe üye olarak onaylıyor musunuz?');">
                                                <i class="fa-solid fa-user-check me-1"></i> Onayla
                                            </a>
                                            <?php if (!$is_moderator && !$is_kisitli_rol): ?>
                                                <a href="index.php?sayfa=bekleyen-uyeler&aksiyon=basvuru_reddet&id=<?= $b['id']; ?>" class="btn btn-outline-danger btn-sm fw-bold px-2.5 shadow-sm" onclick="return confirm('<?= htmlspecialchars($b['adi_soyadi']); ?> isimli başvuruyu tamamen silmek istediğinize emin misiniz?');">
                                                    <i class="fa-solid fa-user-xmark me-1"></i> Reddet
                                                </a>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr>
                                <td colspan="9" class="text-center py-5 text-muted">
                                    <i class="fa-solid fa-envelope-open-text fa-3x mb-3 d-block text-secondary"></i>
                                    Şu anda onay bekleyen yeni bir üyelik başvurusu bulunmuyor.
                                </td>
                            </tr>
                        <?php endif; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>