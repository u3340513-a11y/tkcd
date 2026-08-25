<?php
// Bu dosya inc/uyeler.php olarak kaydedilecek.

$mesaj = "";
$mesaj_turu = "";

$kullanici_rolu      = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin';
$is_admin            = ($kullanici_rolu === 'admin');
$is_yonetim          = ($kullanici_rolu === 'yonetim');
$is_il_baskani       = ($kullanici_rolu === 'il_baskani');
$is_ilce_baskani     = ($kullanici_rolu === 'ilce_baskani');
$is_kurum_temsilcisi = ($kullanici_rolu === 'kurum_temsilcisi');
$is_kisitli_rol      = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi);

// İşlem sonrası aynı sayfaya geri yönlendirme linki
$geri_link = isset($_SERVER['HTTP_REFERER']) ? $_SERVER['HTTP_REFERER'] : 'index.php?sayfa=uyeler';

// --- ÜYE SİLME MOTORU ---
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'uye_sil' && isset($_GET['id'])) {
    if ($is_kisitli_rol) {
        die("Erişim Engellendi: Bu işlemi yapmaya yetkiniz yok!");
    }
    $uye_id = intval($_GET['id']);
    try {
        // Silme öncesi üye adını çek
        $ad_sorgu = $db_baglanti->prepare("SELECT adi_soyadi FROM dernek_uyeler WHERE id = ?");
        $ad_sorgu->execute([$uye_id]);
        $silinen_ad = $ad_sorgu->fetchColumn() ?: ('Bilinmeyen #' . $uye_id);

        $sil_sorgu = $db_baglanti->prepare("DELETE FROM dernek_uyeler WHERE id = ?");
        $durum = $sil_sorgu->execute([$uye_id]);
        if ($durum) {
            log_kaydet($db_baglanti, 'uye_sil', $silinen_ad . ' adlı üye sistemden silindi.', 'dernek_uyeler', $uye_id);
            echo "<script>window.location.href='".$geri_link."';</script>";
            exit;
        }
    } catch (\PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
        $mesaj_turu = "danger";
    }
}

// --- ANA STATÜ DEĞİŞTİRME MOTORU ---
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'statü_degistir' && isset($_GET['id']) && isset($_GET['tur'])) {
    if ($is_kisitli_rol) {
        die("Erişim Engellendi: Bu işlemi yapmaya yetkiniz yok!");
    }
    $uye_id = intval($_GET['id']);
    $yeni_tur = trim($_GET['tur']);
    $bolge = isset($_GET['bolge']) ? trim($_GET['bolge']) : null;
    
    $gecerli_türler = ['Normal Üye', 'Yönetim Kurulu Üyesi', 'Yönetim Kurulu Üyesi Yedek', 'İl Başkanı', 'İlçe Başkanı', 'Kurum Temsilcisi', 'Bölge Koordinatörü'];
    
    if (in_array($yeni_tur, $gecerli_türler)) {
        try {
            // Önceki statüyü ve üye adını çek
            $eski_sorgu = $db_baglanti->prepare("SELECT adi_soyadi, temsilci_turu FROM dernek_uyeler WHERE id = ?");
            $eski_sorgu->execute([$uye_id]);
            $eski_veri = $eski_sorgu->fetch(PDO::FETCH_ASSOC);
            $uye_adi  = $eski_veri['adi_soyadi'] ?? ('Bilinmeyen #' . $uye_id);
            $eski_tur = $eski_veri['temsilci_turu'] ?? 'Belirtilmemiş';

            if ($yeni_tur !== 'Bölge Koordinatörü' && $yeni_tur !== 'İlçe Başkanı') {
                $bolge = null;
            }
            
            $guncelle_sorgu = $db_baglanti->prepare("UPDATE dernek_uyeler SET temsilci_turu = ?, sorumlu_bolge = ? WHERE id = ?");
            $durum = $guncelle_sorgu->execute([$yeni_tur, $bolge, $uye_id]);
            if ($durum) {
                $log_aciklama = $uye_adi . ' — ' . $eski_tur . ' → ' . $yeni_tur;
                if ($bolge) {
                    $log_aciklama .= ' (Bölge: ' . $bolge . ')';
                }
                log_kaydet($db_baglanti, 'temsilci_ata', $log_aciklama, 'dernek_uyeler', $uye_id);
                echo "<script>window.location.href='".$geri_link."';</script>";
                exit;
            }
        } catch (\PDOException $e) {
            $mesaj = "Hata: " . $e->getMessage();
            $mesaj_turu = "danger";
        }
    }
}

// --- EK GÖREV DEĞİŞTİRME VE SİLME MOTORU ---
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'ek_gorev_degistir' && isset($_GET['id']) && isset($_GET['gorev'])) {
    if ($is_kisitli_rol) {
        die("Erişim Engellendi: Bu işlemi yapmaya yetkiniz yok!");
    }
    $uye_id = intval($_GET['id']);
    $yeni_ek_gorev = trim($_GET['gorev']);

    if ($yeni_ek_gorev === 'sil') {
        $yeni_ek_gorev = null;
    }

    try {
        // Önceki ek görevi ve üye adını çek
        $eski_ek_sorgu = $db_baglanti->prepare("SELECT adi_soyadi, ek_gorev FROM dernek_uyeler WHERE id = ?");
        $eski_ek_sorgu->execute([$uye_id]);
        $eski_ek_veri = $eski_ek_sorgu->fetch(PDO::FETCH_ASSOC);
        $ek_uye_adi    = $eski_ek_veri['adi_soyadi'] ?? ('Bilinmeyen #' . $uye_id);
        $eski_ek_gorev = $eski_ek_veri['ek_gorev'] ?? 'Yok';

        $ek_guncelle_sorgu = $db_baglanti->prepare("UPDATE dernek_uyeler SET ek_gorev = ? WHERE id = ?");
        $durum = $ek_guncelle_sorgu->execute([$yeni_ek_gorev, $uye_id]);
        if ($durum) {
            $yeni_label = $yeni_ek_gorev ?? 'Kaldırıldı';
            log_kaydet($db_baglanti, 'temsilci_ata', $ek_uye_adi . ' — Ek görev: ' . $eski_ek_gorev . ' → ' . $yeni_label, 'dernek_uyeler', $uye_id);
            echo "<script>window.location.href='".$geri_link."';</script>";
            exit;
        }
    } catch (\PDOException $e) {
        $mesaj = "Hata: " . $e->getMessage();
        $mesaj_turu = "danger";
    }
}

// --- DASHBOARD KARTLARINDAN GELEN RADAR FİLTRESİNİ YAKALAMA MOTORU ---
$aktif_filtre = isset($_GET['filtre']) ? trim($_GET['filtre']) : '';

$limit = 50;
$mevcut_sayfa = isset($_GET['p']) ? max(1, intval($_GET['p'])) : 1;
$offset = ($mevcut_sayfa - 1) * $limit;

$iller_modu = false;

// ─── ROL BAZLI EK FİLTRE (mevcut filtre mantığına dokunulmaz) ────────
$rol_ek_where = '';
$rol_ek_parametreler = [];

if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
    $rol_ek_where = " AND ikamet_ili = ?";
    $rol_ek_parametreler[] = $_SESSION['sorumlu_il'];
} elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
    $rol_ek_where = " AND (ikamet_ilcesi = ? OR (ikamet_ilcesi IS NULL AND trabzon_ilcesi = ?))";
    $rol_ek_parametreler[] = $_SESSION['sorumlu_ilce'];
    $rol_ek_parametreler[] = $_SESSION['sorumlu_ilce'];
} elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
    $rol_ek_where = " AND kurum = ?";
    $rol_ek_parametreler[] = $_SESSION['sorumlu_kurum'];
}

try {
    if ($aktif_filtre === 'kurum_temsilcisi') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'yonetim_kurulu') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Yönetim Kurulu Üyesi' OR temsilci_turu = 'Yönetim Kurulu Üyesi Yedek' OR temsilci_turu = 'Yönetici' OR ek_gorev = 'Yönetim Kurulu Üyesi' OR ek_gorev = 'Yönetim Kurulu Üyesi Yedek' OR ek_gorev = 'Yönetici')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Yönetim Kurulu Üyesi' OR temsilci_turu = 'Yönetim Kurulu Üyesi Yedek' OR temsilci_turu = 'Yönetici' OR ek_gorev = 'Yönetim Kurulu Üyesi' OR ek_gorev = 'Yönetim Kurulu Üyesi Yedek' OR ek_gorev = 'Yönetici')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'bolge_koordinatoru') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'il_baskani') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'ilce_baskani') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'teskilatlanma_sorumlusu') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan')" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan')" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'aktif_iller') {
        $iller_modu = true;
        $toplam_onayli = $db_baglanti->query("SELECT COUNT(DISTINCT ikamet_ili) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != ''")->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT ikamet_ili, COUNT(*) as uye_adet FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND ikamet_ili IS NOT NULL AND ikamet_ili != '' GROUP BY ikamet_ili ORDER BY ikamet_ili ASC LIMIT ? OFFSET ?");
    } else {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli'" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli'" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    }
    
    // Parametreleri bind et: önce rol parametreleri, sonra limit/offset
    $param_idx = 1;
    foreach ($rol_ek_parametreler as $rp) {
        $sorgu->bindValue($param_idx++, $rp, PDO::PARAM_STR);
    }
    $sorgu->bindValue($param_idx++, $limit, PDO::PARAM_INT);
    $sorgu->bindValue($param_idx, $offset, PDO::PARAM_INT);
    $sorgu->execute();
    $veriler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('Yönetim üye listeleme hatası: ' . $e->getMessage());
    die('Üye verileri yüklenirken bir hata oluştu.');
}
?>

<div class="container-fluid px-2 px-md-4 py-3" style="max-width: 100%; overflow-x: hidden;">
    
    <!-- ÜST HEADER BÖLÜMÜ -->
    <div class="row mb-3 align-items-center g-2" style="margin-left: 0 !important; margin-right: 0 !important;">
        <div class="col-12 col-md-5 text-center text-md-start px-0">
            <h3 class="fw-bold text-dark mb-0 fs-4"><i class="fa-solid fa-users me-2"></i>Üye Listesi</h3>
            <p class="text-muted mb-0 small">
                <?php 
                if($aktif_filtre === 'kurum_temsilcisi') echo 'Filtrelenen: Kurum Temsilcileri Listesi';
                elseif($aktif_filtre === 'yonetim_kurulu') echo 'Filtrelenen: Yönetim Kurulu Üyeleri Listesi';
                elseif($aktif_filtre === 'bolge_koordinatoru') echo 'Filtrelenen: Bölge Koordinatörleri Listesi';
                elseif($aktif_filtre === 'il_baskani') echo 'Filtrelenen: İl Başkanları Listesi';
                elseif($aktif_filtre === 'ilce_baskani') echo 'Filtrelenen: İlçe Başkanları Listesi';
                elseif($aktif_filtre === 'teskilatlanma_sorumlusu') echo 'Filtrelenen: Teşkilatlanma, Komiteler ve Temsilcilerden Sorumlu Başkan Listesi';
                elseif($aktif_filtre === 'aktif_iller') echo 'Filtrelenen: Aktif İl Listesi (Toplam ' . $toplam_onayli . ' İl)';
                else echo 'Derneğe kayıtlı aktif üyeler listesi.';
                ?>
            </p>
        </div>
        <div class="col-12 col-md-7 px-0 mt-2 mt-md-0">
            <div class="d-flex shadow-sm rounded-3">
                <div class="input-group">
                    <!-- Readonly hilesi ile klavye tamamlama baloncuğu tamamen engellendi -->
                    <input type="text" id="tabloCanliAra" readonly onfocus="this.removeAttribute('readonly');" <?= $iller_modu ? 'disabled placeholder="İl modunda arama devre dışı..."' : 'oninput="canliVeritabanıArama(this.value)" placeholder="Arama..."'; ?> class="form-control rounded-start px-3" autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
                    
                    <?php if(!$iller_modu && !$is_kisitli_rol && !$is_yonetim): ?>
                    <button type="button" onclick="dosyaYonlendir('excel')" class="btn btn-success fw-bold px-2 px-sm-3 d-flex align-items-center justify-content-center btn-sm">
                        <i class="fa-solid fa-file-excel me-1"></i> Excel
                    </button>
                    <button type="button" onclick="dosyaYonlendir('pdf')" class="btn btn-danger fw-bold px-2 px-sm-3 d-flex align-items-center justify-content-center btn-sm">
                        <i class="fa-solid fa-file-pdf me-1"></i> PDF
                    </button>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- TABLO KARTI -->
    <div class="card border-0 shadow-sm rounded-3 mb-3">
        <div class="card-body p-0">
            <div class="table-responsive" style="overflow-x: auto !important; -webkit-overflow-scrolling: touch; width: 100%; min-height: 420px;">
                
                <?php if ($iller_modu): ?>
                <table class="table table-hover align-middle mb-0 w-100">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3" style="width: 10%;">#</th>
                            <th style="width: 50%;">İl Adı</th>
                            <th class="text-center" style="width: 20%;">Kayıtlı Üye Sayısı</th>
                            <th class="text-center" style="width: 20%;">İşlem</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php if (count($veriler) > 0): $sira = $offset + 1; ?>
                            <?php foreach ($veriler as $satir): ?>
                                <tr>
                                    <td class="ps-3 fw-bold text-muted"><?= $sira++; ?></td>
                                    <td class="fw-bold text-dark"><i class="fa-solid fa-map-pin text-danger me-2"></i><?= htmlspecialchars($satir['ikamet_ili']); ?></td>
                                    <td class="text-center"><span class="badge bg-primary fs-6 px-3 py-1.5 rounded-pill"><?= $satir['uye_adet']; ?> Üye</span></td>
                                    <td class="text-center">
                                        <a href="index.php?sayfa=uyeler" onclick="localStorage.setItem('oto_ara', '<?= $satir['ikamet_ili']; ?>');" class="btn btn-dark btn-sm fw-bold shadow-sm">
                                            <i class="fa-solid fa-eye me-1"></i> Üyeleri Gör
                                        </a>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="4" class="text-center py-5 text-muted">Henüz aktif il verisi bulunmamaktadır.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                
                <?php else: ?>
                <table class="table table-hover align-middle mb-0" style="min-width: 900px; width: 100%;">
                    <thead class="table-dark">
                        <tr>
                            <th class="ps-3 py-3" style="width: 160px;">Adı Soyadı</th>
                            <th style="width: 110px; white-space: nowrap;">Telefon</th>
                            <th style="width: 150px; white-space: nowrap;">E-Posta</th>
                            <th style="width: 65px; white-space: nowrap;" class="text-center">Kan</th>
                            <th style="width: 85px; white-space: nowrap;" class="text-center">Doğum T.</th>
                            <th style="width: 100px; white-space: nowrap;">İl / İlçe</th>
                            <th style="width: 130px; white-space: nowrap;">Kurum / Ünvan</th>
                            <th style="width: 85px; white-space: nowrap;">Çalışma</th>
                            <th style="width: 150px; white-space: nowrap;" class="text-center">Statü</th>
                            <th class="text-center pe-3" style="width: 100px; white-space: nowrap;">İşlemler</th>
                        </tr>
                    </thead>
                    <tbody id="uyeTabloGövdesi">
                        <?php if (count($veriler) > 0): ?>
                            <?php foreach ($veriler as $uye): ?>
                                <?php 
                                $satir_klasi = "";
                                $rozet_klasi = "bg-secondary";
                                
                                $parcalar = explode(' ', trim($uye['adi_soyadi']));
                                $ilk_isim = mb_strtoupper($parcalar[0], 'UTF-8');
                                
                                $kadin_isimleri = ['SEMRA', 'AYŞEGÜL', 'BEGÜM', 'HATİCE', 'FATMA', 'AYŞE', 'EMİNE', 'ZEYNEP', 'MERYEM', 'ELİF', 'HÜLYA', 'GAMZE', 'MERVE', 'BÜŞRA', 'ESRA', 'SEDA', 'DERYA', 'KÜBRA', 'ASLI', 'PELİN', 'TUĞBA', 'DEMET', 'ÖZLEM', 'SİNEM', 'GÜL', 'NUR', 'MELİS', 'DİLAN', 'BURCU', 'CANAN', 'SULTAN', 'MELİKE', 'YASEMİN', 'EDA', 'BERNA', 'SELEN', 'PINAR', 'BANU', 'YEŞİM', 'EBRU', 'FADİME', 'NURAN', 'SELMA', 'DİLEK', 'FİLİZ', 'ARZU', 'LEYLA', 'SİBEL', 'HALE', 'JALE', 'GONCA', 'MÜGE', 'NESLİHAN', 'NAZLI', 'MİNE', 'SELİN', 'ESMA', 'FAZİLET', 'NESRİN', 'REYHAN', 'AHSEN', 'İPEK', 'ÖZGE', 'GÜLAY', 'SÜREYYA', 'DİDEM', 'Handan', 'NURTEN', 'ŞERİFE', 'SABİHA', 'ZEHRA', 'ÜMMÜHAN', 'RABİA', 'BÜŞRANUR', 'FATMANUR', 'GÜLSÜM', 'KÜBRANUR', 'ŞEYMA', 'BETÜL', 'SÜMEYYE', 'KADRİYE', 'HAVVA', 'SONGÜL', 'DÖNDÜ', 'NURAY', 'FİRDEVS', 'AYTEN', 'AYSEL', 'GÜLER', 'NURSEL', 'NURCAN', 'MELEK', 'FİLİZ', 'NURHAN', 'PERİHAN', 'SUZAN', 'SUNA', 'ŞENNUR', 'İLKAY', 'GÜLDEN', 'İLKY_NUR', 'GÜLŞAH', 'AŞKIN', 'SEVAL', 'SEVİL', 'SEVİM', 'NİHAL', 'NİLÜFER', 'NİLAY', 'NURSEl', 'MELTEM'];
                                
                                if (in_array($ilk_isim, $kadin_isimleri)) {
                                    $ikon_renk = 'color: #e83e8c !important;'; 
                                    $ikon_sekil = 'fa-user-nurse';
                                } else {
                                    $ikon_renk = 'color: #007bff !important;'; 
                                    $ikon_sekil = 'fa-user';
                                }

                                $temsilci_turu_kontrol = trim($uye['temsilci_turu']);
                                $ek_gorev_kontrol     = trim($uye['ek_gorev'] ?? '');

                                $ust_bolge_yazisi = "";
                                $rozet_yazisi = htmlspecialchars($uye['temsilci_turu']);
                                
                                if(!empty($uye['sorumlu_bolge'])) {
                                    $bolge_metni = trim($uye['sorumlu_bolge']);
                                    $dinamik_ikon = 'fa-solid fa-award text-dark'; 
                                    
                                    if (mb_stripos($bolge_metni, 'Türkiye Temsilci ve Komite Başkanı', 0, 'UTF-8') !== false) {
                                        $dinamik_ikon = 'fa-solid fa-ranking-star text-primary animate__animated animate__pulse animate__infinite';
                                    } elseif (mb_stripos($bolge_metni, 'Dernek Başkanı', 0, 'UTF-8') !== false) {
                                        $dinamik_ikon = 'fa-solid fa-crown text-warning animate__animated animate__pulse animate__infinite';
                                    } elseif (mb_stripos($bolge_metni, 'Gençlik', 0, 'UTF-8') !== false) {
                                        $dinamik_ikon = 'fa-solid fa-child-reaching text-primary';
                                    } elseif (mb_stripos($bolge_metni, 'Kadın', 0, 'UTF-8') !== false) {
                                        $dinamik_ikon = 'fa-solid fa-person-dress text-pink';
                                    } elseif ($temsilci_turu_kontrol === 'İlçe Başkanı') {
                                        $dinamik_ikon = 'fa-solid fa-location-dot text-purple';
                                    } elseif ($temsilci_turu_kontrol === 'Bölge Koordinatörü' || mb_stripos($bolge_metni, 'Bölge', 0, 'UTF-8') !== false) {
                                        $dinamik_ikon = 'fa-solid fa-earth-europe text-info';
                                    }
                                    
                                    $ust_bolge_yazisi = '<div class="fw-bold text-dark text-center mb-1 small" style="word-break: keep-all; max-width: 220px; line-height: 1.3;"><i class="' . $dinamik_ikon . ' me-1"></i>' . htmlspecialchars($bolge_metni) . '</div>';
                                }
                                
                                if ($temsilci_turu_kontrol === 'Yönetim Kurulu Üyesi') {
                                    $satir_klasi = 'class="table-primary"'; 
                                    $rozet_klasi = "bg-primary text-white";
                                    $sol_ikon = '<i class="fa-solid fa-user-shield text-primary me-2"></i>';
                                } elseif ($temsilci_turu_kontrol === 'Yönetim Kurulu Üyesi Yedek') {
                                    $satir_klasi = 'class="table-info"'; 
                                    $rozet_klasi = "bg-info text-dark";
                                    $sol_ikon = '<i class="fa-solid fa-user-shield text-info me-2"></i>';
                                } elseif ($temsilci_turu_kontrol === 'İl Başkanı') {
                                    $satir_klasi = 'class="table-success"'; 
                                    $rozet_klasi = "bg-success text-white";
                                    $sol_ikon = '<i class="fa-solid fa-building-flag text-success me-2"></i>';
                                } elseif ($temsilci_turu_kontrol === 'İlçe Başkanı') {
                                    $satir_klasi = 'class="ilce-baskani-satir"'; 
                                    $rozet_klasi = "text-white";
                                    $sol_ikon = '<i class="fa-solid fa-map-location-dot me-2" style="color:#6a1b9a !important;"></i>';
                                } elseif ($temsilci_turu_kontrol === 'Kurum Temsilcisi') {
                                    $satir_klasi = 'class="table-warning"'; 
                                    $rozet_klasi = "bg-warning text-dark";
                                    $sol_ikon = '<i class="fa-solid fa-building-user text-warning me-2"></i>';
                                } elseif ($temsilci_turu_kontrol === 'Bölge Koordinatörü') {
                                    $satir_klasi = 'class="bolge-koordinator-satir"'; 
                                    $rozet_klasi = "text-white";
                                    $sol_ikon = '<i class="fa-solid fa-earth-americas me-2" style="color:#00838f !important;"></i>';
                                } else {
                                    $sol_ikon = '<i class="fa-solid '.$ikon_sekil.' me-2" style="'.$ikon_renk.'"></i>';
                                }
                                
                                $kan = !empty($uye['kan_grubu']) ? $uye['kan_grubu'] : '-';
                                if (!empty($uye['dogum_tarihi'])) {
                                    $dt = trim($uye['dogum_tarihi']);
                                    if (preg_match('/^(\d{2})[\/\.](\d{2})[\/\.](\d{4})$/', $dt, $m)) {
                                        $ts = mktime(0, 0, 0, (int)$m[2], (int)$m[1], (int)$m[3]);
                                        $dogum = $ts ? date('d.m.Y', $ts) : $dt;
                                    } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
                                        $dogum = date('d.m.Y', strtotime($dt));
                                    } else {
                                        $dogum = $dt;
                                    }
                                } else {
                                    $dogum = !empty($uye['dogum_yili']) ? $uye['dogum_yili'] : '-';
                                }
                                ?>
                                <tr <?= $satir_klasi; ?> style="transition: background-color 0.2s;">
                                    <td class="ps-3 fw-bold">
                                        <a href="index.php?sayfa=uye-detay&id=<?= $uye['id']; ?>" class="text-decoration-none text-dark d-block py-1 hover-link" style="cursor: pointer;">
                                            <div class="d-flex align-items-center">
                                                <?= $sol_ikon; ?> 
                                                <div>
                                                    <?= htmlspecialchars($uye['adi_soyadi']); ?>
                                                </div>
                                            </div>
                                        </a>
                                    </td>
                                    <td style="white-space: nowrap; font-weight: 500; font-size: 0.9rem;"><?= htmlspecialchars($uye['telefon'] ?: '-'); ?></td>
                                    <td><small class="text-truncate d-inline-block" style="max-width: 140px;"><?= htmlspecialchars($uye['eposta'] ?: '-'); ?></small></td>
                                    <td class="text-center"><span class="badge bg-danger text-white"><?= htmlspecialchars($kan ?: '-'); ?></span></td>
                                    <td class="text-center"><small><?= htmlspecialchars($dogum); ?></small></td>
                                    <td>
                                        <strong><?= htmlspecialchars($uye['ikamet_ili'] ?: '-'); ?></strong>
                                        <br><small class="text-muted"><?= htmlspecialchars(!empty($uye['ikamet_ilcesi']) ? $uye['ikamet_ilcesi'] : ($uye['trabzon_ilcesi'] ?: '-')); ?></small>
                                    </td>
                                    <td>
                                        <small><?= htmlspecialchars($uye['kurum'] ?: '-'); ?></small>
                                        <br><small class="text-muted"><?= htmlspecialchars($uye['gorev_unvan'] ?: '-'); ?></small>
                                    </td>
                                    <td><small><?= htmlspecialchars($uye['calisma_sekli'] ?: '-'); ?></small></td>
                                    <td class="text-center">
                                        <div class="d-flex flex-column align-items-center justify-content-center">
                                            <?= $ust_bolge_yazisi; ?>
                                            <span class="badge <?= $rozet_klasi; ?> statu-rozet fw-semibold" 
                                                <?php 
                                                if($temsilci_turu_kontrol === 'İlçe Başkanı') echo 'style="background-color: #6a1b9a !important;"';
                                                if($temsilci_turu_kontrol === 'Bölge Koordinatörü') echo 'style="background-color: #00838f !important;"';
                                                ?>>
                                                <?= $rozet_yazisi; ?>
                                            </span>

                                            <?php if(!empty($ek_gorev_kontrol)): ?>
                                                <div class="mt-1">
                                                    <span class="badge bg-dark text-white statu-rozet small" title="Ek Görev">
                                                        <i class="fa-solid fa-plus-circle text-warning me-1"></i><?= htmlspecialchars($ek_gorev_kontrol); ?>
                                                    </span>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </td>
                                    <td class="text-center pe-3">
                                        <?php if ($is_kisitli_rol): ?>
                                            <span class="badge bg-info text-dark px-2 py-1"><i class="fa-solid fa-eye me-1"></i>Sadece Görüntüleme</span>
                                        <?php else: ?>
                                            <div class="btn-group dropup position-static">
                                                <button class="btn btn-dark btn-sm dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-popper-config='{"strategy":"fixed"}'>
                                                    <i class="fa-solid fa-user-gear me-1"></i> Yönet
                                                </button>
                                                <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 kucuk-yonet-menu">
                                                    <li><h6 class="dropdown-header fw-bold text-uppercase py-1" style="font-size: 0.72rem;">Ana Statü Değiştir</h6></li>
                                                    <li><a class="dropdown-item text-info fw-bold py-1" href="javascript:void(0);" onclick="bolgeSecimPenceresi(<?= $uye['id']; ?>)"><i class="fa-solid fa-earth-americas me-1.5"></i>Bölge Koordinatörü Yap</a></li>
                                                    
                                                    <?php if($temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi'): ?>
                                                        <li><a class="dropdown-item text-primary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-user-shield me-1.5"></i>Yönetim Kurulu Üyesi Yap</a></li>
                                                    <?php endif; ?>

                                                    <?php if($temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi Yedek'): ?>
                                                        <li><a class="dropdown-item text-info py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-user-shield me-1.5"></i>Yönetim Kurulu Üyesi Yedek Yap</a></li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($temsilci_turu_kontrol !== 'İl Başkanı'): ?>
                                                        <li><a class="dropdown-item text-success py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=İl+Başkanı"><i class="fa-solid fa-building-flag me-1.5"></i>İl Başkanı Yap</a></li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($temsilci_turu_kontrol !== 'İlçe Başkanı'): ?>
                                                        <li><a class="dropdown-item py-1" style="color: #6a1b9a;" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=İlçe+Başkanı"><i class="fa-solid fa-map-location-dot me-1.5"></i>İlçe Başkanı Yap</a></li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($temsilci_turu_kontrol !== 'Kurum Temsilcisi'): ?>
                                                        <li><a class="dropdown-item text-warning py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=Kurum+Temsilcisi"><i class="fa-solid fa-building-user me-1.5"></i>Kurum Temsilcisi Yap</a></li>
                                                    <?php endif; ?>
                                                    
                                                    <?php if($temsilci_turu_kontrol !== 'Normal Üye'): ?>
                                                        <li><a class="dropdown-item text-secondary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id']; ?>&tur=Normal+Üye"><i class="fa-solid fa-user-minus me-1.5"></i>Normal Üyeliğe Çek</a></li>
                                                    <?php endif; ?>

                                                    <!-- EK GÖREV ATAMALARI -->
                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li><h6 class="dropdown-header text-dark fw-bold text-uppercase py-1" style="font-size: 0.72rem;">Ek Görev Atamaları</h6></li>

                                                    <?php if($ek_gorev_kontrol !== 'Yönetim Kurulu Üyesi' && $temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi'): ?>
                                                        <li><a class="dropdown-item text-primary fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id']; ?>&gorev=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Yönetim Kurulu Üyesi Yap</a></li>
                                                    <?php endif; ?>

                                                    <?php if($ek_gorev_kontrol !== 'Yönetim Kurulu Üyesi Yedek' && $temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi Yedek'): ?>
                                                        <li><a class="dropdown-item text-info fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id']; ?>&gorev=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Y. Kurulu Yedek Yap</a></li>
                                                    <?php endif; ?>

                                                    <?php if($ek_gorev_kontrol !== 'Teşkilatlanma Sorumlu Başkan' && $temsilci_turu_kontrol !== 'Teşkilatlanma Sorumlu Başkan'): ?>
                                                        <li><a class="dropdown-item fw-bold py-1" style="color: #e65100;" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id']; ?>&gorev=Teşkilatlanma+Sorumlu+Başkan"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Teşkilatlanma Sor. Bşk.</a></li>
                                                    <?php endif; ?>

                                                    <?php if(!empty($ek_gorev_kontrol)): ?>
                                                        <li><a class="dropdown-item text-danger py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id']; ?>&gorev=sil"><i class="fa-solid fa-xmark me-1.5"></i>Görev İptal Et/Sil</a></li>
                                                    <?php endif; ?>

                                                    <li><hr class="dropdown-divider my-1"></li>
                                                    <li>
                                                        <a class="dropdown-item text-danger fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=uye_sil&id=<?= $uye['id']; ?>" onclick="return confirm('<?= htmlspecialchars($uye['adi_soyadi']); ?> isimli üyeyi tamamen silmek istediğinize emin misiniz?');">
                                                            <i class="fa-solid fa-trash-can me-1.5"></i> Üyeyi Sil
                                                        </a>
                                                    </li>
                                                </ul>
                                            </div>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            <?php endforeach; ?>
                        <?php else: ?>
                            <tr><td colspan="10" class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-3x mb-3 d-block text-secondary"></i>Henüz veritabanında kayıtlı onaylı üye bulunmamaktadır.</td></tr>
                        <?php endif; ?>
                    </tbody>
                </table>
                <?php endif; ?>
                
            </div>
        </div>
    </div>

    <div id="sayfalamaKutusu">
        <?php if ($toplam_sayfa > 1): ?>
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center shadow-sm rounded">
                    <li class="page-item <?= ($mevcut_sayfa <= 1) ? 'disabled' : ''; ?>">
                        <a class="page-link fw-bold text-dark" href="index.php?sayfa=uyeler&p=<?= $mevcut_sayfa - 1; ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : ''; ?>"><i class="fa-solid fa-chevron-left me-1"></i> Önceki</a>
                    </li>
                    <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                        <li class="page-item <?= ($mevcut_sayfa == $i) ? 'active' : ''; ?>">
                            <a class="page-link fw-bold <?= ($mevcut_sayfa == $i) ? 'bg-dark border-dark text-white' : 'text-dark'; ?>" href="index.php?sayfa=uyeler&p=<?= $i; ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : ''; ?>"><?= $i; ?></a>
                        </li>
                    <?php endfor; ?>
                    <li class="page-item <?= ($mevcut_sayfa >= $toplam_sayfa) ? 'disabled' : ''; ?>">
                        <a class="page-link fw-bold text-dark" href="index.php?sayfa=uyeler&p=<?= $mevcut_sayfa + 1; ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : ''; ?>">Sonraki <i class="fa-solid fa-chevron-right ms-1"></i></a>
                    </li>
                </ul>
            </nav>
        <?php endif; ?>
    </div>
</div>

<?php if (!$is_kisitli_rol): ?>
<div class="modal fade" id="bolgeModal" tabindex="-1" aria-labelledby="bolgeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg">
      <div class="modal-header bg-dark text-white">
        <h5 class="modal-title fw-bold" id="bolgeModalLabel"><i class="fa-solid fa-earth-americas me-2 text-info"></i>Sorumlu Bölge Seçimi</h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Close"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-3">Lütfen bu üyenin koordine edeceği Türkiye coğrafi bölgesini seçiniz:</p>
        <input type="hidden" id="modalUyeId" value="">
        <div class="d-grid gap-2">
            <button onclick="bolgeAta('Marmara Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Marmara Bölgesi</button>
            <button onclick="bolgeAta('Karadeniz Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Karadeniz Bölgesi</button>
            <button onclick="bolgeAta('İç Anadolu Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>İç Anadolu Bölgesi</button>
            <button onclick="bolgeAta('Ege Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Ege Bölgesi</button>
            <button onclick="bolgeAta('Akdeniz Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Akdeniz Bölgesi</button>
            <button onclick="bolgeAta('Doğu Anadolu Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Doğu Anadolu Bölgesi</button>
            <button onclick="bolgeAta('Güneydoğu Anadolu Bölgesi')" class="btn btn-outline-dark fw-bold text-start"><i class="fa-solid fa-circle-dot text-info me-2"></i>Güneydoğu Anadolu Bölgesi</button>
        </div>
      </div>
    </div>
  </div>
</div>
<?php endif; ?>

<script>
window.addEventListener('load', function() {
    let otoAra = localStorage.getItem('oto_ara');
    if (otoAra) {
        localStorage.removeItem('oto_ara');
        let aramaKutusu = document.getElementById('tabloCanliAra');
        if (aramaKutusu) {
            aramaKutusu.value = otoAra;
            canliVeritabanıArama(otoAra);
        }
    }
});

let aramaZamanlayici;

function canliVeritabanıArama(deger) {
    clearTimeout(aramaZamanlayici);
    aramaZamanlayici = setTimeout(() => {
        let kelime = deger.trim();
        let sayfalama = document.getElementById("sayfalamaKutusu");
        if (kelime.length >= 2 || kelime.length === 0) {
            if(kelime.length > 0) { if(sayfalama) sayfalama.style.display = "none"; }
            else { if(sayfalama) sayfalama.style.display = "block"; }

            let aktifFiltre = '<?= $aktif_filtre; ?>';
            let url = 'inc/canli-ara.php?kelime=' + encodeURIComponent(kelime);
            if(aktifFiltre !== '') {
                url += '&filtre=' + encodeURIComponent(aktifFiltre);
            }

            fetch(url)
                .then(response => response.text())
                .then(html => {
                    document.getElementById("uyeTabloGövdesi").innerHTML = html;
                    let dropdownElementList = [].slice.call(document.querySelectorAll('.dropdown-toggle'));
                    dropdownElementList.map(function (dropdownToggleEl) {
                        return new bootstrap.Dropdown(dropdownToggleEl);
                    });
                });
        }
    }, 200);
}

function dosyaYonlendir(tur) {
    <?php if ($is_kisitli_rol || $is_yonetim): ?>
        alert('Bu kullanıcı yetkisi ile dosya indirme işlemi kısıtlanmıştır.');
        return;
    <?php endif; ?>
    let aramaKutusu = document.getElementById('tabloCanliAra');
    let aramaKelimesi = aramaKutusu ? aramaKutusu.value.trim() : '';
    let aktifFiltre = '<?= $aktif_filtre; ?>';
    let temelUrl = (tur === 'excel') ? "inc/excel-indir.php" : "inc/pdf-indir.php";
    
    let queryParams = [];
    
    if (aramaKelimesi !== "") {
        queryParams.push("arama=" + encodeURIComponent(aramaKelimesi));
    }
    if (aktifFiltre !== "") {
        queryParams.push("filtre=" + encodeURIComponent(aktifFiltre));
    }
    
    let nihaiUrl = temelUrl;
    if (queryParams.length > 0) {
        nihaiUrl += "?" + queryParams.join("&");
    }
    
    window.open(nihaiUrl, '_blank');
}

function bolgeSecimPenceresi(uyeId) {
    <?php if (!$is_kisitli_rol): ?>
    document.getElementById("modalUyeId").value = uyeId;
    var myModal = new bootstrap.Modal(document.getElementById('bolgeModal'));
    myModal.show();
    <?php endif; ?>
}

function bolgeAta(bolgeAdi) {
    <?php if (!$is_kisitli_rol): ?>
    var uyeId = document.getElementById("modalUyeId").value;
    if(uyeId) {
        window.location.href = 'index.php?sayfa=uyeler&aksiyon=statü_degistir&id=' + uyeId + '&tur=Bölge+Koordinatörü&bolge=' + encodeURIComponent(bolgeAdi);
    }
    <?php endif; ?>
}
</script>

<style>
.hover-link:hover { color: #610012 !important; text-decoration: underline !important; }
.ilce-baskani-satir, .ilce-baskani-satir td { background-color: #f3e5f5 !important; }
.ilce-baskani-satir:hover, .ilce-baskani-satir:hover td { background-color: #eaecfa !important; }
.bolge-koordinator-satir, .bolge-koordinator-satir td { background-color: #e0f7fa !important; }
.bolge-koordinator-satir:hover, .bolge-koordinator-satir:hover td { background-color: #b2ebf2 !important; }
.text-pink { color: #e83e8c !important; }

/* Mobilde Sağa-Sola Akıcı Kaydırma */
.table-responsive {
    width: 100% !important;
    max-width: 100% !important;
    overflow-x: auto !important;
    overflow-y: visible !important;
    min-height: 420px !important;
    -webkit-overflow-scrolling: touch;
}

.table-responsive table {
    table-layout: fixed !important;
}

.card-body {
    overflow: visible !important;
}

#uyeTabloGövdesi {
    min-height: 280px !important;
}

/* Rozet Standart Boyut Sınıfı */
.statu-rozet {
    min-width: 140px !important;
    display: inline-block !important;
    text-align: center !important;
    padding: 6px 8px !important;
    font-size: 0.78rem !important;
    white-space: nowrap !important;
}

/* Telefon Klavyelerinin Geçmiş Tamamlama Baloncuğunu / Otomatik Doldurmasını Tamamen Engelleme */
#tabloCanliAra::-webkit-contacts-auto-fill-button,
#tabloCanliAra::-webkit-credentials-auto-fill-button,
#tabloCanliAra::-webkit-search-decoration,
#tabloCanliAra::-webkit-search-cancel-button,
#tabloCanliAra::-webkit-search-results-button,
#tabloCanliAra::-webkit-search-results-decoration {
    visibility: hidden !important;
    display: none !important;
    pointer-events: none !important;
    -webkit-appearance: none !important;
}

/* Yönet Menüsü Açılma Z-Index & Konumlandırma */
.kucuk-yonet-menu {
    z-index: 999999 !important;
    min-width: 220px !important;
    font-size: 0.82rem !important;
}

.kucuk-yonet-menu .dropdown-item {
    font-size: 0.82rem !important;
    padding-top: 0.3rem !important;
    padding-bottom: 0.3rem !important;
}
</style>