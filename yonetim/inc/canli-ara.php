<?php
// Bu dosya yonetim/inc/canli-ara.php olarak kaydedilecektir.
require_once __DIR__ . '/../inc/baglan.php';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    die("Yetkisiz erişim!");
}

$kullanici_rolu = isset($_SESSION['rol']) ? $_SESSION['rol'] : 'admin';
$is_denetci   = ($kullanici_rolu === 'denetci');
$is_moderator = ($kullanici_rolu === 'moderator');

// Yeni roller
$is_admin             = ($kullanici_rolu === 'admin');
$is_yonetim           = ($kullanici_rolu === 'yonetim');
$is_il_baskani        = ($kullanici_rolu === 'il_baskani');
$is_ilce_baskani      = ($kullanici_rolu === 'ilce_baskani');
$is_kurum_temsilcisi  = ($kullanici_rolu === 'kurum_temsilcisi');
$is_kisitli_rol       = ($is_il_baskani || $is_ilce_baskani || $is_kurum_temsilcisi);

$arama_kelimesi = isset($_GET['kelime']) ? trim($_GET['kelime']) : '';
$aktif_filtre   = isset($_GET['filtre']) ? trim($_GET['filtre']) : '';

try {
    // Dinamik taban SQL şartı oluşturuyoruz
    $where_sartlari = ["onay_durumu = 'onayli'"];
    $parametreler   = [];

    // Rol bazlı ek filtre
    if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
        $where_sartlari[] = "ikamet_ili = ?";
        $parametreler[] = $_SESSION['sorumlu_il'];
    } elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
        $where_sartlari[] = "trabzon_ilcesi = ?";
        $parametreler[] = $_SESSION['sorumlu_ilce'];
    } elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
        $where_sartlari[] = "kurum = ?";
        $parametreler[] = $_SESSION['sorumlu_kurum'];
    }

    // 1. Dashboard kart filtreleri (Hem ana statüye hem ek göreve bakar)
    if ($aktif_filtre === 'kurum_temsilcisi') {
        $where_sartlari[] = "(temsilci_turu = 'Kurum Temsilcisi' OR ek_gorev = 'Kurum Temsilcisi')";
    } elseif ($aktif_filtre === 'yonetim_kurulu') {
        $where_sartlari[] = "(temsilci_turu = 'Yönetim Kurulu Üyesi' OR temsilci_turu = 'Yönetim Kurulu Üyesi Yedek' OR temsilci_turu = 'Yönetici' OR ek_gorev = 'Yönetim Kurulu Üyesi' OR ek_gorev = 'Yönetim Kurulu Üyesi Yedek' OR ek_gorev = 'Yönetici')";
    } elseif ($aktif_filtre === 'bolge_koordinatoru') {
        $where_sartlari[] = "(temsilci_turu = 'Bölge Koordinatörü' OR ek_gorev = 'Bölge Koordinatörü')";
    } elseif ($aktif_filtre === 'il_baskani') {
        $where_sartlari[] = "(temsilci_turu = 'İl Başkanı' OR temsilci_turu = 'İl Temsilcisi' OR ek_gorev = 'İl Başkanı' OR ek_gorev = 'İl Temsilcisi')";
    } elseif ($aktif_filtre === 'ilce_baskani') {
        $where_sartlari[] = "(temsilci_turu = 'İlçe Başkanı' OR temsilci_turu = 'İlçe Temsilcisi' OR ek_gorev = 'İlçe Başkanı' OR ek_gorev = 'İlçe Temsilcisi')";
    } elseif ($aktif_filtre === 'teskilatlanma_sorumlusu') {
        $where_sartlari[] = "(temsilci_turu = 'Teşkilatlanma Sorumlu Başkan' OR ek_gorev = 'Teşkilatlanma Sorumlu Başkan')";
    }

    // 2. Canlı arama kutusu
    if ($arama_kelimesi !== '') {
        $where_sartlari[] = "(adi_soyadi LIKE ? OR ikamet_ili LIKE ? OR trabzon_ilcesi LIKE ? OR kurum LIKE ? OR gorev_unvan LIKE ? OR kan_grubu LIKE ? OR eposta LIKE ? OR telefon LIKE ? OR sorumlu_bolge LIKE ? OR ek_gorev LIKE ?)";
        $arama_wildcard = '%' . $arama_kelimesi . '%';
        for ($i = 0; $i < 10; $i++) {
            $parametreler[] = $arama_wildcard;
        }
        
        $sql_text = "SELECT * FROM dernek_uyeler WHERE " . implode(" AND ", $where_sartlari) . " ORDER BY adi_soyadi ASC";
    } else {
        $limit_text = (!empty($aktif_filtre)) ? "" : " LIMIT 50 OFFSET 0";
        $sql_text = "SELECT * FROM dernek_uyeler WHERE " . implode(" AND ", $where_sartlari) . " ORDER BY adi_soyadi ASC" . $limit_text;
    }

    $sorgu = $db_baglanti->prepare($sql_text);
    $sorgu->execute($parametreler);
    $uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
    
    if (count($uyeler) > 0) {
        foreach ($uyeler as $uye) {
            $satir_klasi = "";
            $rozet_klasi = "bg-secondary";
            $ek_unvan_yazisi = "";

            // --- CİNSİYET TESPİT MOTORU ---
            $parcalar = explode(' ', trim($uye['adi_soyadi']));
            $ilk_isim = mb_strtoupper($parcalar[0], 'UTF-8');
            $kadin_isimleri = ['AYŞEGÜL', 'BEGÜM', 'HATİCE', 'FATMA', 'AYŞE', 'EMİNE', 'ZEYNEP', 'MERYEM', 'ELİF', 'HÜLYA', 'GAMZE', 'MERVE', 'BÜŞRA', 'ESRA', 'SEDA', 'DERYA', 'KÜBRA', 'ASLI', 'PELİN', 'TUĞBA', 'DEMET', 'ÖZLEM', 'SİNEM', 'GÜL', 'NUR', 'MELİS', 'DİLAN', 'BURCU', 'CANAN', 'SULTAN', 'MELİKE', 'YASEMİN', 'EDA', 'DİDIDEM', 'BERNA', 'SELEN', 'PINAR', 'BANU', 'YEŞİM', 'EBRU', 'FADİME', 'NURAN', 'SELMA', 'DİLEK', 'FİLİZ', 'ARZU', 'LEYLA', 'SİBEL', 'HALE', 'JALE', 'GONCA', 'MÜGE', 'NESLİHAN', 'NAZLI'];
            
            if (in_array($ilk_isim, $kadin_isimleri)) {
                $ikon_renk = 'color: #e83e8c !important;'; 
                $ikon_sekil = 'fa-user-nurse';
            } else {
                $ikon_renk = 'color: #007bff !important;'; 
                $ikon_sekil = 'fa-user';
            }

            $temsilci_turu_kontrol = trim($uye['temsilci_turu']);
            $ek_gorev_kontrol     = trim($uye['ek_gorev'] ?? '');

            if ($temsilci_turu_kontrol === 'Yönetim Kurulu Üyesi') {
                $satir_klasi = 'class="table-primary"'; 
                $rozet_klasi = "bg-primary text-white";
                $sol_ikon = '<i class="fa-solid fa-user-shield me-2" style="'.$ikon_renk.'"></i>';
            } elseif ($temsilci_turu_kontrol === 'Yönetim Kurulu Üyesi Yedek') {
                $satir_klasi = 'class="table-info"'; 
                $rozet_klasi = "bg-info text-dark";
                $sol_ikon = '<i class="fa-solid fa-user-shield me-2" style="'.$ikon_renk.'"></i>';
            } elseif ($temsilci_turu_kontrol === 'İl Başkanı') {
                $satir_klasi = 'class="table-success"'; 
                $rozet_klasi = "bg-success text-white";
                $sol_ikon = '<i class="fa-solid fa-building-flag me-2" style="'.$ikon_renk.'"></i>';
            } elseif ($temsilci_turu_kontrol === 'İlçe Başkanı') {
                $satir_klasi = 'class="ilce-baskani-satir"'; 
                $rozet_klasi = "text-white";
                $sol_ikon = '<i class="fa-solid fa-map-location-dot me-2" style="'.$ikon_renk.'"></i>';
            } elseif ($temsilci_turu_kontrol === 'Kurum Temsilcisi') {
                $satir_klasi = 'class="table-warning"'; 
                $rozet_klasi = "bg-warning text-dark";
                $sol_ikon = '<i class="fa-solid fa-building-user me-2" style="'.$ikon_renk.'"></i>';
            } elseif ($temsilci_turu_kontrol === 'Bölge Koordinatörü') {
                $satir_klasi = 'class="bolge-koordinator-satir"'; 
                $rozet_klasi = "text-white";
                $sol_ikon = '<i class="fa-solid fa-earth-americas me-2" style="'.$ikon_renk.'"></i>';
                if(!empty($uye['sorumlu_bolge'])) {
                    $ek_unvan_yazisi = '<br><small class="text-secondary fw-semibold"><i class="fa-solid fa-location-crosshairs me-1"></i>'.htmlspecialchars($uye['sorumlu_bolge']).'</small>';
                }
            } else {
                $sol_ikon = '<i class="fa-solid '.$ikon_sekil.' me-2" style="'.$ikon_renk.'"></i>';
            }

            // Statü Rozet Boyutları Standartlaştırıldı
            $statü_rozet_html = '<span class="badge '.$rozet_klasi.' statu-rozet fw-semibold" '.($temsilci_turu_kontrol === 'İlçe Başkanı' ? 'style="background-color: #6a1b9a !important;"' : ($temsilci_turu_kontrol === 'Bölge Koordinatörü' ? 'style="background-color: #00838f !important;"' : '')).'>'.htmlspecialchars($uye['temsilci_turu']).'</span>';
            
            if (!empty($ek_gorev_kontrol)) {
                $statü_rozet_html .= '<div class="mt-1"><span class="badge bg-dark text-white statu-rozet small" title="Ek Görev"><i class="fa-solid fa-plus-circle text-warning me-1"></i>'.htmlspecialchars($ek_gorev_kontrol).'</span></div>';
            }
            
            $kan = !empty($uye['kan_grubu']) ? $uye['kan_grubu'] : '-';
            $dogum = !empty($uye['dogum_tarihi']) ? date('d.m.Y', strtotime($uye['dogum_tarihi'])) : (!empty($uye['dogum_yili']) ? $uye['dogum_yili'] : '-');
            
            // İŞLEM / YETKİ HÜCRESİ
            if ($is_denetci) {
                $islem_icerik = '<span class="badge bg-secondary text-white px-2 py-1"><i class="fa-solid fa-eye me-1"></i>Sadece İnceleme</span>';
            } elseif ($is_moderator) {
                $islem_icerik = '<span class="badge bg-warning text-dark px-2 py-1"><i class="fa-solid fa-lock me-1"></i>Yetki Yok</span>';
            } elseif ($is_kisitli_rol) {
                $islem_icerik = '<span class="badge bg-info text-dark px-2 py-1"><i class="fa-solid fa-eye me-1"></i>Sadece Görüntüleme</span>';
            } else {
                $islem_icerik = '
                <div class="btn-group dropup position-static">
                    <button class="btn btn-dark btn-sm dropdown-toggle fw-bold shadow-sm" type="button" data-bs-toggle="dropdown" aria-expanded="false" data-bs-popper-config=\'{"strategy":"fixed"}\'>
                        <i class="fa-solid fa-user-gear me-1"></i> Yönet
                    </button>
                    <ul class="dropdown-menu dropdown-menu-end shadow-lg border-0 kucuk-yonet-menu">
                        <li><h6 class="dropdown-header fw-bold text-uppercase py-1" style="font-size: 0.72rem;">Ana Statü Değiştir</h6></li>
                        <li><a class="dropdown-item text-info fw-bold py-1" href="javascript:void(0);" onclick="bolgeSecimPenceresi('.$uye['id'].')"><i class="fa-solid fa-earth-americas me-1.5"></i>Bölge Koordinatörü Yap</a></li>';
                        
                        if($temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi') {
                            $islem_icerik .= '<li><a class="dropdown-item text-primary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-user-shield me-1.5"></i>Yönetim Kurulu Üyesi Yap</a></li>';
                        }

                        if($temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi Yedek') {
                            $islem_icerik .= '<li><a class="dropdown-item text-info py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-user-shield me-1.5"></i>Yönetim Kurulu Üyesi Yedek Yap</a></li>';
                        }

                        if($temsilci_turu_kontrol !== 'İl Başkanı') {
                            $islem_icerik .= '<li><a class="dropdown-item text-success py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=İl+Başkanı"><i class="fa-solid fa-building-flag me-1.5"></i>İl Başkanı Yap</a></li>';
                        }
                        if($temsilci_turu_kontrol !== 'İlçe Başkanı') {
                            $islem_icerik .= '<li><a class="dropdown-item py-1" style="color: #6a1b9a;" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=İlçe+Başkanı"><i class="fa-solid fa-map-location-dot me-1.5"></i>İlçe Başkanı Yap</a></li>';
                        }
                        if($temsilci_turu_kontrol !== 'Kurum Temsilcisi') {
                            $islem_icerik .= '<li><a class="dropdown-item text-warning py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=Kurum+Temsilcisi"><i class="fa-solid fa-building-user me-1.5"></i>Kurum Temsilcisi Yap</a></li>';
                        }
                        if($temsilci_turu_kontrol !== 'Normal Üye') {
                            $islem_icerik .= '<li><a class="dropdown-item text-secondary py-1" href="index.php?sayfa=uyeler&aksiyon=stat%C3%BC_degistir&id='.$uye['id'].'&tur=Normal+Üye"><i class="fa-solid fa-user-minus me-1.5"></i>Normal Üyeliğe Çek</a></li>';
                        }

                        // EK GÖREV MENÜSÜ
                        $islem_icerik .= '<li><hr class="dropdown-divider my-1"></li>';
                        $islem_icerik .= '<li><h6 class="dropdown-header text-dark fw-bold text-uppercase py-1" style="font-size: 0.72rem;">Ek Görev Atamaları</h6></li>';

                        if ($ek_gorev_kontrol !== 'Yönetim Kurulu Üyesi' && $temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi') {
                            $islem_icerik .= '<li><a class="dropdown-item text-primary fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id='.$uye['id'].'&gorev=Yönetim+Kurulu+Üyesi"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Yönetim Kurulu Üyesi Yap</a></li>';
                        }
                        if ($ek_gorev_kontrol !== 'Yönetim Kurulu Üyesi Yedek' && $temsilci_turu_kontrol !== 'Yönetim Kurulu Üyesi Yedek') {
                            $islem_icerik .= '<li><a class="dropdown-item text-info fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id='.$uye['id'].'&gorev=Yönetim+Kurulu+Üyesi+Yedek"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Y. Kurulu Yedek Yap</a></li>';
                        }
                        if ($ek_gorev_kontrol !== 'Teşkilatlanma Sorumlu Başkan' && $temsilci_turu_kontrol !== 'Teşkilatlanma Sorumlu Başkan') {
                            $islem_icerik .= '<li><a class="dropdown-item fw-bold py-1" style="color: #e65100;" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id='.$uye['id'].'&gorev=Teşkilatlanma+Sorumlu+Başkan"><i class="fa-solid fa-plus me-1.5"></i>+ Görev: Teşkilatlanma Sor. Bşk.</a></li>';
                        }

                        if (!empty($ek_gorev_kontrol)) {
                            $islem_icerik .= '<li><a class="dropdown-item text-danger py-1" href="index.php?sayfa=uyeler&aksiyon=ek_gorev_degistir&id='.$uye['id'].'&gorev=sil"><i class="fa-solid fa-xmark me-1.5"></i>Görev İptal Et/Sil</a></li>';
                        }
                        
                        $islem_icerik .= '<li><hr class="dropdown-divider my-1"></li>
                        <li><a class="dropdown-item text-danger fw-bold py-1" href="index.php?sayfa=uyeler&aksiyon=uye_sil&id='.$uye['id'].'" onclick="return confirm(\''.htmlspecialchars($uye['adi_soyadi']).' isimli üyeyi tamamen silmek istediğinize emin misiniz?\');"><i class="fa-solid fa-trash-can me-1.5"></i> Üyeyi Sil</a></li>
                    </ul>
                </div>';
            }

            // SAF TABLO SATIRI ÇIKTISI
            echo '<tr '.$satir_klasi.' style="transition: background-color 0.2s;">
                    <td class="ps-3 fw-bold">
                        <a href="index.php?sayfa=uye-detay&id='.$uye['id'].'" class="text-decoration-none text-dark d-block py-1 hover-link">
                            <div class="d-flex align-items-center">
                                '.$sol_ikon.' 
                                <div>
                                    '.htmlspecialchars($uye['adi_soyadi']).'
                                    '.$ek_unvan_yazisi.'
                                </div>
                            </div>
                        </a>
                    </td>
                    <td style="white-space: nowrap; font-weight: 500; font-size: 0.9rem;">'.htmlspecialchars($uye['telefon'] ?: '-').'</td>
                    <td><small class="text-truncate d-inline-block" style="max-width: 140px;">'.htmlspecialchars($uye['eposta'] ?: '-').'</small></td>
                    <td class="text-center"><span class="badge bg-danger text-white">'.htmlspecialchars($kan).'</span></td>
                    <td class="text-center"><small>'.htmlspecialchars($dogum).'</small></td>
                    <td><strong>'.htmlspecialchars($uye['ikamet_ili'] ?: '-').'</strong><br><small class="text-muted">'.htmlspecialchars($uye['trabzon_ilcesi'] ?: '-').'</small></td>
                    <td><small>'.htmlspecialchars($uye['kurum'] ?: '-').'</small><br><small class="text-muted">'.htmlspecialchars($uye['gorev_unvan'] ?: '-').'</small></td>
                    <td><small>'.htmlspecialchars($uye['calisma_sekli'] ?: '-').'</small></td>
                    <td class="text-center">
                        '.$statü_rozet_html.'
                    </td>
                    <td class="text-center pe-3">
                        '.$islem_icerik.'
                    </td>
                  </tr>';
        }
    } else {
        echo '<tr><td colspan="10" class="text-center py-5 text-muted"><i class="fa-solid fa-folder-open fa-3x mb-3 d-block text-secondary"></i>Aranan kriterlere uygun üye bulunmamaktadır.</td></tr>';
    }
} catch (\Exception $e) {
    error_log('Yönetim canlı arama hatası: ' . $e->getMessage());
    die('Arama sırasında bir hata oluştu.');
}