<?php
// Bu dosya inc/uye-ekle.php olarak kaydedilecek.

$mesaj = "";
$mesaj_turu = "";

// Form gönderildiğinde çalışacak kodlar
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $adi_soyadi      = trim($_POST['adi_soyadi']);
    $telefon         = trim($_POST['telefon']);
    $eposta          = trim($_POST['eposta']);
    $kan_grubu       = trim($_POST['kan_grubu']); 
    
    // Gelen GG/AA/YYYY formatını veritabanının kabul ettiği YYYY-MM-DD formatına çeviriyoruz
    $dogum_input     = trim($_POST['dogum_tarihi']);
    $dogum_tarihi    = null;
    if (!empty($dogum_input)) {
        $parcalar = explode('/', $dogum_input);
        if (count($parcalar) === 3) {
            $dogum_tarihi = $parcalar[2] . '-' . $parcalar[1] . '-' . $parcalar[0];
        }
    }
    
    $ikamet_ili      = trim($_POST['ikamet_ili']);
    $kurum           = trim($_POST['kurum']);
    $gorev_unvan     = trim($_POST['gorev_unvan']);
    $calisma_sekli   = trim($_POST['calisma_sekli']);
    $trabzon_ilcesi  = trim($_POST['trabzon_ilcesi']);
    $temsilci_turu   = trim($_POST['temsilci_turu']);

    if (!empty($adi_soyadi)) {
        try {
            $sql = "INSERT INTO dernek_uyeler 
                    (adi_soyadi, telefon, eposta, kan_grubu, dogum_tarihi, ikamet_ili, kurum, gorev_unvan, calisma_sekli, trabzon_ilcesi, temsilci_turu, onay_durumu) 
                    VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'onayli')";
            
            $sorgu = $db_baglanti->prepare($sql);
            $durum = $sorgu->execute([
                $adi_soyadi, $telefon, $eposta, $kan_grubu, $dogum_tarihi, $ikamet_ili, 
                $kurum, $gorev_unvan, $calisma_sekli, $trabzon_ilcesi, $temsilci_turu
            ]);

            if ($durum) {
                $mesaj = "Üye kaydı başarıyla tamamlandı!";
                $mesaj_turu = "success";

                log_kaydet($db_baglanti, 'uye_ekle', 'Manuel üye eklendi: ' . $adi_soyadi, 'dernek_uyeler', (int) $db_baglanti->lastInsertId());
            } else {
                $mesaj = "Kayıt sırasında bir hata oluştu.";
                $mesaj_turu = "danger";
            }
        } catch (\PDOException $e) {
            $mesaj = "Veritabanı Hatası: " . $e->getMessage();
            $mesaj_turu = "danger";
        }
    } else {
        $mesaj = "Adı Soyadı alanı zorunludur!";
        $mesaj_turu = "warning";
    }
}

$iller = ["Adana","Adıyaman","Afyonkarahisar","Ağrı","Amasya","Ankara","Antalya","Artvin","Aydın","Balıkesir","Bilecik","Bingöl","Bitlis","Bolu","Burdur","Bursa","Çanakkale","Çankırı","Çorum","Denizli","Diyarbakır","Edirne","Elazığ","Erzincan","Erzurum","Eskişehir","Gaziantep","Giresun","Gümüşhane","Hakkari","Hatay","Isparta","Mersin","İstanbul","İzmir","Kars","Kastamonu","Kayseri","Kırklareli","Kırşehir","Kocaeli","Konya","Kütahya","Malatya","Manisa","Kahramanmaraş","Mardin","Muğla","Muş","Nevşehir","Niğde","Ordu","Rize","Sakarya","Samsun","Siirt","Sinop","Sivas","Tekirdağ","Tokat","Trabzon","Tunceli","Şanlıurfa","Uşak","Van","Yozgat","Zonguldak","Aksaray","Bayburt","Karaman","Kırıkkale","Batman","Şırnak","Bartın","Ardahan","Iğdır","Yalova","Karabük","Kilis","Osmaniye","Düzce"];
$trabzon_ilceleri = ["Akçaabat", "Araklı", "Arsin", "Beşikdüzü", "Çarşıbaşı", "Çaykara", "Dernekpazarı", "Düzköy", "Hayrat", "Köprübaşı", "Maçka", "Of", "Ortahisar", "Sürmene", "Şalpazarı", "Tonya", "Vakfıkebir", "Yomra"];
$kan_gruplari = ["A Rh+", "A Rh-", "B Rh+", "B Rh-", "AB Rh+", "AB Rh-", "0 Rh+", "0 Rh-"];
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12 text-center text-md-start">
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-user-plus me-2"></i>Yeni Üye Kayıt Formu</h2>
            <p class="text-muted">Derneğe yeni üye veya temsilci eklemek için form alanlarını doldurunuz.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 mx-auto">
            
            <?php if (!empty($mesaj)): ?>
                <div class="alert alert-<?= $mesaj_turu; ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
                    <strong><i class="fa-solid fa-circle-info me-2"></i></strong> <?= $mesaj; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>

            <div class="card border-0 shadow-sm rounded-3 mb-5">
                <div class="card-body p-4">
                    <form action="index.php?sayfa=uye-ekle" method="POST">
                        
                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-id-card me-2"></i>Kişisel Bilgiler</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Adı Soyadı <span class="text-danger">*</span></label>
                                <input type="text" name="adi_soyadi" class="form-control" required placeholder="Örn: Ahmet Yılmaz">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Telefon Numarası</label>
                                <input type="tel" id="inputTelefon" name="telefon" class="form-control" oninput="telefonFormatla(this)" maxlength="14" placeholder="0535 418 61 61">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">E-Posta Adresi</label>
                                <input type="email" name="eposta" class="form-control" placeholder="Örn: ahmetyılmaz@eposta.com">
                            </div>
                            
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Kan Grubu</label>
                                <select name="kan_grubu" class="form-select">
                                    <option value="">-- Kan Grubu Seçiniz --</option>
                                    <?php foreach($kan_gruplari as $kg): ?>
                                        <option value="<?= $kg; ?>"><?= $kg; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>

                            <!-- GÜNCELLEME: TIKLAYINCA SİLİNEN VE / İŞARETİNİ OTOMATİK KOYAN AKILLI TARİH MASKESİ -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Doğum Tarihi</label>
                                <input type="text" name="dogum_tarihi" class="form-control" oninput="tarihFormatla(this)" onfocus="tarihTemizle(this)" onblur="tarihGeriYukle(this)" maxlength="10" placeholder="GG/AA/YYYY">
                            </div>

                            <div class="col-md-6">
                                <label class="form-label fw-semibold">İkamet Edilen İl (Türkiye)</label>
                                <select name="ikamet_ili" class="form-select">
                                    <option value="">-- İl Seçiniz --</option>
                                    <?php foreach($iller as $il): ?>
                                        <option value="<?= $il; ?>"><?= $il; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Trabzon İlçesi (Nüfusa Kayıtlı)</label>
                                <select name="trabzon_ilcesi" class="form-select">
                                    <option value="">-- İlçe Seçiniz --</option>
                                    <?php foreach($trabzon_ilceleri as $ilce): ?>
                                        <option value="<?= $ilce; ?>"><?= $ilce; ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>

                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-briefcase me-2"></i>Mesleki / Kurumsal Bilgiler</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Çalıştığı Kurum</label>
                                <input type="text" name="kurum" class="form-control" placeholder="Örn: Maliye Bakanlığı, Valilik">
                            </div>
                            <div class="col-md-6">
                                <label class="form-label fw-semibold">Görev / Ünvan</label>
                                <input type="text" name="gorev_unvan" class="form-control" placeholder="Örn: Uzman, Mühendis, Müdür">
                            </div>
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Çalışma Şekli</label>
                                <input type="text" name="calisma_sekli" class="form-control" placeholder="Örn: Kadrolu, Sözleşmeli, Taşeron">
                            </div>
                        </div>

                        <h5 class="text-danger fw-bold border-bottom pb-2 mb-3"><i class="fa-solid fa-user-shield me-2"></i>Dernek Yönetim Statüsü</h5>
                        
                        <div class="row g-3 mb-4">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold">Üyelik / Temsilcilik Türü</label>
                                <!-- GÜNCELLEME: KUTU SEÇİLDİĞİNDE ARKA PLAN RENGİNİ DİNAMİK DEĞİŞTİREN SELECT YAPISI -->
                                <select id="selectTemsilciTuru" name="temsilci_turu" class="form-select border-danger fw-bold" onchange="statüRenkDegistir(this)">
                                    <option value="Normal Üye" class="fw-bold" style="background-color: #ffffff; color: #333333;">Normal Üye (Temsilci Değil)</option>
                                    <option value="Yönetim Kurulu Üyesi" class="fw-bold" style="background-color: #CFE2FF; color: #084298;">Yönetim Kurulu Üyesi</option>
                                    <option value="İl Başkanı" class="fw-bold" style="background-color: #D1E7DD; color: #0f5132;">İl Başkanı (Türkiye Geneli)</option>
                                    <option value="İlçe Başkanı" class="fw-bold" style="background-color: #f3e5f5; color: #4a148c;">İlçe Başkanı</option>
                                    <option value="Kurum Temsilcisi" class="fw-bold" style="background-color: #FFF3CD; color: #664d03;">Kurum Temsilcisi</option>
                                </select>
                            </div>
                        </div>

                        <div class="d-grid gap-2 mt-4">
                            <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                                <i class="fa-solid fa-floppy-disk me-2"></i>Üyeyi Veritabanına Kaydet
                            </button>
                        </div>

                    </form>
                </div>
            </div>

        </div>
    </div>
</div>

<script>
// TELEFON FORMATLAYICI
function telefonFormatla(element) {
    var temizNumara = element.value.replace(/\D/g, '');
    var formatliMetin = '';
    if (temizNumara.length > 0) {
        formatliMetin += temizNumara.substring(0, 4);
        if (temizNumara.length > 4) formatliMetin += ' ' + temizNumara.substring(4, 7);
        if (temizNumara.length > 7) formatliMetin += ' ' + temizNumara.substring(7, 9);
        if (temizNumara.length > 9) formatliMetin += ' ' + temizNumara.substring(9, 11);
    }
    element.value = formatliMetin;
}

// AKILLI TARİH OTOMATİK SMAÇLAYICI (/) 
function textTarihFormatla(v) {
    v = v.replace(/\D/g, ""); // Sadece rakamlar
    if (v.length > 2 && v.length <= 4) {
        v = v.substring(0, 2) + "/" + v.substring(2);
    } else if (v.length > 4) {
        v = v.substring(0, 2) + "/" + v.substring(2, 4) + "/" + v.substring(4, 8);
    }
    return v;
}

function tarihFormatla(element) {
    element.value = textTarihFormatla(element.value);
}

function tarihTemizle(element) {
    if (element.value === "GG/AA/YYYY") {
        element.value = "";
    }
}

function tarihGeriYukle(element) {
    if (element.value.trim() === "") {
        element.placeholder = "GG/AA/YYYY";
    }
}

// SEÇİM KUTUSU SEÇİLDİKÇE KUTUYU O RENGE BOYAYAN MOTOR
function statüRenkDegistir(selectElement) {
    var deger = selectElement.value;
    // Bootstrap sınıflarını temizleyip inline stillerle tam eşleme yapıyoruz
    selectElement.style.color = "";
    selectElement.style.backgroundColor = "";

    if (deger === "Yönetim Kurulu Üyesi") {
        selectElement.style.backgroundColor = "#CFE2FF";
        selectElement.style.color = "#084298";
    } else if (deger === "İl Başkanı") {
        selectElement.style.backgroundColor = "#D1E7DD";
        selectElement.style.color = "#0f5132";
    } else if (deger === "İlçe Başkanı") {
        selectElement.style.backgroundColor = "#f3e5f5";
        selectElement.style.color = "#4a148c";
    } else if (deger === "Kurum Temsilcisi") {
        selectElement.style.backgroundColor = "#FFF3CD";
        selectElement.style.color = "#664d03";
    } else {
        selectElement.style.backgroundColor = "#ffffff";
        selectElement.style.color = "#333333";
    }
}

// Sayfa ilk açıldığında varsayılan statü rengini tetiklemek için çalışır
document.addEventListener("DOMContentLoaded", function() {
    statüRenkDegistir(document.getElementById("selectTemsilciTuru"));
});
</script>