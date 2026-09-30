<?php

declare(strict_types=1);

/**
 * Yönetim Paneli — Yeni Üye Ekle
 *
 * Form alanları site (membership.php) ile birebir eşleştirilmiştir:
 *   - Ad / Soyad (ayrı alanlar, birleştirilip adi_soyadi olarak kaydedilir)
 *   - Telefon (05XX prefix + 9 hane)
 *   - E-Posta (zorunlu)
 *   - Kan Grubu (isteğe bağlı)
 *   - Doğum Tarihi (date input, zorunlu)
 *   - İkamet İli + İkamet İlçesi (dinamik, zorunlu)
 *   - Trabzon İlçesi — Nüfusa Kayıtlı (zorunlu)
 *   - Çalıştığı Kurum (zorunlu)
 *   - Görev / Ünvan (zorunlu, gorev_unvan olarak kaydedilir)
 *   - Cinsiyet (zorunlu)
 *   - Çalışma Şekli (select, zorunlu)
 *   - Üyelik / Temsilcilik Türü (admin-only ek alan)
 */

$mesaj     = '';
$mesajTuru = '';

$calismaSekilleri = ['Kadrolu', 'Yarı Zamanlı', 'Sözleşmeli', 'Emekli Kamu Çalışanı'];
$kanGruplari      = ['A Rh+', 'A Rh-', 'B Rh+', 'B Rh-', 'AB Rh+', 'AB Rh-', '0 Rh+', '0 Rh-'];

$iller = [
    'Adana','Adıyaman','Afyonkarahisar','Ağrı','Aksaray','Amasya','Ankara',
    'Antalya','Ardahan','Artvin','Aydın','Balıkesir','Bartın','Batman',
    'Bayburt','Bilecik','Bingöl','Bitlis','Bolu','Burdur','Bursa',
    'Çanakkale','Çankırı','Çorum','Denizli','Diyarbakır','Düzce','Edirne',
    'Elazığ','Erzincan','Erzurum','Eskişehir','Gaziantep','Giresun',
    'Gümüşhane','Hakkari','Hatay','Iğdır','Isparta','İstanbul','İzmir',
    'Kahramanmaraş','Karabük','Karaman','Kars','Kastamonu','Kayseri',
    'Kilis','Kırıkkale','Kırklareli','Kırşehir','Kocaeli','Konya',
    'Kütahya','Malatya','Manisa','Mardin','Mersin','Muğla','Muş',
    'Nevşehir','Niğde','Ordu','Osmaniye','Rize','Sakarya','Samsun',
    'Siirt','Sinop','Sivas','Şanlıurfa','Şırnak','Tekirdağ','Tokat',
    'Trabzon','Tunceli','Uşak','Van','Yalova','Yozgat','Zonguldak',
];
sort($iller, SORT_LOCALE_STRING);

$trabzonIlceleri = [
    'Akçaabat','Araklı','Arsin','Beşikdüzü','Çarşıbaşı',
    'Çaykara','Dernekpazarı','Düzköy','Hayrat','Köprübaşı',
    'Maçka','Of','Ortahisar','Sürmene','Şalpazarı',
    'Tonya','Vakfıkebir','Yomra',
];

// ─── FORM İŞLE ──────────────────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $ad           = trim((string) ($_POST['ad']           ?? ''));
    $soyad        = trim((string) ($_POST['soyad']        ?? ''));
    $telefonHam   = preg_replace('/\D/', '', (string) ($_POST['telefon'] ?? ''));
    $eposta       = trim((string) ($_POST['eposta']       ?? ''));
    $kanGrubu     = trim((string) ($_POST['kan_grubu']    ?? ''));
    $dogumTarihi  = trim((string) ($_POST['dogum_tarihi'] ?? ''));
    $ikametIli    = trim((string) ($_POST['ikamet_ili']   ?? ''));
    $ikametIlcesi = trim((string) ($_POST['ikamet_ilcesi'] ?? ''));
    $trabzonIlce  = trim((string) ($_POST['trabzon_ilcesi'] ?? ''));
    $kurum        = trim((string) ($_POST['kurum']        ?? ''));
    $gorevUnvan   = trim((string) ($_POST['gorev_unvan']  ?? ''));
    $cinsiyet     = trim((string) ($_POST['cinsiyet']     ?? ''));
    $calismaSekli = trim((string) ($_POST['calisma_sekli'] ?? ''));
    $temsilciTuru = trim((string) ($_POST['temsilci_turu'] ?? 'Normal Üye'));

    $adiSoyadi = trim($ad . ' ' . $soyad);

    // Telefonu 05XX formatına tamamla
    if (strlen($telefonHam) === 9) {
        $telefon = '05' . $telefonHam;
    } elseif (strlen($telefonHam) === 10 && str_starts_with($telefonHam, '0')) {
        $telefon = '0' . $telefonHam;
    } else {
        $telefon = $telefonHam;
    }

    // Doğum tarihi (HTML date → YYYY-MM-DD)
    $dogumDB = (!empty($dogumTarihi) && strtotime($dogumTarihi) !== false)
        ? date('Y-m-d', strtotime($dogumTarihi))
        : null;

    // Temel zorunlu alan kontrolü
    $eksikAlanlar = [];
    if ($ad === '')           $eksikAlanlar[] = 'Ad';
    if ($soyad === '')        $eksikAlanlar[] = 'Soyad';
    if ($telefon === '')      $eksikAlanlar[] = 'Telefon';
    if ($eposta === '')       $eksikAlanlar[] = 'E-Posta';
    if ($dogumDB === null)    $eksikAlanlar[] = 'Doğum Tarihi';
    if ($ikametIli === '')    $eksikAlanlar[] = 'İkamet İli';
    if ($ikametIlcesi === '') $eksikAlanlar[] = 'İkamet İlçesi';
    if ($trabzonIlce === '')  $eksikAlanlar[] = 'Trabzon İlçesi';
    if ($kurum === '')        $eksikAlanlar[] = 'Çalıştığı Kurum';
    if ($gorevUnvan === '')   $eksikAlanlar[] = 'Görev / Ünvan';
    if ($cinsiyet === '')     $eksikAlanlar[] = 'Cinsiyet';
    if ($calismaSekli === '') $eksikAlanlar[] = 'Çalışma Şekli';

    if (!empty($eksikAlanlar)) {
        $mesaj     = 'Zorunlu alanlar eksik: ' . implode(', ', $eksikAlanlar);
        $mesajTuru = 'warning';
    } else {
        try {
            $stmt = $db_baglanti->prepare(
                "INSERT INTO dernek_uyeler
                    (adi_soyadi, telefon, eposta, kan_grubu, dogum_tarihi,
                     ikamet_ili, ikamet_ilcesi, trabzon_ilcesi,
                     kurum, gorev_unvan, cinsiyet, calisma_sekli,
                     temsilci_turu, onay_durumu)
                 VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'onayli')"
            );

            $stmt->execute([
                $adiSoyadi, $telefon, $eposta, $kanGrubu, $dogumDB,
                $ikametIli, $ikametIlcesi, $trabzonIlce,
                $kurum, $gorevUnvan, $cinsiyet, $calismaSekli,
                $temsilciTuru,
            ]);

            $yeniId = (int) $db_baglanti->lastInsertId();
            log_kaydet($db_baglanti, 'uye_ekle', "Manuel üye eklendi: {$adiSoyadi}", 'dernek_uyeler', $yeniId);

            $mesaj     = "✅ {$adiSoyadi} başarıyla sisteme eklendi!";
            $mesajTuru = 'success';
        } catch (\PDOException $e) {
            error_log('uye-ekle hata: ' . $e->getMessage());
            $mesaj     = 'Veritabanı hatası oluştu.';
            $mesajTuru = 'danger';
        }
    }
}
?>

<div class="container py-4">
    <div class="row mb-4">
        <div class="col-12">
            <h2 class="fw-bold text-dark mb-1">
                <i class="fa-solid fa-user-plus me-2"></i>Yeni Üye Kayıt Formu
            </h2>
            <p class="text-muted">Derneğe yeni üye veya temsilci eklemek için form alanlarını doldurunuz.</p>
        </div>
    </div>

    <div class="row">
        <div class="col-md-10 mx-auto">

            <?php if ($mesaj !== ''): ?>
            <div class="alert alert-<?= $mesajTuru; ?> alert-dismissible fade show shadow-sm mb-4" role="alert">
                <strong><i class="fa-solid fa-circle-info me-2"></i></strong> <?= htmlspecialchars($mesaj); ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Kapat"></button>
            </div>
            <?php endif; ?>

            <form action="index.php?sayfa=uye-ekle" method="POST" novalidate>

                <!-- ── KİŞİSEL BİLGİLER ──────────────────────────────── -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-id-card me-2"></i>Kişisel Bilgiler
                        </h5>

                        <div class="row g-3">
                            <!-- Ad -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-ad">
                                    Adı <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="ue-ad" name="ad" class="form-control"
                                       placeholder="Adı" maxlength="60" required
                                       autocomplete="given-name" spellcheck="false">
                            </div>
                            <!-- Soyad -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-soyad">
                                    Soyadı <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="ue-soyad" name="soyad" class="form-control"
                                       placeholder="Soyadı" maxlength="60" required
                                       autocomplete="family-name" spellcheck="false">
                            </div>
                            <!-- Telefon -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-telefon">
                                    Telefon Numarası <span class="text-danger">*</span>
                                </label>
                                <div class="input-group">
                                    <span class="input-group-text fw-bold">05</span>
                                    <input type="tel" id="ue-telefon" name="telefon" class="form-control"
                                           placeholder="XX XXX XX XX" maxlength="9"
                                           inputmode="numeric" pattern="[0-9]{9}" required
                                           autocomplete="tel-national" spellcheck="false">
                                </div>
                                <div class="form-text">Başında 05 olmadan 9 rakam giriniz.</div>
                            </div>
                            <!-- E-Posta -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-eposta">
                                    E-Posta Adresi <span class="text-danger">*</span>
                                </label>
                                <input type="email" id="ue-eposta" name="eposta" class="form-control"
                                       placeholder="ornek@kurum.gov.tr" maxlength="254" required
                                       autocomplete="email" autocapitalize="off">
                            </div>
                            <!-- Doğum Tarihi -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-dogum">
                                    Doğum Tarihi <span class="text-danger">*</span>
                                </label>
                                <input type="date" id="ue-dogum" name="dogum_tarihi" class="form-control"
                                       required autocomplete="bday"
                                       max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                                       min="1930-01-01">
                            </div>
                            <!-- Kan Grubu -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-kan">
                                    Kan Grubu <span class="text-muted small">(İsteğe Bağlı)</span>
                                </label>
                                <select id="ue-kan" name="kan_grubu" class="form-select">
                                    <option value="">-- Kan Grubu (İsteğe Bağlı) --</option>
                                    <?php foreach ($kanGruplari as $kg): ?>
                                    <option value="<?= htmlspecialchars($kg) ?>"><?= htmlspecialchars($kg) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- Cinsiyet -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-cinsiyet">
                                    Cinsiyet <span class="text-danger">*</span>
                                </label>
                                <select id="ue-cinsiyet" name="cinsiyet" class="form-select" required>
                                    <option value="">-- Seçiniz --</option>
                                    <option value="Erkek">Erkek</option>
                                    <option value="Kadın">Kadın</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── İKAMET BİLGİLERİ ───────────────────────────────── -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-map-location-dot me-2"></i>İkamet Bilgileri
                        </h5>

                        <div class="row g-3">
                            <!-- İkamet İli -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-ikamet-il">
                                    İkamet Edilen İl <span class="text-danger">*</span>
                                </label>
                                <select id="ue-ikamet-il" name="ikamet_ili" class="form-select" required
                                        onchange="ikametIlceleriniYukle(this.value)">
                                    <option value="">-- İl Seçiniz --</option>
                                    <?php foreach ($iller as $il): ?>
                                    <option value="<?= htmlspecialchars($il) ?>"><?= htmlspecialchars($il) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            <!-- İkamet İlçesi (dinamik) -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-ikamet-ilce">
                                    İkamet Edilen İlçe <span class="text-danger">*</span>
                                </label>
                                <select id="ue-ikamet-ilce" name="ikamet_ilcesi" class="form-select" required>
                                    <option value="">-- Önce İl Seçiniz --</option>
                                </select>
                            </div>
                            <!-- Trabzon İlçesi -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-trabzon-ilce">
                                    Trabzon İlçesi (Nüfusa Kayıtlı) <span class="text-danger">*</span>
                                </label>
                                <select id="ue-trabzon-ilce" name="trabzon_ilcesi" class="form-select" required>
                                    <option value="">-- İlçe Seçiniz --</option>
                                    <?php foreach ($trabzonIlceleri as $ilce): ?>
                                    <option value="<?= htmlspecialchars($ilce) ?>"><?= htmlspecialchars($ilce) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── MESLEKİ BİLGİLER ──────────────────────────────── -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="text-primary fw-bold border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-briefcase me-2"></i>Mesleki / Kurumsal Bilgiler
                        </h5>

                        <div class="row g-3">
                            <!-- Kurum -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-kurum">
                                    Çalıştığı Kurum <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="ue-kurum" name="kurum" class="form-control"
                                       placeholder="Örn: Maliye Bakanlığı, Valilik"
                                       maxlength="200" required autocomplete="organization">
                            </div>
                            <!-- Görev / Ünvan -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-gorev">
                                    Görev / Ünvan <span class="text-danger">*</span>
                                </label>
                                <input type="text" id="ue-gorev" name="gorev_unvan" class="form-control"
                                       placeholder="Örn: Uzman, Mühendis, Müdür"
                                       maxlength="120" required autocomplete="organization-title">
                            </div>
                            <!-- Çalışma Şekli -->
                            <div class="col-md-6">
                                <label class="form-label fw-semibold" for="ue-calisma">
                                    Çalışma Şekli <span class="text-danger">*</span>
                                </label>
                                <select id="ue-calisma" name="calisma_sekli" class="form-select" required>
                                    <option value="">-- Seçiniz --</option>
                                    <?php foreach ($calismaSekilleri as $sekil): ?>
                                    <option value="<?= htmlspecialchars($sekil) ?>"><?= htmlspecialchars($sekil) ?></option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── DERNEK YÖNETİM STATÜSÜ (admin-only) ───────────── -->
                <div class="card border-0 shadow-sm rounded-3 mb-4">
                    <div class="card-body p-4">
                        <h5 class="text-danger fw-bold border-bottom pb-2 mb-3">
                            <i class="fa-solid fa-user-shield me-2"></i>Dernek Yönetim Statüsü
                        </h5>

                        <div class="row g-3">
                            <div class="col-md-12">
                                <label class="form-label fw-semibold" for="ue-temsilci">
                                    Üyelik / Temsilcilik Türü
                                </label>
                                <select id="ue-temsilci" name="temsilci_turu" class="form-select border-danger fw-bold"
                                        onchange="statusRenkDegistir(this)">
                                    <option value="Normal Üye" style="background:#fff;color:#333;">Normal Üye (Temsilci Değil)</option>
                                    <option value="Yönetim Kurulu Üyesi" style="background:#CFE2FF;color:#084298;">Yönetim Kurulu Üyesi</option>
                                    <option value="İl Başkanı" style="background:#D1E7DD;color:#0f5132;">İl Başkanı (Türkiye Geneli)</option>
                                    <option value="İlçe Başkanı" style="background:#f3e5f5;color:#4a148c;">İlçe Başkanı</option>
                                    <option value="Kurum Temsilcisi" style="background:#FFF3CD;color:#664d03;">Kurum Temsilcisi</option>
                                </select>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ── KAYDET ────────────────────────────────────────── -->
                <div class="d-grid gap-2 mt-2 mb-5">
                    <button type="submit" class="btn btn-success btn-lg fw-bold shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Üyeyi Veritabanına Kaydet
                    </button>
                </div>

            </form>
        </div>
    </div>
</div>

<script>
// ── İl seçilince ilçe listesini doldur ─────────────────────────────────
const ilIlceVerileri = <?php
    $turkiyeIlceDosya = __DIR__ . '/turkiye-ilce-verileri.php';
    if (is_file($turkiyeIlceDosya)) {
        $ilceVerisi = include $turkiyeIlceDosya;
        echo json_encode($ilceVerisi, JSON_UNESCAPED_UNICODE);
    } else {
        echo '{}';
    }
?>;

function ikametIlceleriniYukle(il) {
    const ilceSecim = document.getElementById('ue-ikamet-ilce');
    ilceSecim.innerHTML = '<option value="">-- İlçe Seçiniz --</option>';

    if (!il || !ilIlceVerileri[il]) return;

    ilIlceVerileri[il].forEach(function(ilce) {
        const opt = document.createElement('option');
        opt.value = ilce;
        opt.textContent = ilce;
        ilceSecim.appendChild(opt);
    });
}

// ── Statü seçilince select rengini değiştir ────────────────────────────
function statusRenkDegistir(el) {
    const renkler = {
        'Yönetim Kurulu Üyesi': { bg: '#CFE2FF', color: '#084298' },
        'İl Başkanı':           { bg: '#D1E7DD', color: '#0f5132' },
        'İlçe Başkanı':         { bg: '#f3e5f5', color: '#4a148c' },
        'Kurum Temsilcisi':     { bg: '#FFF3CD', color: '#664d03' },
    };
    const stil = renkler[el.value] || { bg: '#fff', color: '#333' };
    el.style.backgroundColor = stil.bg;
    el.style.color = stil.color;
}

document.addEventListener('DOMContentLoaded', function () {
    statusRenkDegistir(document.getElementById('ue-temsilci'));

    // Telefon — sadece rakam
    const telInput = document.getElementById('ue-telefon');
    if (telInput) {
        telInput.addEventListener('input', function () {
            this.value = this.value.replace(/\D/g, '').slice(0, 9);
        });
    }
});
</script>