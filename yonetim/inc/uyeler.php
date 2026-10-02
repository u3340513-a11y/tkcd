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
$is_kadin_kollari    = ($kullanici_rolu === 'kadin_kollari_baskani');
$is_kisitli_rol      = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi || $is_kadin_kollari);

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
    
    $gecerli_türler = ['Normal Üye', 'Yönetim Kurulu Üyesi', 'Yönetim Kurulu Üyesi Yedek', 'İl Başkanı', 'İlçe Başkanı', 'Kurum Temsilcisi', 'Bölge Koordinatörü', 'Kadın Kolları Başkanı'];
    
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

// --- ÇOK STATÜLÜ EK ROLLER TOGGLE MOTORU ---
// ek_roller sütunu JSON dizisi tutar: ["Kurum Temsilcisi","İlçe Başkanı"]
// Bu handler rol ekler ya da (zaten varsa) kaldırır.
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'ek_roller_toggle' && isset($_GET['id']) && isset($_GET['rol'])) {
    if ($is_kisitli_rol) {
        die("Erişim Engellendi: Bu işlemi yapmaya yetkiniz yok!");
    }
    $uye_id   = intval($_GET['id']);
    $hedef_rol = trim($_GET['rol']);

    $gecerli_roller = [
        'Normal Üye', 'Yönetim Kurulu Üyesi', 'Yönetim Kurulu Üyesi Yedek',
        'İl Başkanı', 'İlçe Başkanı', 'Kurum Temsilcisi', 'Bölge Koordinatörü',
        'Kadın Kolları Başkanı', 'Teşkilatlanma Sorumlu Başkan',
    ];

    if (!in_array($hedef_rol, $gecerli_roller, true)) {
        die("Geçersiz rol değeri.");
    }

    try {
        $sorgu = $db_baglanti->prepare("SELECT adi_soyadi, ek_roller FROM dernek_uyeler WHERE id = ?");
        $sorgu->execute([$uye_id]);
        $satir = $sorgu->fetch(PDO::FETCH_ASSOC);
        $uye_adi = $satir['adi_soyadi'] ?? ('Bilinmeyen #' . $uye_id);

        // Mevcut roller dizisini çöz
        $mevcutRoller = [];
        if (!empty($satir['ek_roller'])) {
            $parsed = json_decode($satir['ek_roller'], true);
            if (is_array($parsed)) {
                $mevcutRoller = $parsed;
            }
        }

        // Toggle: varsa çıkar, yoksa ekle
        $idx = array_search($hedef_rol, $mevcutRoller, true);
        $islem = '';
        if ($idx !== false) {
            array_splice($mevcutRoller, $idx, 1);
            $islem = 'kaldırıldı';
        } else {
            $mevcutRoller[] = $hedef_rol;
            $islem = 'eklendi';
        }

        $yeni_json = empty($mevcutRoller) ? null : json_encode(array_values($mevcutRoller), JSON_UNESCAPED_UNICODE);

        $guncelle = $db_baglanti->prepare("UPDATE dernek_uyeler SET ek_roller = ? WHERE id = ?");
        if ($guncelle->execute([$yeni_json, $uye_id])) {
            log_kaydet(
                $db_baglanti,
                'temsilci_ata',
                $uye_adi . ' — Ek rol "' . $hedef_rol . '" ' . $islem . '.',
                'dernek_uyeler',
                $uye_id
            );
            echo "<script>window.location.href='" . $geri_link . "';</script>";
            exit;
        }
    } catch (\PDOException $e) {
        $mesaj     = "Hata: " . $e->getMessage();
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
} elseif ($is_kadin_kollari) {
    $rol_ek_where = " AND cinsiyet = 'Kadın'";
}

try {
    if ($aktif_filtre === 'kurum_temsilcisi') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"Kurum Temsilcisi\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"Kurum Temsilcisi\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'yonetim_kurulu') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Yönetim Kurulu Üyesi' OR temsilci_turu = 'Yönetim Kurulu Üyesi Yedek' OR temsilci_turu = 'Yönetici' OR ek_gorev = 'Yönetim Kurulu Üyesi' OR ek_gorev = 'Yönetim Kurulu Üyesi Yedek' OR ek_gorev = 'Yönetici' OR JSON_CONTAINS(ek_roller, '\"Yönetim Kurulu Üyesi\"') OR JSON_CONTAINS(ek_roller, '\"Yönetim Kurulu Üyesi Yedek\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Yönetim Kurulu Üyesi' OR temsilci_turu = 'Yönetim Kurulu Üyesi Yedek' OR temsilci_turu = 'Yönetici' OR ek_gorev = 'Yönetim Kurulu Üyesi' OR ek_gorev = 'Yönetim Kurulu Üyesi Yedek' OR ek_gorev = 'Yönetici' OR JSON_CONTAINS(ek_roller, '\"Yönetim Kurulu Üyesi\"') OR JSON_CONTAINS(ek_roller, '\"Yönetim Kurulu Üyesi Yedek\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'bolge_koordinatoru') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü' OR JSON_CONTAINS(ek_roller, '\"Bölge Koordinatörü\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü' OR JSON_CONTAINS(ek_roller, '\"Bölge Koordinatörü\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'il_baskani') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"İl Başkanı\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"İl Başkanı\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'ilce_baskani') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"İlçe Başkanı\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi' OR JSON_CONTAINS(ek_roller, '\"İlçe Başkanı\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'teskilatlanma_sorumlusu') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan' OR JSON_CONTAINS(ek_roller, '\"Teşkilatlanma Sorumlu Başkan\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan' OR JSON_CONTAINS(ek_roller, '\"Teşkilatlanma Sorumlu Başkan\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
    } elseif ($aktif_filtre === 'kadin_kollari') {
        $say_sql = "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kadın Kolları Başkanı' OR JSON_CONTAINS(ek_roller, '\"Kadın Kolları Başkanı\"'))" . $rol_ek_where;
        $say_sorgu = $db_baglanti->prepare($say_sql);
        $say_sorgu->execute($rol_ek_parametreler);
        $toplam_onayli = $say_sorgu->fetchColumn();
        $toplam_sayfa = ceil($toplam_onayli / $limit);
        $sorgu = $db_baglanti->prepare("SELECT * FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (temsilci_turu = 'Kadın Kolları Başkanı' OR JSON_CONTAINS(ek_roller, '\"Kadın Kolları Başkanı\"'))" . $rol_ek_where . " ORDER BY adi_soyadi ASC LIMIT ? OFFSET ?");
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

<style>
/* ═══════════════════════════════════════════════════
   Üye Listesi — Modern Tasarım
   Tüm iş mantığı korunmuştur; yalnızca görsel katman
   yeniden tasarlandı.
═══════════════════════════════════════════════════ */

/* ── Header ──────────────────────────────────────── */
.ul-header {
    display: flex; align-items: center; gap: 1rem;
    flex-wrap: wrap; margin-bottom: 1.5rem;
    background: #fff;
    border: 1px solid #eef0f5;
    border-radius: 16px;
    padding: 1rem 1.25rem;
    box-shadow: 0 2px 16px rgba(0,0,0,.05);
}
.ul-header__ikon {
    width: 46px; height: 46px; border-radius: 13px; flex-shrink: 0;
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 4px 14px rgba(26,26,46,.3);
}
.ul-header__ikon i { color: #00c9a7; font-size: 1.15rem; }
.ul-header__baslik { font-size: 1.2rem; font-weight: 800; color: #1a1a2e; margin: 0; }
.ul-header__aciklama { color: #6c757d; font-size: 0.8rem; margin: 0; }
.ul-header__sag { margin-left: auto; display: flex; align-items: center; gap: 0.5rem; flex-wrap: wrap; }

/* Arama input */
.ul-arama-grup {
    display: flex; align-items: center;
    border: 1.5px solid #e9ecef; border-radius: 10px;
    overflow: hidden; background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.ul-arama-grup:focus-within {
    border-color: #1a1a2e;
    box-shadow: 0 0 0 3px rgba(26,26,46,.1);
}
.ul-arama-grup__ikon {
    padding: 0 0.75rem; color: #adb5bd; font-size: 0.85rem;
    background: #f8f9fa; border-right: 1.5px solid #e9ecef;
    height: 36px; display: flex; align-items: center;
}
.ul-arama-input {
    border: none; outline: none; padding: 0 0.8rem;
    font-size: 0.85rem; color: #343a40; height: 36px; width: 220px;
    background: transparent;
}
.ul-arama-input::placeholder { color: #adb5bd; }

/* Export butonları */
.ul-btn {
    height: 36px; border: none; border-radius: 9px;
    padding: 0 0.9rem; font-weight: 700; font-size: 0.78rem;
    cursor: pointer; display: flex; align-items: center; gap: 0.35rem;
    white-space: nowrap; transition: filter .15s, transform .15s;
}
.ul-btn:hover { filter: brightness(1.08); transform: scale(1.02); }
.ul-btn--excel { background: #1d7a45; color: #fff; box-shadow: 0 3px 8px rgba(29,122,69,.3); }
.ul-btn--pdf   { background: #c0392b; color: #fff; box-shadow: 0 3px 8px rgba(192,57,43,.3); }
.ul-btn--harita { background: #e67e22; color: #fff; box-shadow: 0 3px 8px rgba(230,126,34,.3); }
.ul-btn--cinsiyet {
    height: 36px; border: 1.5px solid #dee2e6; border-radius: 9px;
    padding: 0 0.65rem; font-size: 0.78rem; color: #495057;
    background: #fff; cursor: pointer;
}

/* ── Tablo sarmalayıcı ────────────────────────────── */
.ul-kart {
    background: #fff;
    border-radius: 16px;
    border: 1px solid #eef0f5;
    box-shadow: 0 2px 20px rgba(0,0,0,.06);
    overflow: hidden; margin-bottom: 1rem;
}

/* ── Tablo ────────────────────────────────────────── */
.ul-tablo {
    width: 100%; border-collapse: collapse; min-width: 880px;
}
.ul-tablo thead tr {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
}
.ul-tablo thead th {
    color: rgba(255,255,255,.85);
    font-size: 0.72rem; font-weight: 700;
    text-transform: uppercase; letter-spacing: .05em;
    padding: 0.85rem 0.75rem; white-space: nowrap;
    border: none;
}
.ul-tablo thead th:first-child { padding-left: 1.25rem; border-radius: 0; }
.ul-tablo thead th:last-child  { padding-right: 1.25rem; }

/* Gövde satırlar */
.ul-tablo tbody tr {
    border-bottom: 1px solid #f5f6fa;
    transition: background .15s;
}
.ul-tablo tbody tr:hover td { background: #f8f9ff !important; }
.ul-tablo tbody tr:last-child { border-bottom: none; }
.ul-tablo tbody td {
    padding: 0.7rem 0.75rem; font-size: 0.84rem; color: #343a40;
    vertical-align: middle;
}
.ul-tablo tbody td:first-child { padding-left: 1.25rem; }
.ul-tablo tbody td:last-child  { padding-right: 1.25rem; }

/* Renkli statü satırları */
.ul-satir--yk td              { background: rgba(0,123,255,.05); }
.ul-satir--yk-yedek td        { background: rgba(23,162,184,.05); }
.ul-satir--il td              { background: rgba(40,167,69,.05); }
.ul-satir--ilce td            { background: rgba(106,27,154,.05); }
.ul-satir--kurum td           { background: rgba(255,193,7,.05); }
.ul-satir--bolge td           { background: rgba(0,131,143,.05); }
.ul-satir--kadin td           { background: rgba(214,51,132,.05); }

/* Üye adı linki */
.ul-uye-link {
    text-decoration: none; color: #1a1a2e; font-weight: 700;
    display: flex; align-items: center; gap: 0.5rem;
    transition: color .15s;
}
.ul-uye-link:hover { color: #610012; text-decoration: underline; }

/* Avatar ikon */
.ul-avatar {
    width: 30px; height: 30px; border-radius: 8px; flex-shrink: 0;
    display: flex; align-items: center; justify-content: center; font-size: 0.75rem;
}
.ul-avatar--erkek { background: rgba(0,123,255,.1); color: #007bff; }
.ul-avatar--kadin { background: rgba(232,62,140,.1); color: #e83e8c; }

/* Kan grubu badge */
.ul-kan {
    display: inline-block; background: #ff4757; color: #fff;
    border-radius: 6px; padding: 2px 7px; font-size: 0.68rem; font-weight: 700;
}
.ul-kan--bos { background: #e9ecef; color: #6c757d; }

/* İl / İlçe */
.ul-il { font-weight: 700; color: #1a1a2e; }
.ul-ilce { font-size: 0.74rem; color: #adb5bd; }

/* Kurum/Ünvan */
.ul-kurum { font-weight: 600; font-size: 0.82rem; color: #343a40; }
.ul-unvan { font-size: 0.74rem; color: #6c757d; }

/* Statü rozeti */
.ul-rozet {
    display: inline-block; border-radius: 8px; padding: 4px 10px;
    font-size: 0.72rem; font-weight: 700; white-space: nowrap;
    text-align: center; min-width: 130px;
}
.ul-rozet--normal   { background: #f0f2f5; color: #495057; }
.ul-rozet--yk       { background: #dbeafe; color: #1d4ed8; }
.ul-rozet--yk-yedek { background: #cff4fc; color: #055160; }
.ul-rozet--il       { background: #d1fae5; color: #065f46; }
.ul-rozet--ilce     { background: #ede9fe; color: #5b21b6; }
.ul-rozet--kurum    { background: #fef3c7; color: #92400e; }
.ul-rozet--bolge    { background: #e0f7fa; color: #006064; }
.ul-rozet--kadin    { background: rgba(214,51,132,.1); color: #be185d; }

.ul-ek-rozet {
    display: inline-block; background: #1a1a2e; color: #fff;
    border-radius: 6px; padding: 2px 8px; font-size: 0.68rem; font-weight: 600;
    margin-top: 3px;
}
.ul-bolge-yazisi {
    font-size: 0.72rem; font-weight: 700; color: #1a1a2e;
    text-align: center; margin-bottom: 3px; word-break: keep-all; max-width: 200px;
}

/* Yönet butonu */
.ul-yonet-btn {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    color: #fff; border: none; border-radius: 8px;
    padding: 0.38rem 0.75rem; font-size: 0.78rem; font-weight: 700;
    cursor: pointer; transition: filter .15s, transform .15s;
    display: flex; align-items: center; gap: 0.35rem;
}
.ul-yonet-btn:hover { filter: brightness(1.2); transform: scale(1.03); }

/* Dropdown menu geliştirme */
.ul-dropdown-menu {
    min-width: 220px;
    max-width: min(320px, calc(100vw - 1rem));
    max-height: min(70vh, 520px);
    overflow-y: auto;
    overflow-x: hidden;
    font-size: 0.82rem;
    border: none; border-radius: 12px;
    box-shadow: 0 8px 32px rgba(0,0,0,.18);
    z-index: 999999 !important;
    /* Momentum scroll iOS */
    -webkit-overflow-scrolling: touch;
}
.ul-dropdown-menu .dropdown-item { padding: 0.4rem 1rem; white-space: normal; }
.ul-dropdown-menu .dropdown-header {
    font-size: 0.68rem; font-weight: 800;
    text-transform: uppercase; letter-spacing: .06em; color: #adb5bd;
    padding: 0.5rem 1rem 0.25rem;
}

/* Mobil: dropdown sağa değil, ekranın ortasına/sola sabit */
@media (max-width: 600px) {
    .kucuk-yonet-menu {
        position: fixed !important;
        left: 0.5rem !important;
        right: 0.5rem !important;
        width: auto !important;
        max-width: calc(100vw - 1rem) !important;
        max-height: 65vh !important;
        top: auto !important;
        transform: none !important;
    }
}

/* Sayfalama */
.ul-sayfalama { display: flex; justify-content: center; padding: 0.75rem 0; }
.ul-sayfalama .page-link {
    border-radius: 8px; margin: 0 2px;
    border: 1.5px solid #e9ecef; color: #343a40; font-weight: 600;
    font-size: 0.82rem;
    transition: background .15s, border-color .15s;
}
.ul-sayfalama .page-item.active .page-link {
    background: linear-gradient(135deg, #1a1a2e, #16213e);
    border-color: #1a1a2e; color: #fff;
}

/* Boş durum */
.ul-bos { text-align: center; padding: 4rem 2rem; color: #adb5bd; }
.ul-bos i { font-size: 2.5rem; margin-bottom: 1rem; display: block; }

/* Çalışma şekli chip */
.ul-calisma {
    background: #f0f2f5; color: #495057; border-radius: 6px;
    padding: 2px 8px; font-size: 0.72rem; font-weight: 600; white-space: nowrap;
}

/* Responsive */
.ul-table-wrap {
    overflow-x: auto; -webkit-overflow-scrolling: touch;
    width: 100%; min-height: 420px;
}

/* Telefon arama input ipuçları engelleme */
#tabloCanliAra::-webkit-contacts-auto-fill-button,
#tabloCanliAra::-webkit-credentials-auto-fill-button,
#tabloCanliAra::-webkit-search-decoration,
#tabloCanliAra::-webkit-search-cancel-button,
#tabloCanliAra::-webkit-search-results-button,
#tabloCanliAra::-webkit-search-results-decoration {
    visibility: hidden !important; display: none !important;
    pointer-events: none !important; -webkit-appearance: none !important;
}

/* İlçe/Bölge satır renkleri (hover koruması) */
.ul-satir--ilce:hover td  { background: rgba(106,27,154,.09) !important; }
.ul-satir--bolge:hover td { background: rgba(0,131,143,.09) !important; }
.ul-satir--kadin:hover td { background: rgba(214,51,132,.09) !important; }
</style>

<div class="container-fluid px-2 px-md-4 py-3">

    <!-- ── HEADER ─────────────────────────────────────── -->
    <div class="ul-header">
        <div class="ul-header__ikon">
            <i class="fa-solid fa-users"></i>
        </div>
        <div>
            <h3 class="ul-header__baslik">Üye Listesi</h3>
            <p class="ul-header__aciklama">
                <?php
                if($aktif_filtre === 'kurum_temsilcisi')        echo 'Kurum Temsilcileri · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'yonetim_kurulu')      echo 'Yönetim Kurulu Üyeleri · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'bolge_koordinatoru')  echo 'Bölge Koordinatörleri · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'il_baskani')          echo 'İl Başkanları · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'ilce_baskani')        echo 'İlçe Başkanları · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'teskilatlanma_sorumlusu') echo 'Teşkilatlanma Sorumlu Başkanlar · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'kadin_kollari')       echo 'Kadın Kolları Başkanları · ' . $toplam_onayli . ' kişi';
                elseif($aktif_filtre === 'aktif_iller')         echo 'Aktif İller · ' . $toplam_onayli . ' il';
                else echo 'Kayıtlı aktif üyeler · ' . number_format($toplam_onayli) . ' kişi';
                ?>
            </p>
        </div>
        <div class="ul-header__sag">
            <!-- Arama -->
            <div class="ul-arama-grup">
                <div class="ul-arama-grup__ikon"><i class="fa-solid fa-magnifying-glass"></i></div>
                <input type="text" id="tabloCanliAra" class="ul-arama-input"
                       readonly onfocus="this.removeAttribute('readonly');"
                       <?= $iller_modu ? 'disabled placeholder="İl modunda arama devre dışı..."' : 'oninput="canliVeritabanıArama(this.value)" placeholder="İsim, il, kurum ara..."'; ?>
                       autocomplete="off" autocorrect="off" autocapitalize="off" spellcheck="false">
            </div>

            <!-- Export araçları -->
            <?php if (!$iller_modu && !$is_kisitli_rol && !$is_yonetim): ?>
            <select id="indirCinsiyetFiltre" class="ul-btn--cinsiyet" title="Cinsiyete göre filtrele">
                <option value="">Tümü (K/E)</option>
                <option value="Erkek">Erkek</option>
                <option value="Kadın">Kadın</option>
            </select>
            <button type="button" onclick="dosyaYonlendir('excel')" class="ul-btn ul-btn--excel">
                <i class="fa-solid fa-file-excel"></i> Excel
            </button>
            <button type="button" onclick="dosyaYonlendir('pdf')" class="ul-btn ul-btn--pdf">
                <i class="fa-solid fa-file-pdf"></i> PDF
            </button>
            <?php if (in_array($_SESSION['rol'] ?? '', ['admin', 'gelistirici'], true)): ?>
            <button type="button" onclick="window.location.href='index.php?sayfa=bos-il-excel'"
                    class="ul-btn ul-btn--harita" title="Üyesi olmayan iller">
                <i class="fa-solid fa-map-location-dot"></i> Üyesiz İller
            </button>
            <?php endif; ?>
            <?php endif; ?>
        </div>
    </div>

    <!-- ── TABLO KARTI ─────────────────────────────────── -->
    <div class="ul-kart">
        <div class="ul-table-wrap">

            <?php if ($iller_modu): ?>
            <!-- İL MODU TABLOSU -->
            <table class="ul-tablo">
                <thead>
                    <tr>
                        <th style="width:60px;">#</th>
                        <th>İl Adı</th>
                        <th class="text-center" style="width:160px;">Kayıtlı Üye</th>
                        <th class="text-center" style="width:140px;">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (count($veriler) > 0): $sira = $offset + 1; ?>
                    <?php foreach ($veriler as $satir): ?>
                    <tr>
                        <td style="color:#adb5bd;font-weight:600;"><?= $sira++ ?></td>
                        <td>
                            <span style="display:flex;align-items:center;gap:.5rem;font-weight:700;color:#1a1a2e;">
                                <i class="fa-solid fa-map-pin" style="color:#ff4757;font-size:.8rem;"></i>
                                <?= htmlspecialchars($satir['ikamet_ili']) ?>
                            </span>
                        </td>
                        <td class="text-center">
                            <span style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#00c9a7;border-radius:8px;padding:4px 14px;font-weight:700;font-size:.82rem;">
                                <?= $satir['uye_adet'] ?> Üye
                            </span>
                        </td>
                        <td class="text-center">
                            <a href="index.php?sayfa=uyeler"
                               onclick="localStorage.setItem('oto_ara', '<?= $satir['ikamet_ili'] ?>');"
                               class="ul-btn ul-btn--excel" style="display:inline-flex;height:30px;border-radius:7px;font-size:.76rem;padding:0 .7rem;">
                                <i class="fa-solid fa-eye"></i> Üyeleri Gör
                            </a>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="4" class="ul-bos"><i class="fa-solid fa-folder-open"></i>Henüz aktif il verisi bulunmamaktadır.</td></tr>
                    <?php endif; ?>
                </tbody>
            </table>

            <?php else: ?>
            <!-- ANA ÜYE TABLOSU -->
            <table class="ul-tablo">
                <thead>
                    <tr>
                        <th style="width:180px;">Adı Soyadı</th>
                        <th style="width:115px;">Telefon</th>
                        <th style="width:155px;">E-Posta</th>
                        <th style="width:65px;" class="text-center">Kan</th>
                        <th style="width:80px;" class="text-center">Doğum</th>
                        <th style="width:110px;">İl / İlçe</th>
                        <th style="width:150px;">Kurum / Ünvan</th>
                        <th style="width:85px;">Çalışma</th>
                        <th style="width:160px;" class="text-center">Statü</th>
                        <th style="width:100px;" class="text-center">İşlemler</th>
                    </tr>
                </thead>
                <tbody id="uyeTabloGövdesi">
                    <?php if (count($veriler) > 0): ?>
                    <?php foreach ($veriler as $uye):
                        $temsilciTurKontrol = trim($uye['temsilci_turu']);
                        $ekGorevKontrol     = trim($uye['ek_gorev'] ?? '');

                        // ── Çok statülü ek_roller JSON parse ──────────
                        $ekRollerArr = [];
                        if (!empty($uye['ek_roller'])) {
                            $parsed = json_decode($uye['ek_roller'], true);
                            if (is_array($parsed)) {
                                $ekRollerArr = $parsed;
                            }
                        }

                        // ── Cinsiyet tespiti ──────────────────────────
                        $cinsiyetDb = mb_strtolower(trim($uye['cinsiyet'] ?? ''), 'UTF-8');
                        if (in_array($cinsiyetDb, ['kadın','kadin','female','k'], true)) {
                            $isKadin = true;
                        } elseif (in_array($cinsiyetDb, ['erkek','male','e'], true)) {
                            $isKadin = false;
                        } else {
                            $ilkIsim = mb_strtoupper(explode(' ', trim($uye['adi_soyadi']))[0], 'UTF-8');
                            $kadinIsimleri = ['SEMRA','AYŞEGÜL','BEGÜM','HATİCE','FATMA','AYŞE','EMİNE','ZEYNEP','MERYEM','ELİF','HÜLYA','GAMZE','MERVE','BÜŞRA','ESRA','SEDA','DERYA','KÜBRA','ASLI','PELİN','TUĞBA','DEMET','ÖZLEM','SİNEM','GÜL','NUR','MELİS','DİLAN','BURCU','CANAN','SULTAN','MELİKE','YASEMİN','EDA','BERNA','SELEN','PINAR','BANU','YEŞİM','EBRU','FADİME','NURAN','SELMA','DİLEK','FİLİZ','ARZU','LEYLA','SİBEL','HALE','JALE','GONCA','MÜGE','NESLİHAN','NAZLI','MİNE','SELİN','ESMA','FAZİLET','NESRİN','REYHAN','AHSEN','İPEK','ÖZGE','GÜLAY','SÜREYYA','DİDEM','HANDAN','NURTEN','ŞERİFE','SABİHA','ZEHRA','ÜMMÜHAN','RABİA','BÜŞRANUR','FATMANUR','GÜLSÜM','KÜBRANUR','ŞEYMA','BETÜL','SÜMEYYE','KADRİYE','HAVVA','SONGÜL','DÖNDÜ','NURAY','FİRDEVS','AYTEN','AYSEL','GÜLER','NURSEL','NURCAN','MELEK','NURHAN','PERİHAN','SUZAN','SUNA','ŞENNUR','İLKAY','GÜLDEN','GÜLŞAH','SEVAL','SEVİL','SEVİM','NİHAL','NİLÜFER','NİLAY','MELTEM','DUYGU','NURŞEN'];
                            $isKadin = in_array($ilkIsim, $kadinIsimleri, true);
                        }

                        // ── Satır / Rozet sınıfı ─────────────────────
                        $satirKlasi  = '';
                        $rozetKlasi  = 'ul-rozet--normal';
                        $rozetYazisi = htmlspecialchars($uye['temsilci_turu'] ?: 'Normal Üye');
                        $solIkonHtml = '';

                        // ── Filtre bağlamı override ───────────────────
                        // Aktif filtre varsa ve kişi o role ek_roller'dan giriyorsa
                        // badge'i ve satır rengini filtre rolüyle göster.
                        $filtreBaglamRol = null;
                        $filtreBaglamRolMap = [
                            'ilce_baskani'           => 'İlçe Başkanı',
                            'il_baskani'             => 'İl Başkanı',
                            'yonetim_kurulu'         => 'Yönetim Kurulu Üyesi',
                            'kurum_temsilcisi'       => 'Kurum Temsilcisi',
                            'bolge_koordinatoru'     => 'Bölge Koordinatörü',
                            'teskilatlanma_sorumlusu'=> 'Teşkilatlanma Sorumlu Başkan',
                            'kadin_kollari'          => 'Kadın Kolları Başkanı',
                        ];
                        if (!empty($aktif_filtre) && isset($filtreBaglamRolMap[$aktif_filtre])) {
                            $hedefRol = $filtreBaglamRolMap[$aktif_filtre];
                            // Kişi bu role sadece ek_roller üzerinden giriyorsa
                            if (in_array($hedefRol, $ekRollerArr, true)
                                && $temsilciTurKontrol !== $hedefRol
                                && $ekGorevKontrol !== $hedefRol
                            ) {
                                $filtreBaglamRol = $hedefRol;
                                $rozetYazisi     = htmlspecialchars($hedefRol);
                            }
                        }

                        // Etkin rol: override varsa onu, yoksa temsilciTurKontrol'ü kullan
                        $etkinRol = $filtreBaglamRol ?? $temsilciTurKontrol;

                        match ($etkinRol) {
                            'Yönetim Kurulu Üyesi' => [
                                $satirKlasi = 'ul-satir--yk',
                                $rozetKlasi = 'ul-rozet--yk',
                            ],
                            'Yönetim Kurulu Üyesi Yedek' => [
                                $satirKlasi = 'ul-satir--yk-yedek',
                                $rozetKlasi = 'ul-rozet--yk-yedek',
                            ],
                            'İl Başkanı' => [
                                $satirKlasi = 'ul-satir--il',
                                $rozetKlasi = 'ul-rozet--il',
                            ],
                            'İlçe Başkanı' => [
                                $satirKlasi = 'ul-satir--ilce',
                                $rozetKlasi = 'ul-rozet--ilce',
                            ],
                            'Kurum Temsilcisi' => [
                                $satirKlasi = 'ul-satir--kurum',
                                $rozetKlasi = 'ul-rozet--kurum',
                            ],
                            'Bölge Koordinatörü' => [
                                $satirKlasi = 'ul-satir--bolge',
                                $rozetKlasi = 'ul-rozet--bolge',
                            ],
                            'Kadın Kolları Başkanı' => [
                                $satirKlasi = 'ul-satir--kadin',
                                $rozetKlasi = 'ul-rozet--kadin',
                            ],
                            default => null,
                        };

                        // ── Sorumlu Bölge Yazısı ─────────────────────
                        $ustBolgeHtml = '';
                        if (!empty($uye['sorumlu_bolge'])) {
                            $bolgeMetni = trim($uye['sorumlu_bolge']);
                            $dinamikIkon = 'fa-solid fa-award';
                            if (mb_stripos($bolgeMetni, 'Türkiye Temsilci', 0, 'UTF-8') !== false)
                                $dinamikIkon = 'fa-solid fa-ranking-star';
                            elseif (mb_stripos($bolgeMetni, 'Dernek Başkanı', 0, 'UTF-8') !== false)
                                $dinamikIkon = 'fa-solid fa-crown';
                            elseif (mb_stripos($bolgeMetni, 'Bölge', 0, 'UTF-8') !== false)
                                $dinamikIkon = 'fa-solid fa-earth-europe';
                            elseif ($etkinRol === 'İlçe Başkanı')
                                $dinamikIkon = 'fa-solid fa-location-dot';
                            $ustBolgeHtml = '<div class="ul-bolge-yazisi"><i class="'.$dinamikIkon.' me-1"></i>'.htmlspecialchars($bolgeMetni).'</div>';
                        }

                        // ── Doğum tarihi ─────────────────────────────
                        $dogum = '-';
                        if (!empty($uye['dogum_tarihi'])) {
                            $dt = trim($uye['dogum_tarihi']);
                            if (preg_match('/^(\d{2})[\/\.](\d{2})[\/\.](\d{4})$/', $dt, $m)) {
                                $ts = mktime(0,0,0,(int)$m[2],(int)$m[1],(int)$m[3]);
                                $dogum = $ts ? date('d.m.Y', $ts) : $dt;
                            } elseif (preg_match('/^\d{4}-\d{2}-\d{2}$/', $dt)) {
                                $dogum = date('d.m.Y', strtotime($dt));
                            } else {
                                $dogum = $dt;
                            }
                        } elseif (!empty($uye['dogum_yili'])) {
                            $dogum = $uye['dogum_yili'];
                        }

                        $kan = $uye['kan_grubu'] ?? '';
                    ?>
                    <tr class="<?= $satirKlasi ?>">

                        <!-- Ad Soyad -->
                        <td>
                            <a href="index.php?sayfa=uye-detay&id=<?= $uye['id'] ?>" class="ul-uye-link">
                                <div class="ul-avatar <?= $isKadin ? 'ul-avatar--kadin' : 'ul-avatar--erkek' ?>">
                                    <i class="fa-solid <?= $isKadin ? 'fa-user-nurse' : 'fa-user' ?>"></i>
                                </div>
                                <span><?= htmlspecialchars($uye['adi_soyadi']) ?></span>
                            </a>
                        </td>

                        <!-- Telefon -->
                        <td style="white-space:nowrap;font-weight:500;">
                            <?= gizli_alan(htmlspecialchars($uye['telefon'] ?: '')) ?>
                        </td>

                        <!-- E-Posta -->
                        <td>
                            <span class="d-inline-block text-truncate" style="max-width:140px;font-size:0.78rem;color:#6c757d;">
                                <?= gizli_alan(htmlspecialchars($uye['eposta'] ?: '')) ?>
                            </span>
                        </td>

                        <!-- Kan Grubu -->
                        <td class="text-center">
                            <?php if ($kan): ?>
                            <span class="ul-kan"><?= htmlspecialchars($kan) ?></span>
                            <?php else: ?>
                            <span class="ul-kan ul-kan--bos">—</span>
                            <?php endif; ?>
                        </td>

                        <!-- Doğum -->
                        <td class="text-center" style="font-size:0.78rem;color:#6c757d;">
                            <?= htmlspecialchars($dogum) ?>
                        </td>

                        <!-- İl / İlçe -->
                        <td>
                            <div class="ul-il"><?= htmlspecialchars($uye['ikamet_ili'] ?: '-') ?></div>
                            <?php if (!empty($uye['ikamet_ilcesi'])): ?>
                            <div class="ul-ilce"><?= htmlspecialchars($uye['ikamet_ilcesi']) ?></div>
                            <?php endif; ?>
                            <?php if (!empty($uye['trabzon_ilcesi'])): ?>
                            <div class="ul-ilce" style="color:#00c9a7;">Trab: <?= htmlspecialchars($uye['trabzon_ilcesi']) ?></div>
                            <?php endif; ?>
                        </td>

                        <!-- Kurum / Ünvan -->
                        <td>
                            <div class="ul-kurum"><?= htmlspecialchars($uye['kurum'] ?: '-') ?></div>
                            <div class="ul-unvan"><?= htmlspecialchars($uye['gorev_unvan'] ?: '') ?></div>
                        </td>

                        <!-- Çalışma -->
                        <td>
                            <span class="ul-calisma"><?= htmlspecialchars($uye['calisma_sekli'] ?: '-') ?></span>
                        </td>

                        <!-- Statü -->
                        <td class="text-center">
                            <?= $ustBolgeHtml ?>
                            <span class="ul-rozet <?= $rozetKlasi ?>"><?= $rozetYazisi ?></span>
                            <?php if (!empty($ekGorevKontrol)): ?>
                            <br>
                            <span class="ul-ek-rozet">
                                <i class="fa-solid fa-plus-circle me-1" style="color:#00c9a7;"></i><?= htmlspecialchars($ekGorevKontrol) ?>
                            </span>
                            <?php endif; ?>
                            <?php foreach ($ekRollerArr as $ekRol): ?>
                            <br>
                            <span class="ul-ek-rozet" style="background:rgba(99,102,241,0.12);color:#6366f1;border:1px solid rgba(99,102,241,0.3);">
                                <i class="fa-solid fa-circle-plus me-1"></i><?= htmlspecialchars($ekRol) ?>
                            </span>
                            <?php endforeach; ?>
                        </td>

                        <!-- İşlemler -->
                        <td class="text-center">
                            <?php if ($is_kisitli_rol): ?>
                            <span style="font-size:0.72rem;color:#adb5bd;background:#f8f9fa;border-radius:7px;padding:4px 8px;">
                                <i class="fa-solid fa-eye me-1"></i>Görüntüleme
                            </span>
                            <?php else: ?>
                            <div class="btn-group dropup position-static">
                                <button class="ul-yonet-btn dropdown-toggle" type="button"
                                        data-bs-toggle="dropdown" aria-expanded="false"
                                        data-bs-popper-config='{"strategy":"fixed"}'>
                                    <i class="fa-solid fa-user-gear"></i> Yönet
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end ul-dropdown-menu shadow-lg border-0 kucuk-yonet-menu">
                                    <li><h6 class="dropdown-header">Ana Statü Değiştir</h6></li>
                                    <li><a class="dropdown-item text-info fw-bold py-1" href="javascript:void(0);" onclick="bolgeSecimPenceresi(<?= $uye['id'] ?>)"><i class="fa-solid fa-earth-americas me-1"></i>Bölge Koordinatörü Yap</a></li>

                                    <?php if($temsilciTurKontrol !== 'Yönetim Kurulu Üyesi'): ?>
                                    <li><a class="dropdown-item text-primary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-user-shield me-1"></i>Yönetim Kurulu Üyesi Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'Yönetim Kurulu Üyesi Yedek'): ?>
                                    <li><a class="dropdown-item text-info py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-user-shield me-1"></i>Y.K. Üyesi Yedek Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'İl Başkanı'): ?>
                                    <li><a class="dropdown-item text-success py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=İl+Başkanı"><i class="fa-solid fa-building-flag me-1"></i>İl Başkanı Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'İlçe Başkanı'): ?>
                                    <li><a class="dropdown-item py-1" style="color:#6a1b9a;" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=İlçe+Başkanı"><i class="fa-solid fa-map-location-dot me-1"></i>İlçe Başkanı Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'Kurum Temsilcisi'): ?>
                                    <li><a class="dropdown-item text-warning py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=Kurum+Temsilcisi"><i class="fa-solid fa-building-user me-1"></i>Kurum Temsilcisi Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'Kadın Kolları Başkanı'): ?>
                                    <li><a class="dropdown-item py-1" style="color:#d63384;" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=Kadın+Kolları+Başkanı"><i class="fa-solid fa-venus me-1"></i>Kadın Kolları Başkanı Yap</a></li>
                                    <?php endif; ?>

                                    <?php if($temsilciTurKontrol !== 'Normal Üye'): ?>
                                    <li><a class="dropdown-item text-secondary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id=<?= $uye['id'] ?>&tur=Normal+Üye"><i class="fa-solid fa-user-minus me-1"></i>Normal Üyeliğe Çek</a></li>
                                    <?php endif; ?>

                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><h6 class="dropdown-header">Ek Görev Atamaları</h6></li>

                                    <?php if($ekGorevKontrol !== 'Yönetim Kurulu Üyesi' && $temsilciTurKontrol !== 'Yönetim Kurulu Üyesi'): ?>
                                    <li><a class="dropdown-item text-primary fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id'] ?>&gorev=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-plus me-1"></i>+ Görev: Y.K. Üyesi</a></li>
                                    <?php endif; ?>

                                    <?php if($ekGorevKontrol !== 'Yönetim Kurulu Üyesi Yedek' && $temsilciTurKontrol !== 'Yönetim Kurulu Üyesi Yedek'): ?>
                                    <li><a class="dropdown-item text-info fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id'] ?>&gorev=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-plus me-1"></i>+ Görev: Y.K. Yedek</a></li>
                                    <?php endif; ?>

                                    <?php if($ekGorevKontrol !== 'Teşkilatlanma Sorumlu Başkan' && $temsilciTurKontrol !== 'Teşkilatlanma Sorumlu Başkan'): ?>
                                    <li><a class="dropdown-item fw-bold py-1" style="color:#e65100;" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id'] ?>&gorev=Teşkilatlanma+Sorumlu+Başkan"><i class="fa-solid fa-plus me-1"></i>+ Görev: Teşk. Sor. Bşk.</a></li>
                                    <?php endif; ?>

                                    <?php if(!empty($ekGorevKontrol)): ?>
                                    <li><a class="dropdown-item text-danger py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id=<?= $uye['id'] ?>&gorev=sil"><i class="fa-solid fa-xmark me-1"></i>Eski Ek Görevi İptal Et</a></li>
                                    <?php endif; ?>

                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li><h6 class="dropdown-header" style="color:#6366f1;font-weight:700;">
                                        <i class="fa-solid fa-layer-group me-1"></i>Ek Roller (Çok Statü)
                                    </h6></li>
                                    <?php
                                    $tumRoller = [
                                        ['Yönetim Kurulu Üyesi',          'fa-user-shield',       '#0d6efd'],
                                        ['Yönetim Kurulu Üyesi Yedek',    'fa-user-shield',       '#0dcaf0'],
                                        ['Bölge Koordinatörü',            'fa-earth-americas',    '#0dcaf0'],
                                        ['İl Başkanı',                    'fa-building-flag',     '#198754'],
                                        ['İlçe Başkanı',                  'fa-map-location-dot',  '#6a1b9a'],
                                        ['Kurum Temsilcisi',              'fa-building-user',     '#b45309'],
                                        ['Kadın Kolları Başkanı',         'fa-venus',             '#d63384'],
                                        ['Teşkilatlanma Sorumlu Başkan',  'fa-sitemap',           '#e65100'],
                                    ];
                                    foreach ($tumRoller as [$rolAdi, $ikon, $renk]):
                                        $rolAktif = in_array($rolAdi, $ekRollerArr, true);
                                    ?>
                                    <li>
                                        <a class="dropdown-item py-1 d-flex align-items-center gap-2"
                                           style="color:<?= $renk ?>;<?= $rolAktif ? 'background:rgba(99,102,241,0.08);font-weight:700;' : '' ?>"
                                           href="index.php?sayfa=uyeler&aksiyon=ek_roller_toggle&id=<?= $uye['id'] ?>&rol=<?= rawurlencode($rolAdi) ?>">
                                            <?php if ($rolAktif): ?>
                                            <i class="fa-solid fa-circle-check" style="color:#6366f1;"></i>
                                            <?php else: ?>
                                            <i class="fa-solid fa-<?= $ikon ?>"></i>
                                            <?php endif; ?>
                                            <?= htmlspecialchars($rolAdi) ?>
                                            <?php if ($rolAktif): ?>
                                            <span class="ms-auto badge" style="background:#6366f1;font-size:0.6rem;">Aktif</span>
                                            <?php endif; ?>
                                        </a>
                                    </li>
                                    <?php endforeach; ?>

                                    <li><hr class="dropdown-divider my-1"></li>
                                    <li>
                                        <a class="dropdown-item text-danger fw-bold py-1"
                                           href="index.php?sayfa=uyeler&aksiyon=uye_sil&id=<?= $uye['id'] ?>"
                                           onclick="return confirm('<?= htmlspecialchars($uye['adi_soyadi']) ?> isimli üyeyi silmek istediğinize emin misiniz?')">
                                            <i class="fa-solid fa-trash-can me-1"></i>Üyeyi Sil
                                        </a>
                                    </li>
                                </ul>
                            </div>
                            <?php endif; ?>
                        </td>
                    </tr>
                    <?php endforeach; ?>
                    <?php else: ?>
                    <tr><td colspan="10" class="ul-bos">
                        <i class="fa-solid fa-folder-open" style="color:#dee2e6;"></i>
                        Henüz kayıtlı onaylı üye bulunmamaktadır.
                    </td></tr>
                    <?php endif; ?>
                </tbody>
            </table>
            <?php endif; ?>

        </div>
    </div>

    <!-- ── SAYFALAMA ──────────────────────────────────── -->
    <div id="sayfalamaKutusu">
        <?php if ($toplam_sayfa > 1): ?>
        <nav aria-label="Sayfalama" class="ul-sayfalama">
            <ul class="pagination mb-0">
                <li class="page-item <?= ($mevcut_sayfa <= 1) ? 'disabled' : '' ?>">
                    <a class="page-link" href="index.php?sayfa=uyeler&p=<?= $mevcut_sayfa - 1 ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : '' ?>">
                        <i class="fa-solid fa-chevron-left"></i>
                    </a>
                </li>
                <?php for ($i = 1; $i <= $toplam_sayfa; $i++): ?>
                <li class="page-item <?= ($mevcut_sayfa == $i) ? 'active' : '' ?>">
                    <a class="page-link" href="index.php?sayfa=uyeler&p=<?= $i ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : '' ?>"><?= $i ?></a>
                </li>
                <?php endfor; ?>
                <li class="page-item <?= ($mevcut_sayfa >= $toplam_sayfa) ? 'disabled' : '' ?>">
                    <a class="page-link" href="index.php?sayfa=uyeler&p=<?= $mevcut_sayfa + 1 ?><?= !empty($aktif_filtre) ? '&filtre='.$aktif_filtre : '' ?>">
                        <i class="fa-solid fa-chevron-right"></i>
                    </a>
                </li>
            </ul>
        </nav>
        <?php endif; ?>
    </div>

</div>

<!-- ── BÖLGE MODAL (Bootstrap) ──────────────────────── -->
<?php if (!$is_kisitli_rol): ?>
<div class="modal fade" id="bolgeModal" tabindex="-1" aria-labelledby="bolgeModalLabel" aria-hidden="true">
  <div class="modal-dialog modal-dialog-centered">
    <div class="modal-content border-0 shadow-lg" style="border-radius:18px;overflow:hidden;">
      <div class="modal-header" style="background:linear-gradient(135deg,#1a1a2e,#16213e);border:none;">
        <h5 class="modal-title fw-bold text-white" id="bolgeModalLabel">
            <i class="fa-solid fa-earth-americas me-2" style="color:#00c9a7;"></i>Sorumlu Bölge Seçimi
        </h5>
        <button type="button" class="btn-close btn-close-white" data-bs-dismiss="modal" aria-label="Kapat"></button>
      </div>
      <div class="modal-body p-4">
        <p class="text-muted small mb-3">Bu üyenin koordine edeceği Türkiye coğrafi bölgesini seçiniz:</p>
        <input type="hidden" id="modalUyeId" value="">
        <div class="d-grid gap-2">
            <?php foreach (['Marmara Bölgesi','Karadeniz Bölgesi','İç Anadolu Bölgesi','Ege Bölgesi','Akdeniz Bölgesi','Doğu Anadolu Bölgesi','Güneydoğu Anadolu Bölgesi'] as $bolge): ?>
            <button onclick="bolgeAta('<?= $bolge ?>')"
                    class="btn fw-bold text-start"
                    style="background:#f8f9fa;border:1.5px solid #e9ecef;border-radius:10px;color:#1a1a2e;transition:all .15s;"
                    onmouseover="this.style.background='#1a1a2e';this.style.color='#00c9a7';"
                    onmouseout="this.style.background='#f8f9fa';this.style.color='#1a1a2e';">
                <i class="fa-solid fa-circle-dot me-2" style="color:#00c9a7;"></i><?= $bolge ?>
            </button>
            <?php endforeach; ?>
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
        let sayfalama = document.getElementById('sayfalamaKutusu');
        if (kelime.length >= 2 || kelime.length === 0) {
            if (kelime.length > 0) { if (sayfalama) sayfalama.style.display = 'none'; }
            else                   { if (sayfalama) sayfalama.style.display = 'block'; }
            let aktifFiltre = '<?= $aktif_filtre ?>';
            let url = 'inc/canli-ara.php?kelime=' + encodeURIComponent(kelime);
            if (aktifFiltre !== '') url += '&filtre=' + encodeURIComponent(aktifFiltre);
            fetch(url)
                .then(r => r.text())
                .then(html => {
                    document.getElementById('uyeTabloGövdesi').innerHTML = html;
                    [].slice.call(document.querySelectorAll('.dropdown-toggle')).map(el => new bootstrap.Dropdown(el));
                });
        }
    }, 200);
}

function dosyaYonlendir(tur) {
    <?php if ($is_kisitli_rol || $is_yonetim): ?>
        alert('Bu kullanıcı yetkisi ile dosya indirme işlemi kısıtlanmıştır.');
        return;
    <?php endif; ?>
    let aramaKelimesi = (document.getElementById('tabloCanliAra') || {value:''}).value.trim();
    let aktifFiltre   = '<?= $aktif_filtre ?>';
    let cinsiyet      = (document.getElementById('indirCinsiyetFiltre') || {value:''}).value;
    let base          = tur === 'excel' ? 'inc/excel-indir.php' : 'inc/pdf-indir.php';
    let params        = [];
    if (aramaKelimesi) params.push('arama=' + encodeURIComponent(aramaKelimesi));
    if (aktifFiltre)   params.push('filtre=' + encodeURIComponent(aktifFiltre));
    if (cinsiyet)      params.push('cinsiyet=' + encodeURIComponent(cinsiyet));
    window.open(base + (params.length ? '?' + params.join('&') : ''), '_blank');
}

function bolgeSecimPenceresi(uyeId) {
    <?php if (!$is_kisitli_rol): ?>
    document.getElementById('modalUyeId').value = uyeId;
    new bootstrap.Modal(document.getElementById('bolgeModal')).show();
    <?php endif; ?>
}

function bolgeAta(bolgeAdi) {
    <?php if (!$is_kisitli_rol): ?>
    var uyeId = document.getElementById('modalUyeId').value;
    if (uyeId) window.location.href = 'index.php?sayfa=uyeler&aksiyon=statü_degistir&id=' + uyeId + '&tur=Bölge+Koordinatörü&bolge=' + encodeURIComponent(bolgeAdi);
    <?php endif; ?>
}
</script>
