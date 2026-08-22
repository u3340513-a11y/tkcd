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
 * Kullanıcı adı bazlı login deneme sayacı.
 *
 * Neden dosya tabanlı: Yönetim paneli Composer/framework kullanmıyor;
 * DB'de ayrı bir tablo oluşturmak yerine basit bir dosya tabanlı
 * mekanizma yeterli ve bağımsız çalışır.
 *
 * Yapı: storage/security/login_attempts_user/ altında kullanıcı bazlı JSON dosyaları.
 * Her kullanıcı kendi kilit sayacına sahiptir, bir kullanıcının hatalı
 * girişleri diğer kullanıcıları etkilemez.
 */
$guvenlikDizini = __DIR__ . '/../storage/security/login_attempts_user';
if (!is_dir($guvenlikDizini)) {
    @mkdir($guvenlikDizini, 0755, true);
}

/**
 * Belirtilen kullanıcının kilitli olup olmadığını kontrol eder.
 *
 * @param string $kullaniciAdi Giriş yapılmaya çalışılan kullanıcı adı
 * @param string $dizin        Depolama dizini
 * @param int    $maxDeneme    Maksimum başarısız deneme (varsayılan: 5)
 * @param int    $kilitSure    Kilit süresi saniye cinsinden (varsayılan: 900 = 15 dk)
 * @return bool true ise hesap kilitli, giriş engellenmeli
 */
function login_kilitli_mi(string $kullaniciAdi, string $dizin, int $maxDeneme = 5, int $kilitSure = 900): bool
{
    $dosya = $dizin . '/' . md5($kullaniciAdi) . '.json';
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
 *
 * @param string $kullaniciAdi Hatalı giriş yapılan kullanıcı adı
 * @param string $dizin        Depolama dizini
 */
function login_basarisiz_kaydet(string $kullaniciAdi, string $dizin): void
{
    $dosya = $dizin . '/' . md5($kullaniciAdi) . '.json';
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
 *
 * @param string $kullaniciAdi Başarılı giriş yapılan kullanıcı adı
 * @param string $dizin        Depolama dizini
 */
function login_basarili_temizle(string $kullaniciAdi, string $dizin): void
{
    $dosya = $dizin . '/' . md5($kullaniciAdi) . '.json';
    if (is_file($dosya)) {
        @unlink($dosya);
    }
}

// ─── LOGIN İŞLEMİ ──────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kullanici_adi'])) {

    $kullanici = trim($_POST['kullanici_adi']);
    $sifre     = trim($_POST['sifre']);

    // Brute-force kontrolü: kullanıcı adı bazlı
    if (!empty($kullanici) && login_kilitli_mi($kullanici, $guvenlikDizini)) {
        $hata_mesaji = "Bu hesap için çok fazla başarısız giriş denemesi. Lütfen 15 dakika sonra tekrar deneyin.";
    } elseif (!empty($kullanici) && !empty($sifre)) {
        $sorgu = $db_baglanti->prepare("SELECT id, kullanici_adi, sifre, rol, sorumlu_il, sorumlu_ilce, sorumlu_kurum FROM dernek_yoneticiler WHERE kullanici_adi = ?");
        $sorgu->execute([$kullanici]);
        $user = $sorgu->fetch();

        if ($user && password_verify($sifre, $user['sifre'])) {
            // Session fixation koruması: login sonrası yeni session ID üret
            session_regenerate_id(true);

            $_SESSION['oturum'] = true;
            $_SESSION['id'] = $user['id'];
            $_SESSION['kullanici_adi'] = $user['kullanici_adi'];
            $_SESSION['rol'] = $user['rol'] ?? 'admin';
            $_SESSION['sorumlu_il']    = $user['sorumlu_il'] ?? null;
            $_SESSION['sorumlu_ilce']  = $user['sorumlu_ilce'] ?? null;
            $_SESSION['sorumlu_kurum'] = $user['sorumlu_kurum'] ?? null;
            $_SESSION['son_aktivite'] = time();

            // Başarılı giriş: deneme sayacını temizle
            login_basarili_temizle($kullanici, $guvenlikDizini);

            header("Location: /yonetim/");
            exit;
        } else {
            // Başarısız giriş: sayacı artır
            login_basarisiz_kaydet($kullanici, $guvenlikDizini);
            $hata_mesaji = "Kullanıcı adı veya şifre hatalı!";
        }
    }
}

// ─── OTURUM KONTROLÜ ────────────────────────────────────────────────────
if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    include 'inc/login_form.php';
    exit;
}

// ─── ROL KONTROLLERİ ───────────────────────────────────────────────────
$kullanici_rolu      = $_SESSION['rol'] ?? 'admin';
$is_admin            = ($kullanici_rolu === 'admin');
$is_yonetim          = ($kullanici_rolu === 'yonetim');
$is_il_baskani       = ($kullanici_rolu === 'il_baskani');
$is_ilce_baskani     = ($kullanici_rolu === 'ilce_baskani');
$is_kurum_temsilcisi = ($kullanici_rolu === 'kurum_temsilcisi');
$is_kisitli_rol      = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi);
$is_yetki_var        = ($is_admin || $is_yonetim);

$sayfa = isset($_GET['sayfa']) ? trim($_GET['sayfa']) : 'dashboard';

include 'inc/header.php';
include 'inc/navbar.php';

switch ($sayfa) {
    case 'uyeler':
        include 'inc/uyeler.php';
        break;
        
    case 'uye-ekle':
        if ($is_kisitli_rol) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu hesap türü ile üye ekleme işlemi yapılamaz.</div></div>';
        } else {
            include 'inc/uye-ekle.php';
        }
        break;

    case 'uye-detay':
        include 'inc/uye-detay.php';
        break;

    case 'bekleyen-uyeler':
        if ($is_kisitli_rol) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu hesap türü ile bekleyen başvuruları görüntüleyemezsiniz.</div></div>';
        } else {
            include 'inc/bekleyen-uyeler.php';
        }
        break;

    case 'hesap-yonetimi':
        if (!$is_admin) {
            echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Hesap yönetimi sadece tam yetkili yöneticilere açıktır.</div></div>';
        } else {
            include 'inc/hesap-yonetimi.php';
        }
        break;
        
    case 'dashboard':
    default:
        // ─── KISITLI ROLLER İÇİN ÖZEL DASHBOARD ─────────────────────────
        if ($is_kisitli_rol) {
            $kisitli_baslik = 'Kontrol Paneli';
            $kisitli_aciklama = '';
            $kisitli_ikon = 'fa-chart-pie';
            $kisitli_renk = 'primary';
            $kisitli_where = "onay_durumu = 'onayli'";
            $kisitli_param = null;

            if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
                $kisitli_baslik = htmlspecialchars($_SESSION['sorumlu_il']) . ' İli';
                $kisitli_aciklama = htmlspecialchars($_SESSION['sorumlu_il']) . ' iline ait kayıtlı üyeler.';
                $kisitli_ikon = 'fa-building-flag';
                $kisitli_renk = 'success';
                $kisitli_where .= " AND ikamet_ili = ?";
                $kisitli_param = $_SESSION['sorumlu_il'];
            } elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
                $kisitli_baslik = htmlspecialchars($_SESSION['sorumlu_ilce']) . ' İlçesi';
                $kisitli_aciklama = htmlspecialchars($_SESSION['sorumlu_ilce']) . ' ilçesine ait kayıtlı üyeler.';
                $kisitli_ikon = 'fa-map-location-dot';
                $kisitli_renk = 'purple';
                $kisitli_where .= " AND trabzon_ilcesi = ?";
                $kisitli_param = $_SESSION['sorumlu_ilce'];
            } elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
                $kisitli_baslik = htmlspecialchars($_SESSION['sorumlu_kurum']);
                $kisitli_aciklama = htmlspecialchars($_SESSION['sorumlu_kurum']) . ' kurumuna ait kayıtlı üyeler.';
                $kisitli_ikon = 'fa-building-user';
                $kisitli_renk = 'warning';
                $kisitli_where .= " AND kurum = ?";
                $kisitli_param = $_SESSION['sorumlu_kurum'];
            }

            try {
                $k_sorgu = $db_baglanti->prepare("SELECT COUNT(*) FROM dernek_uyeler WHERE " . $kisitli_where);
                if ($kisitli_param !== null) {
                    $k_sorgu->execute([$kisitli_param]);
                } else {
                    $k_sorgu->execute();
                }
                $kisitli_uye_sayisi = $k_sorgu->fetchColumn();
            } catch (\PDOException $e) {
                error_log('Kısıtlı dashboard hatası: ' . $e->getMessage());
                $kisitli_uye_sayisi = 0;
            }
            ?>
            <div class="container-fluid py-4 px-md-4">
                <div class="row mb-4">
                    <div class="col-12 text-center">
                        <div class="bg-white p-5 rounded shadow-sm border">
                            <div class="mb-3">
                                <span class="badge bg-<?= $kisitli_renk; ?> px-3 py-2 fs-6 rounded-pill">
                                    <i class="fa-solid fa-<?= $kisitli_ikon; ?> me-1"></i> <?= $kisitli_baslik; ?>
                                </span>
                            </div>
                            <h2 class="fw-bold text-dark mb-2"><i class="fa-solid fa-users me-2 text-<?= $kisitli_renk; ?>"></i><?= $kisitli_uye_sayisi; ?> Kayıtlı Üye</h2>
                            <p class="text-muted mb-4"><?= $kisitli_aciklama; ?></p>
                            <a href="index.php?sayfa=uyeler" class="btn btn-dark fw-bold px-4 shadow-sm">
                                <i class="fa-solid fa-list me-2"></i>Üye Listesini Görüntüle
                            </a>
                        </div>
                    </div>
                </div>
            </div>
            <?php
            break;
        }

        // ─── STANDART DASHBOARD (admin, yonetim, il_baskani, ilce_baskani, kurum_temsilcisi) ────
        try {
            $toplam_uye = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli'")->fetchColumn();
            $toplam_il  = $db_baglanti->query("SELECT COUNT(DISTINCT ikamet_ili) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != ''")->fetchColumn();
            
            $yonetim_kurulu = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu LIKE '%Yönetim Kurulu%' OR temsilci_turu = 'Yönetici' OR ek_gorev LIKE '%Yönetim Kurulu%' OR ek_gorev = 'Yönetici')")->fetchColumn();
            $bolge_koordinatorleri = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')")->fetchColumn();
            $il_baskanlari = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi')")->fetchColumn();
            $ilce_baskanlari = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi')")->fetchColumn();
            $kurum_temsilcileri = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')")->fetchColumn();
            $teskilatlanma_sorumlusu = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan')")->fetchColumn();

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
                
                <!-- Toplam Üye -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-primary border-5 shadow-sm" style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Toplam Üye</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $toplam_uye; ?></h2>
                                </div>
                                <div class="bg-primary bg-opacity-10 p-3 rounded text-primary"><i class="fa-solid fa-users fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
                </div>

                <!-- Aktif İller -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=aktif_iller" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-danger border-5 shadow-sm" style="cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-muted text-uppercase small fw-bold mb-1" style="font-size: 0.75rem;">Aktif İller</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $toplam_il; ?></h2>
                                </div>
                                <div class="bg-danger bg-opacity-10 p-3 rounded text-danger"><i class="fa-solid fa-map-location-dot fa-xl"></i></div>
                            </div>
                        </div>
                    </a>
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

                <!-- Teşkilatlanma Sorumlu Başkan (Herkes İçin Açık) -->
                <div class="col">
                    <a href="index.php?sayfa=uyeler&filtre=teskilatlanma_sorumlusu" class="text-decoration-none text-dark d-block" style="transition: transform 0.2s;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-5 shadow-sm" style="border-left-color: #e65100 !important; cursor: pointer;">
                            <div class="d-flex align-items-center justify-content-between">
                                <div>
                                    <h6 class="text-uppercase small fw-bold mb-1" style="font-size: 0.75rem; color: #e65100;">Teşkilatlanma Sor. Bşk.</h6>
                                    <h2 class="fw-bold mb-0 text-dark"><?= $teskilatlanma_sorumlusu; ?></h2>
                                </div>
                                <div class="p-3 rounded" style="background-color: rgba(230, 81, 0, 0.1); color: #e65100;"><i class="fa-solid fa-sitemap fa-xl"></i></div>
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

                <!-- Bekleyen Başvuru -->
                <div class="col">
                    <a href="index.php?sayfa=bekleyen-uyeler" class="text-decoration-none d-block">
                        <div class="card card-stat bg-white h-100 p-3 border-0 border-start border-danger border-5 shadow-sm position-relative" style="transition: transform 0.2s; cursor: pointer;" onmouseover="this.style.transform='translateY(-3px)'" onmouseout="this.style.transform='translateY(0)'">
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
                    </a>
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
                        
                        <div class="d-flex gap-2">
                            <a href="index.php?sayfa=bekleyen-uyeler" class="btn btn-danger btn-sm fw-bold px-3 shadow-sm"><i class="fa-solid fa-user-clock me-1"></i>Başvuruları İncele</a>
                            <?php if (!$is_kisitli_rol): ?>
                                <a href="index.php?sayfa=uye-ekle" class="btn btn-dark btn-sm fw-bold px-3 shadow-sm"><i class="fa-solid fa-user-plus me-1"></i>Üye Ekle</a>
                            <?php endif; ?>
                        </div>
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