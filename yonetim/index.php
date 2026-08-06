<?php

/**
 * Yönetim Paneli ana giriş noktası.
 *
 * Güvenlik katmanları:
 *   1. Session güvenliği (baglan.php'de merkezi)
 *   2. Login sonrası session_regenerate_id() — session fixation koruması
 *   3. IP bazlı brute-force koruması (5 deneme / 15 dk)
 *   4. Hata mesajlarında detay sızıntısı engellendi
 *   5. Oturum zaman aşımı 30 dk (baglan.php'de)
 */

require_once 'inc/baglan.php';

$hata_mesaji = "";

// ─── ÇIKIŞ İŞLEMİ ──────────────────────────────────────────────────────
if (isset($_GET['islem']) && $_GET['islem'] === 'cikis') {
    $_SESSION = [];
    session_destroy();
    header("Location: /yonetim/");
    exit;
}

// ─── BRUTE-FORCE KORUMASI ───────────────────────────────────────────────
/**
 * IP bazlı login deneme sayacı.
 *
 * Neden dosya tabanlı: Yönetim paneli Composer/framework kullanmıyor;
 * DB'de ayrı bir tablo oluşturmak yerine basit bir dosya tabanlı
 * mekanizma yeterli ve bağımsız çalışır.
 *
 * Yapı: storage/security/login_attempts/ altında IP bazlı JSON dosyaları.
 */
$guvenlikDizini = dirname(__DIR__, 1) . '/../storage/security/login_attempts';
if (!is_dir($guvenlikDizini)) {
    @mkdir($guvenlikDizini, 0755, true);
}

/**
 * Belirtilen IP'nin kilitli olup olmadığını kontrol eder.
 *
 * @param string $ip        İstemci IP adresi
 * @param string $dizin     Depolama dizini
 * @param int    $maxDeneme Maksimum başarısız deneme (varsayılan: 5)
 * @param int    $kilitSure  Kilit süresi saniye cinsinden (varsayılan: 900 = 15 dk)
 * @return bool true ise IP kilitli, giriş engellenmeli
 */
function login_kilitli_mi(string $ip, string $dizin, int $maxDeneme = 5, int $kilitSure = 900): bool
{
    $dosya = $dizin . '/' . md5($ip) . '.json';
    if (!is_file($dosya)) {
        return false;
    }
    $veri = json_decode((string) file_get_contents($dosya), true);
    if (!is_array($veri)) {
        return false;
    }
    // Kilit süresi dolmuşsa dosyayı sil
    if (isset($veri['son_deneme']) && (time() - $veri['son_deneme']) > $kilitSure) {
        @unlink($dosya);
        return false;
    }
    return ($veri['deneme'] ?? 0) >= $maxDeneme;
}

/**
 * Başarısız login denemesini kaydeder.
 */
function login_basarisiz_kaydet(string $ip, string $dizin): void
{
    $dosya = $dizin . '/' . md5($ip) . '.json';
    $veri = ['deneme' => 0, 'son_deneme' => time()];
    if (is_file($dosya)) {
        $okunan = json_decode((string) file_get_contents($dosya), true);
        if (is_array($okunan)) {
            $veri = $okunan;
        }
    }
    $veri['deneme'] = ($veri['deneme'] ?? 0) + 1;
    $veri['son_deneme'] = time();
    file_put_contents($dosya, json_encode($veri), LOCK_EX);
}

/**
 * Başarılı login sonrası deneme sayacını sıfırlar.
 */
function login_basarili_temizle(string $ip, string $dizin): void
{
    $dosya = $dizin . '/' . md5($ip) . '.json';
    if (is_file($dosya)) {
        @unlink($dosya);
    }
}

$istemciIp = $_SERVER['REMOTE_ADDR'] ?? '0.0.0.0';

// ─── LOGIN İŞLEMİ ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kullanici_adi'])) {

    // Brute-force kontrolü
    if (login_kilitli_mi($istemciIp, $guvenlikDizini)) {
        $hata_mesaji = "Çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.";
    } else {
        $kullanici = trim($_POST['kullanici_adi']);
        $sifre     = trim($_POST['sifre']);

        if (!empty($kullanici) && !empty($sifre)) {
            $sorgu = $db_baglanti->prepare("SELECT id, kullanici_adi, sifre, rol FROM dernek_yoneticiler WHERE kullanici_adi = ?");
            $sorgu->execute([$kullanici]);
            $user = $sorgu->fetch();

            if ($user && password_verify($sifre, $user['sifre'])) {
                // Session fixation koruması: login sonrası yeni session ID üret
                session_regenerate_id(true);

                $_SESSION['oturum'] = true;
                $_SESSION['id'] = $user['id'];
                $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
                $_SESSION['rol'] = $user['rol'] ?? 'admin';
                $_SESSION['son_aktivite'] = time();

                // Başarılı giriş: deneme sayacını temizle
                login_basarili_temizle($istemciIp, $guvenlikDizini);

                header("Location: /yonetim/");
                exit;
            } else {
                // Başarısız giriş: sayacı artır
                login_basarisiz_kaydet($istemciIp, $guvenlikDizini);
                $hata_mesaji = "Kullanıcı adı veya şifre hatalı!";
            }
        }
    }
}

// ─── OTURUM KONTROLÜ ────────────────────────────────────────────────────
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    include 'inc/login_form.php';
    exit;
}

// ─── ROL KONTROLLERİ ───────────────────────────────────────────────────
$kullanici_rolu = $_SESSION['rol'] ?? 'admin';
$is_denetci   = ($kullanici_rolu === 'denetci');
$is_moderator = ($kullanici_rolu === 'moderator');

$sayfa = isset($_GET['sayfa']) ? trim($_GET['sayfa']) : 'dashboard';

include 'inc/header.php';
include 'inc/navbar.php';

switch ($sayfa) {
    case 'uyeler':
        $aktif_filtre = isset($_GET['filtre']) ? trim($_GET['filtre']) : '';
        $izinli_filtreler = ['yonetim_kurulu', 'bolge_koordinatoru', 'il_baskani', 'ilce_baskani', 'kurum_temsilcisi'];
        
        if ($is_denetci && (empty($aktif_filtre) || !in_array($aktif_filtre, $izinli_filtreler))) {
            echo '<div class="container py-5"><div class="alert alert-warning text-center fw-bold"><i class="fa-solid fa-triangle-exclamation me-2"></i>Bu filtreyi veya genel liste görünümünü inceleme yetkiniz bulunmamaktadır.</div></div>';
        } else {
            include 'inc/uyeler.php';
        }
        break;
        
    case 'uye-ekle':
        if ($is_denetci) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Üye ekleme yetkiniz bulunmamaktadır.</div></div>';
        } else {
            include 'inc/uye-ekle.php';
        }
        break;

    case 'uye-detay':
        include 'inc/uye-detay.php';
        break;

    case 'bekleyen-uyeler':
        if ($is_denetci) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bekleyen başvuruları inceleme yetkiniz bulunmamaktadır.</div></div>';
        } else {
            include 'inc/bekleyen-uyeler.php';
        }
        break;
        
    case 'dashboard':
    default:
        try {
            $toplam_uye = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli'")->fetchColumn();
            $toplam_il  = $db_baglanti->query("SELECT COUNT(DISTINCT ikamet_ili) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != ''")->fetchColumn();
            
            $yonetim_kurulu = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu LIKE '%Yönetim Kurulu%' OR temsilci_turu = 'Yönetici' OR ek_gorev LIKE '%Yönetim Kurulu%' OR ek_gorev = 'Yönetici')")->fetchColumn();
            $bolge_koordinatorleri = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')")->fetchColumn();
            $il_baskanlari = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi')")->fetchColumn();
            $ilce_baskanlari = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi')")->fetchColumn();
            $kurum_temsilcileri = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')")->fetchColumn();

            $bekleyen_uye_sayisi = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'bekleyen'")->fetchColumn();

            $ilce_sorgu = $db_baglanti->query("SELECT trabzon_ilcesi, COUNT(*) as adet 
                                               FROM dernek_uyeler 
                                               WHERE onay_durumu = 'onayli' AND trabzon_ilcesi IS NOT NULL AND trabzon_ilcesi != '' 
                                               GROUP BY trabzon_ilcesi 
                                               ORDER BY adet DESC");
            $ilce_verileri = $ilce_sorgu->fetchAll();

            $grafik_ekseni = [];
            $grafik_sayilari = [];
            foreach ($ilce_verileri as $veri) {
                $grafik_ekseni[] = $veri['trabzon_ilcesi'];
                $grafik_sayilari[] = intval($veri['adet']);
            }
        } catch (\PDOException $e) {
            error_log('Yönetim dashboard hatası: ' . $e->getMessage());
            echo '<div class="container py-5"><div class="alert alert-danger">İstatistikler yüklenirken bir hata oluştu.</div></div>';
            include 'inc/footer.php';
            exit;
        }
        ?>
        <div class="container-fluid py-4 px-md-4">
            <div class="row mb-4">
                <div class="col-12 text-center text-md-start">
                    <h2 class="fw-bold text-dark mb-1">Kontrol Paneli </h2>
                    <p class="text-muted">Derneğe ait güncel istatistikler ve genel özet aşağıda listelenmiştir.</p>
                </div>
            </div>

            <div class="row row-cols-1 row-cols-sm-2 row-cols-md-2 row-cols-xl-4 g-4 mb-4">
                
                <!-- Toplam Üye (Kart görünüyor, denetçi için sayı gizli, Moderatör ve Admin görür) -->
                <div class="col">
                    <?php if (!$is_denetci): ?>
                        <a href="index.php?sayfa=uyeler" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <?php endif; ?>
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-primary border-5 shadow-sm" style="<?= $is_denetci ? 'cursor: default;' : 'cursor: pointer;'; ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Toplam Üye</h6>
                                    <h2 class="fw-bold mb-0 text-dark">
                                        <?= $is_denetci ? '<span class="text-muted fs-4" title="Gizli Veri">***</span>' : $toplam_uye; ?>
                                    </h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded text-primary"><i class="fa-solid fa-users fa-xl"></i></div>
                            </div>
                        </div>
                    <?php if (!$is_denetci): ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Aktif İller (Denetçiye Kapalı, Moderatör ve Admin Açık) -->
                <div class="col">
                    <?php if (!$is_denetci): ?>
                        <a href="index.php?sayfa=uyeler&filtre=aktif_iller" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                    <?php endif; ?>
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-danger border-5 shadow-sm" style="<?= $is_denetci ? 'cursor: default;' : 'cursor: pointer;'; ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Aktif İller</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $toplam_il; ?></h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded text-danger"><i class="fa-solid fa-map-location-dot fa-xl"></i></div>
                            </div>
                        </div>
                    <?php if (!$is_denetci): ?>
                        </a>
                    <?php endif; ?>
                </div>

                <!-- Yönetim Kurulu (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=yonetim_kurulu" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-5 shadow-sm" style="border-left-color: #0d6efd !important; cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-primary text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Yönetim Kurulu</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $yonetim_kurulu; ?></h2>
                                </div>
                                <div class="p-3 rounded" style="background-color: rgba(13, 110, 253, 0.1); color: #0d6efd;"><i class="fa-solid fa-user-shield fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Bölge Koordinatörü (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=bolge_koordinatoru" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-5 shadow-sm" style="border-left-color: #00838f !important; cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase small fw-bold mb-1" style="font-size: 0.75rem; color: #00838f;">Bölge Koordinatörü</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $bolge_koordinatorleri; ?></h2>
                                </div>
                                <div class="p-3 rounded" style="background-color: rgba(0, 131, 143, 0.1); color: #00838f;"><i class="fa-solid fa-earth-americas fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- İl Başkanları (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=il_baskani" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-success border-5 shadow-sm" style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-success text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">İl Başkanları</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $il_baskanlari; ?></h2>
                                </div>
                                <div class="bg-success bg-opacity-10 p-3 rounded text-success"><i class="fa-solid fa-building-flag fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- İlçe Başkanları (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=ilce_baskani" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-5 shadow-sm" style="border-left-color: #6a1b9a !important; cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase small fw-bold mb-1" style="font-size: 0.75rem; color: #6a1b9a;">İlçe Başkanları</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $ilce_baskanlari; ?></h2>
                                </div>
                                <div class="p-3 rounded" style="background-color: rgba(106, 27, 154, 0.1); color: #6a1b9a;"><i class="fa-solid fa-route fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Kurum Temsilcileri (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=kurum_temsilcisi" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-warning border-5 shadow-sm" style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-warning text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Kurum Temsilcileri</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $kurum_temsilcileri; ?></h2>
                                </div>
                                <div class="bg-warning bg-opacity-10 p-3 rounded text-warning"><i class="fa-solid fa-building-user fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Bekleyen Başvuru (Denetçiye Kapalı, Moderatör ve Admin Açık) -->
                <div class="col">
                    <?php if (!$is_denetci): ?>
                        <a href="index.php?sayfa=bekleyen-uyeler" class="text-decoration-none d-block">
                    <?php endif; ?>
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-danger border-5 shadow-sm position-relative" style="transition: transform 0.2s; <?= $is_denetci ? 'cursor: default;' : 'cursor: pointer;'; ?>" onmouseover="<?= $is_denetci ? '' : "this.style.transform='translateY(-3px)'"; ?>" onmouseout="<?= $is_denetci ? '' : "this.style.transform='translateY(0)'"; ?>">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-danger text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Bekleyen Başvuru</h6>
                                    <h2 class="fw-bold mb-0 text-danger"><?= $bekleyen_uye_sayisi; ?></h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded text-danger"><i class="fa-solid fa-user-clock fa-xl"></i></div>
                            </div>
                            <?php if($bekleyen_uye_sayisi > 0): ?>
                                <span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger" style="margin-left: -15px; margin-top: 5px;">Yeni</span>
                            <?php endif; ?>
                        </div>
                    <?php if (!$is_denetci): ?>
                        </a>
                    <?php endif; ?>
                </div>

            </div>

            <div class="row g-4">
                <div class="col-lg-7">
                    <div class="bg-white p-4 rounded shadow-sm border h-100">
                        <h5 class="fw-bold text-dark mb-1"><i class="fa-solid fa-chart-pie me-2 text-danger"></i>Trabzon İlçe Dağılımı</h5>
                        <p class="text-muted small mb-3">Kayıtlı üyelerin Trabzon ilçelerine göre dağılım grafiği.</p>
                        
                        <?php if(count($ilce_verileri) > 0): ?>
                            <div class="d-flex justify-content-center align-items-center mb-2" style="position: relative; height: 280px; width: 100%;">
                                <canvas id="ilcePastaGrafik"></canvas>
                            </div>
                        <?php else: ?>
                            <div class="text-center py-5 text-muted">
                                <i class="fa-solid fa-chart-pie fa-2x d-block mb-2 text-secondary"></i>
                                Grafik oluşturulabilmesi için henüz ilçesi girilmiş bir üye bulunmuyor.
                            </div>
                        <?php endif; ?>
                    </div>
                </div>

                <div class="col-lg-5">
                    <div class="bg-white p-4 rounded shadow-sm border text-center h-100 d-flex flex-column justify-content-center align-items-center">
                        <img src="assets/logo.webp" alt="Logo" width="80" class="img-fluid mb-3">
                        <h4 class="fw-bold text-dark">T.K.Ç.D. Yönetim Paneli</h4>
                        <p class="text-muted small px-3 mb-4">Sistem genelindeki verilere ve üyelere yukarıdaki menüyü kullanarak anında erişebilirsiniz.</p>
                        
                        <?php if (!$is_denetci): ?>
                            <div class="d-flex gap-2">
                                <a href="index.php?sayfa=bekleyen-uyeler" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm"><i class="fa-solid fa-user-clock me-1"></i>Başvuruları İncele</a>
                                <?php if (!$is_moderator): ?>
                                    <a href="index.php?sayfa=uye-ekle" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm"><i class="fa-solid fa-user-plus me-1"></i>Üye Ekle</a>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

        <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
        <script>
        window.addEventListener("load", function() {
            var canvasElement = document.getElementById('ilcePastaGrafik');
            if(canvasElement) {
                var etiketler = <?= json_encode($grafik_ekseni); ?>;
                var veriler = <?= json_encode($grafik_sayilari); ?>;

                var renkler = [
                    '#b30000', '#006699', '#2e7d32', '#ef6c00', '#6a1b9a',
                    '#4e342e', '#c2185b', '#37474f', '#9e9d24', '#00838f',
                    '#1565c0', '#d84315', '#00695c', '#ad1457', '#558b2f'
                ];

                new Chart(canvasElement, {
                    type: 'pie',
                    data: {
                        labels: etiketler,
                        datasets: [{
                            data: veriler,
                            backgroundColor: renkler.slice(0, etiketler.length),
                            borderWidth: 2,
                            borderColor: '#ffffff'
                        }]
                    },
                    options: {
                        responsive: true,
                        maintainAspectRatio: false,
                        plugins: {
                            legend: {
                                display: true,
                                position: 'right',
                                labels: { boxWidth: 15, font: { size: 12, weight: 'bold' }, color: '#333333' }
                            }
                        }
                    }
                });
            }
        });
        </script>
        <?php
        break;
}

include 'inc/footer.php';
?>