<?php
/**
 * Üst bar — kullanıcı bilgileri, tarih, sidebar toggle.
 *
 * @var bool   $is_gecis_aktif        Hesap geçişi aktif mi
 * @var string $gecis_gercek_kullanici Gerçek kullanıcı adı
 * @var string $kullanici_rolu        Aktif rol
 * @var array  $rol_etiketleri        (opsiyonel, navbar.php'den)
 */

// Hesap geçişi kontrolü
$is_gecis_aktif = isset($_SESSION['gercek_id']);
$gecis_gercek_kullanici = $is_gecis_aktif ? ($_SESSION['gercek_kullanici_adi'] ?? '') : '';

// Rol etiket ve renk haritası
$rol_harita = [
    'admin'                => ['Tam Yetkili',        'tb-role--admin'],
    'yonetim'              => ['Yönetim',            'tb-role--yonetim'],
    'gelistirici'          => ['Geliştirici',        'tb-role--gelistirici'],
    'il_baskani'           => ['İl Başkanı',         'tb-role--il'],
    'ilce_baskani'         => ['İlçe Başkanı',       'tb-role--ilce'],
    'kurum_temsilcisi'     => ['Kurum Temsilcisi',   'tb-role--kurum'],
    'kadin_kollari_baskani'=> ['Kadın Kolları',      'tb-role--kadin'],
];
$rol_bilgi = $rol_harita[$kullanici_rolu] ?? ['Bilinmeyen', ''];

// Sorumluluk bilgisi
$sorumluluk = '';
if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
    $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_il']);
} elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
    $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_ilce']);
} elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
    $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_kurum']);
}

// Tarih Türkçe
$gun_isimleri = ['Sunday'=>'Pazar','Monday'=>'Pazartesi','Tuesday'=>'Salı','Wednesday'=>'Çarşamba','Thursday'=>'Perşembe','Friday'=>'Cuma','Saturday'=>'Cumartesi'];
$ay_isimleri  = ['January'=>'Ocak','February'=>'Şubat','March'=>'Mart','April'=>'Nisan','May'=>'Mayıs','June'=>'Haziran','July'=>'Temmuz','August'=>'Ağustos','September'=>'Eylül','October'=>'Ekim','November'=>'Kasım','December'=>'Aralık'];

$tarih_ham  = date('d F Y');
$gun_ham    = date('l');
foreach ($ay_isimleri as $en => $tr) {
    $tarih_ham = str_replace($en, $tr, $tarih_ham);
}
$gun_tr = $gun_isimleri[$gun_ham] ?? $gun_ham;
$tarih_gosterim = $tarih_ham . ' ' . $gun_tr;

// Saat bilgisine göre selamlama
$saat = (int) date('H');
if ($saat < 6) {
    $selamlama = 'İyi geceler';
} elseif ($saat < 12) {
    $selamlama = 'Günaydın';
} elseif ($saat < 18) {
    $selamlama = 'İyi günler';
} else {
    $selamlama = 'İyi akşamlar';
}

// Avatar baş harfleri
$kullanici_adi = htmlspecialchars($_SESSION['kullanici_adi'] ?? '');
$bas_harfler = '';
$parcalar = explode(' ', $_SESSION['kullanici_adi'] ?? '');
foreach (array_slice($parcalar, 0, 2) as $p) {
    $bas_harfler .= mb_strtoupper(mb_substr($p, 0, 1));
}
?>

<?php if ($is_gecis_aktif): ?>
<div class="panel-impersonate-bar">
    <i class="fa-solid fa-eye" style="font-size: 16px;"></i>
    <span>
        <strong><?= $kullanici_adi ?></strong> hesabını görüntülüyorsunuz
        <span style="opacity:0.8;">(Gerçek hesap: <?= htmlspecialchars($gecis_gercek_kullanici) ?>)</span>
    </span>
    <a href="/yonetim/?islem=hesap_donus" class="btn btn-sm fw-bold px-3 py-1"
       style="background:#fff; color:#ff6d00; border:none; border-radius:20px; font-size:12px; text-decoration:none; box-shadow:0 1px 4px rgba(0,0,0,0.15);">
        <i class="fa-solid fa-arrow-rotate-left me-1"></i>Asıl Hesabıma Dön
    </a>
</div>
<?php endif; ?>

<header class="panel-topbar">
    <div class="tb-left">
        <button class="tb-toggle" id="sidebarToggle" type="button" aria-label="Menüyü aç/kapat">
            <i class="fa-solid fa-bars"></i>
        </button>
        <div class="tb-greeting">
            <span class="tb-greeting__hello"><?= $selamlama ?>, <?= $kullanici_adi ?> 👋</span>
            <span class="tb-greeting__sub">Derneğinizde her şey yolunda görünüyor.</span>
        </div>
    </div>

    <div class="tb-right">
        <span class="tb-date">
            <i class="fa-regular fa-calendar me-1"></i>
            <?= $tarih_gosterim ?>
        </span>

        <div class="tb-user">
            <div class="tb-user__avatar"><?= htmlspecialchars($bas_harfler) ?></div>
            <div class="tb-user__info">
                <span class="tb-user__name"><?= $kullanici_adi ?></span>
                <span class="tb-user__role <?= $rol_bilgi[1] ?>"><?= $rol_bilgi[0] . $sorumluluk ?></span>
            </div>
        </div>

        <?php if ($is_gecis_aktif): ?>
        <a href="/yonetim/?islem=hesap_donus" class="btn btn-warning btn-sm fw-bold px-3" title="Asıl hesaba dön">
            <i class="fa-solid fa-arrow-rotate-left"></i>
        </a>
        <?php else: ?>
        <a href="/yonetim/?islem=cikis" class="btn btn-outline-secondary btn-sm px-3" title="Çıkış yap">
            <i class="fa-solid fa-right-from-bracket"></i>
        </a>
        <?php endif; ?>

    </div>
</header>
