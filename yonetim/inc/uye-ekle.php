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

<style>
/* ── Yeni Üye Ekle — premium form tasarımı ─────────────────────────────── */
.ue-sayfa { max-width: 860px; margin: 0 auto; padding: 2rem 1rem 3rem; }

.ue-header {
    display: flex; align-items: center; gap: 1rem;
    margin-bottom: 2rem; flex-wrap: wrap;
}
.ue-header__ikon {
    width: 52px; height: 52px;
    background: linear-gradient(135deg, #0f3460, #16213e);
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    box-shadow: 0 6px 18px rgba(15,52,96,.35);
    flex-shrink: 0;
}
.ue-header__ikon i { color: #00c9a7; font-size: 1.3rem; }
.ue-header__baslik { font-size: 1.45rem; font-weight: 800; color: #1a1a2e; margin: 0; }
.ue-header__aciklama { color: #6c757d; font-size: 0.86rem; margin: 0; }

/* Bölüm kartları */
.ue-bolum {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #eef0f5;
    box-shadow: 0 2px 20px rgba(0,0,0,.06);
    margin-bottom: 1.25rem;
    overflow: hidden;
}
.ue-bolum__baslik {
    display: flex; align-items: center; gap: 0.7rem;
    padding: 1rem 1.4rem;
    border-bottom: 1px solid #f0f2f7;
    background: #fafbff;
}
.ue-bolum__baslik-ikon {
    width: 32px; height: 32px; border-radius: 9px;
    display: flex; align-items: center; justify-content: center;
    font-size: 0.85rem;
}
.ue-bolum__baslik-ikon--mavi  { background: rgba(0,123,255,.12); color: #007bff; }
.ue-bolum__baslik-ikon--yesil { background: rgba(0,201,167,.12); color: #00c9a7; }
.ue-bolum__baslik-ikon--kirmizi { background: rgba(220,53,69,.12); color: #dc3545; }
.ue-bolum__baslik h6 {
    margin: 0; font-weight: 700; font-size: 0.92rem; color: #1a1a2e;
}
.ue-bolum__govde { padding: 1.25rem 1.4rem; }
.ue-bolum__govde .row { gap-row: 1rem; }

/* Form alanları */
.ue-label {
    font-size: 0.78rem;
    font-weight: 700;
    color: #6c757d;
    text-transform: uppercase;
    letter-spacing: .04em;
    margin-bottom: 0.35rem;
    display: block;
}
.ue-input, .ue-select {
    width: 100%;
    padding: 0.6rem 0.9rem;
    border: 1.5px solid #e9ecef;
    border-radius: 10px;
    font-size: 0.88rem;
    color: #343a40;
    background: #fff;
    transition: border-color .18s, box-shadow .18s;
    outline: none;
    -webkit-appearance: none;
}
.ue-input:focus, .ue-select:focus {
    border-color: #0f3460;
    box-shadow: 0 0 0 3px rgba(15,52,96,.1);
}
.ue-input-group {
    display: flex; align-items: center;
    border: 1.5px solid #e9ecef; border-radius: 10px;
    overflow: hidden; background: #fff;
    transition: border-color .18s, box-shadow .18s;
}
.ue-input-group:focus-within {
    border-color: #0f3460;
    box-shadow: 0 0 0 3px rgba(15,52,96,.1);
}
.ue-input-group__prefix {
    padding: 0.6rem 0.85rem;
    background: #f8f9fa;
    font-weight: 700; font-size: 0.88rem;
    color: #495057;
    border-right: 1.5px solid #e9ecef;
    white-space: nowrap;
}
.ue-input-group .ue-input {
    border: none; border-radius: 0; box-shadow: none; flex: 1;
}
.ue-input-group .ue-input:focus { box-shadow: none; border-color: transparent; }
.ue-yardim { font-size: 0.72rem; color: #adb5bd; margin-top: 0.25rem; }

/* Kaydet butonu */
.ue-kaydet-btn {
    width: 100%;
    padding: 0.9rem;
    background: linear-gradient(135deg, #00b894, #00cec9);
    color: #fff;
    border: none;
    border-radius: 14px;
    font-size: 1rem;
    font-weight: 700;
    cursor: pointer;
    display: flex; align-items: center; justify-content: center; gap: 0.6rem;
    transition: filter .18s, transform .18s;
    box-shadow: 0 6px 20px rgba(0,184,148,.35);
    margin-top: 0.5rem;
}
.ue-kaydet-btn:hover { filter: brightness(1.07); transform: translateY(-1px); }

/* Select renkli statü */
.ue-select--statü-normal    { background: #fff;     color: #333; }
.ue-select--statü-yk        { background: #CFE2FF;  color: #084298; }
.ue-select--statü-il        { background: #D1E7DD;  color: #0f5132; }
.ue-select--statü-ilce      { background: #f3e5f5;  color: #4a148c; }
.ue-select--statü-kurum     { background: #FFF3CD;  color: #664d03; }

/* Bildirim */
.ue-bildirim {
    border-radius: 12px; padding: 0.9rem 1.1rem;
    display: flex; align-items: flex-start; gap: 0.7rem;
    font-size: 0.88rem; font-weight: 600; margin-bottom: 1.25rem;
}
.ue-bildirim--success { background: #d1e7dd; color: #0f5132; }
.ue-bildirim--warning { background: #fff3cd; color: #664d03; }
.ue-bildirim--danger  { background: #f8d7da; color: #842029; }
</style>

<div class="container-fluid py-4 px-md-4">
<div class="ue-sayfa">

    <!-- Başlık -->
    <div class="ue-header">
        <div class="ue-header__ikon">
            <i class="fa-solid fa-user-plus"></i>
        </div>
        <div>
            <h2 class="ue-header__baslik">Yeni Üye Kayıt Formu</h2>
            <p class="ue-header__aciklama">Derneğe yeni üye veya temsilci eklemek için aşağıdaki alanları doldurunuz.</p>
        </div>
    </div>

    <!-- Bildirim -->
    <?php if ($mesaj !== ''): ?>
    <div class="ue-bildirim ue-bildirim--<?= $mesajTuru ?>">
        <i class="fa-solid fa-<?= $mesajTuru === 'success' ? 'circle-check' : ($mesajTuru === 'danger' ? 'circle-exclamation' : 'triangle-exclamation') ?>" style="margin-top:2px;"></i>
        <span><?= htmlspecialchars($mesaj) ?></span>
    </div>
    <?php endif; ?>

    <form action="index.php?sayfa=uye-ekle" method="POST" novalidate>

        <!-- ── KİŞİSEL BİLGİLER ──────────────────────────────────────── -->
        <div class="ue-bolum">
            <div class="ue-bolum__baslik">
                <div class="ue-bolum__baslik-ikon ue-bolum__baslik-ikon--mavi">
                    <i class="fa-solid fa-id-card"></i>
                </div>
                <h6>Kişisel Bilgiler</h6>
            </div>
            <div class="ue-bolum__govde">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-ad">Adı <span style="color:#dc3545">*</span></label>
                        <input type="text" id="ue-ad" name="ad" class="ue-input"
                               placeholder="Adı" maxlength="60" required
                               autocomplete="given-name" spellcheck="false">
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-soyad">Soyadı <span style="color:#dc3545">*</span></label>
                        <input type="text" id="ue-soyad" name="soyad" class="ue-input"
                               placeholder="Soyadı" maxlength="60" required
                               autocomplete="family-name" spellcheck="false">
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-telefon">Telefon <span style="color:#dc3545">*</span></label>
                        <div class="ue-input-group">
                            <span class="ue-input-group__prefix">05</span>
                            <input type="tel" id="ue-telefon" name="telefon" class="ue-input"
                                   placeholder="XX XXX XX XX" maxlength="9"
                                   inputmode="numeric" pattern="[0-9]{9}" required>
                        </div>
                        <p class="ue-yardim">Başında 05 olmadan 9 rakam giriniz</p>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-eposta">E-Posta <span style="color:#dc3545">*</span></label>
                        <input type="email" id="ue-eposta" name="eposta" class="ue-input"
                               placeholder="ornek@kurum.gov.tr" maxlength="254" required
                               autocomplete="email" autocapitalize="off">
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-dogum">Doğum Tarihi <span style="color:#dc3545">*</span></label>
                        <input type="date" id="ue-dogum" name="dogum_tarihi" class="ue-input"
                               required autocomplete="bday"
                               max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                               min="1930-01-01">
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-kan">Kan Grubu</label>
                        <select id="ue-kan" name="kan_grubu" class="ue-select">
                            <option value="">— İsteğe Bağlı —</option>
                            <?php foreach ($kanGruplari as $kg): ?>
                            <option value="<?= htmlspecialchars($kg) ?>"><?= htmlspecialchars($kg) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-cinsiyet">Cinsiyet <span style="color:#dc3545">*</span></label>
                        <select id="ue-cinsiyet" name="cinsiyet" class="ue-select" required>
                            <option value="">— Seçiniz —</option>
                            <option value="Erkek">Erkek</option>
                            <option value="Kadın">Kadın</option>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── İKAMET BİLGİLERİ ───────────────────────────────────────── -->
        <div class="ue-bolum">
            <div class="ue-bolum__baslik">
                <div class="ue-bolum__baslik-ikon ue-bolum__baslik-ikon--yesil">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
                <h6>İkamet Bilgileri</h6>
            </div>
            <div class="ue-bolum__govde">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-ikamet-il">İkamet İli <span style="color:#dc3545">*</span></label>
                        <select id="ue-ikamet-il" name="ikamet_ili" class="ue-select" required
                                onchange="ikametIlceleriniYukle(this.value)">
                            <option value="">— İl Seçiniz —</option>
                            <?php foreach ($iller as $il): ?>
                            <option value="<?= htmlspecialchars($il) ?>"><?= htmlspecialchars($il) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-ikamet-ilce">İkamet İlçesi <span style="color:#dc3545">*</span></label>
                        <select id="ue-ikamet-ilce" name="ikamet_ilcesi" class="ue-select" required>
                            <option value="">— Önce İl Seçiniz —</option>
                        </select>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-trabzon-ilce">Trabzon İlçesi (Nüfusa Kayıtlı) <span style="color:#dc3545">*</span></label>
                        <select id="ue-trabzon-ilce" name="trabzon_ilcesi" class="ue-select" required>
                            <option value="">— İlçe Seçiniz —</option>
                            <?php foreach ($trabzonIlceleri as $ilce): ?>
                            <option value="<?= htmlspecialchars($ilce) ?>"><?= htmlspecialchars($ilce) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── MESLEKİ BİLGİLER ───────────────────────────────────────── -->
        <div class="ue-bolum">
            <div class="ue-bolum__baslik">
                <div class="ue-bolum__baslik-ikon ue-bolum__baslik-ikon--mavi">
                    <i class="fa-solid fa-briefcase"></i>
                </div>
                <h6>Mesleki / Kurumsal Bilgiler</h6>
            </div>
            <div class="ue-bolum__govde">
                <div class="row g-3">
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-kurum">Çalıştığı Kurum <span style="color:#dc3545">*</span></label>
                        <input type="text" id="ue-kurum" name="kurum" class="ue-input"
                               placeholder="Örn: Maliye Bakanlığı" maxlength="200" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-gorev">Görev / Ünvan <span style="color:#dc3545">*</span></label>
                        <input type="text" id="ue-gorev" name="gorev_unvan" class="ue-input"
                               placeholder="Örn: Uzman, Mühendis" maxlength="120" required>
                    </div>
                    <div class="col-md-6">
                        <label class="ue-label" for="ue-calisma">Çalışma Şekli <span style="color:#dc3545">*</span></label>
                        <select id="ue-calisma" name="calisma_sekli" class="ue-select" required>
                            <option value="">— Seçiniz —</option>
                            <?php foreach ($calismaSekilleri as $sekil): ?>
                            <option value="<?= htmlspecialchars($sekil) ?>"><?= htmlspecialchars($sekil) ?></option>
                            <?php endforeach; ?>
                        </select>
                    </div>
                </div>
            </div>
        </div>

        <!-- ── DERNEK YÖNETİM STATÜSÜ ─────────────────────────────────── -->
        <div class="ue-bolum">
            <div class="ue-bolum__baslik">
                <div class="ue-bolum__baslik-ikon ue-bolum__baslik-ikon--kirmizi">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
                <h6>Dernek Yönetim Statüsü</h6>
            </div>
            <div class="ue-bolum__govde">
                <label class="ue-label" for="ue-temsilci">Üyelik / Temsilcilik Türü</label>
                <select id="ue-temsilci" name="temsilci_turu" class="ue-select ue-select--statü-normal"
                        style="font-weight:700;border-color:#dc3545;"
                        onchange="statusRenkDegistir(this)">
                    <option value="Normal Üye">Normal Üye (Temsilci Değil)</option>
                    <option value="Yönetim Kurulu Üyesi">Yönetim Kurulu Üyesi</option>
                    <option value="İl Başkanı">İl Başkanı (Türkiye Geneli)</option>
                    <option value="İlçe Başkanı">İlçe Başkanı</option>
                    <option value="Kurum Temsilcisi">Kurum Temsilcisi</option>
                </select>
            </div>
        </div>

        <!-- ── KAYDET ──────────────────────────────────────────────────── -->
        <button type="submit" class="ue-kaydet-btn">
            <i class="fa-solid fa-floppy-disk"></i>
            Üyeyi Veritabanına Kaydet
        </button>

    </form>

</div>
</div>

<script>
const ilIlceVerileri = <?php
    $turkiyeIlceDosya = __DIR__ . '/turkiye-ilce-verileri.php';
    echo is_file($turkiyeIlceDosya)
        ? json_encode(include $turkiyeIlceDosya, JSON_UNESCAPED_UNICODE)
        : '{}';
?>;

function ikametIlceleriniYukle(il) {
    const sel = document.getElementById('ue-ikamet-ilce');
    sel.innerHTML = '<option value="">— İlçe Seçiniz —</option>';
    (ilIlceVerileri[il] || []).forEach(function(ilce) {
        const o = document.createElement('option');
        o.value = o.textContent = ilce;
        sel.appendChild(o);
    });
}

function statusRenkDegistir(el) {
    const r = {
        'Yönetim Kurulu Üyesi': { bg:'#CFE2FF', color:'#084298' },
        'İl Başkanı':           { bg:'#D1E7DD', color:'#0f5132' },
        'İlçe Başkanı':         { bg:'#f3e5f5', color:'#4a148c' },
        'Kurum Temsilcisi':     { bg:'#FFF3CD', color:'#664d03' },
    };
    const s = r[el.value] || { bg:'#fff', color:'#333' };
    el.style.backgroundColor = s.bg;
    el.style.color = s.color;
}

document.addEventListener('DOMContentLoaded', function () {
    statusRenkDegistir(document.getElementById('ue-temsilci'));
    const tel = document.getElementById('ue-telefon');
    if (tel) tel.addEventListener('input', function () {
        this.value = this.value.replace(/\D/g,'').slice(0,9);
    });
});
</script>
