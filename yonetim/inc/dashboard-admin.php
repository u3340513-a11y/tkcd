<?php
/**
 * Admin / Yönetim Dashboard — Yeni Tasarım.
 *
 * Bu dosya index.php'den include edilir. Tüm değişkenler index.php'de
 * hazırlanıp bu dosyaya aktarılır.
 *
 * Kullanılan değişkenler:
 * @var int    $toplam_uye
 * @var int    $toplam_il
 * @var int    $yonetim_kurulu
 * @var int    $bolge_koordinatorleri
 * @var int    $il_baskanlari
 * @var int    $ilce_baskanlari
 * @var int    $kurum_temsilcileri
 * @var int    $teskilatlanma_sorumlusu
 * @var int    $kadin_kollari_baskanlari
 * @var int    $bekleyen_uye_sayisi
 * @var int    $admin_son30_sayisi
 * @var array  $admin_son_uyeler
 * @var array  $admin_kan_etiketler
 * @var array  $admin_kan_sayilar
 * @var array  $admin_cs_verileri
 * @var array  $admin_kurum_verileri
 * @var int    $admin_toplam_kurum
 * @var int    $admin_kurumlu_uye
 * @var array  $ilce_verileri
 * @var array  $grafik_ekseni
 * @var array  $grafik_sayilari
 * @var array  $il_verileri
 * @var array  $il_grafik_ekseni
 * @var array  $il_grafik_sayilari
 * @var array  $dogum_gunu_uyeleri
 * @var array  $son_giris_verileri
 * @var bool   $is_yonetim
 * @var bool   $is_gelistirici
 * @var array  $duyurular
 * @var array  $son_faaliyetler
 */



// Faaliyetler (log) — Son 10 kayıt
$son_faaliyetler = [];
try {
    $faaliyet_sorgu = $db_baglanti->query(
        "SELECT kullanici_adi, islem_turu, aciklama, tarih
           FROM yonetim_log
          ORDER BY tarih DESC LIMIT 10"
    );
    if ($faaliyet_sorgu) {
        $son_faaliyetler = $faaliyet_sorgu->fetchAll(PDO::FETCH_ASSOC);
    }
} catch (\PDOException $e) {
    $son_faaliyetler = [];
} catch (\Throwable $e) {
    $son_faaliyetler = [];
}

// Duyurular
$duyurular = [];
try {
    // Tablo var mı kontrol et (idempotent)
    $tablo_kontrol = $db_baglanti->query(
        "SELECT COUNT(*) FROM INFORMATION_SCHEMA.TABLES WHERE TABLE_SCHEMA = DATABASE() AND TABLE_NAME = 'duyurular'"
    );
    if ($tablo_kontrol && (int) $tablo_kontrol->fetchColumn() > 0) {
        $duyuru_sorgu = $db_baglanti->query(
            "SELECT baslik, icerik, tarih FROM duyurular WHERE aktif = 1 ORDER BY tarih DESC LIMIT 5"
        );
        if ($duyuru_sorgu) {
            $duyurular = $duyuru_sorgu->fetchAll(PDO::FETCH_ASSOC);
        }
    }
} catch (\PDOException $e) {
    $duyurular = [];
} catch (\Throwable $e) {
    $duyurular = [];
}

// Avatar renk paleti (hash'e göre)
$avatar_renkleri = ['#3b82f6','#ef4444','#f59e0b','#10b981','#8b5cf6','#ec4899','#06b6d4','#f97316','#14b8a6','#6366f1'];
if (!function_exists('avatarRengi')) {
    function avatarRengi(string $isim, array $renkler): string {
        return $renkler[abs(crc32($isim)) % count($renkler)];
    }
}
if (!function_exists('basHarfleri')) {
    function basHarfleri(string $isim): string {
        $parcalar = explode(' ', trim($isim));
        $harfler = '';
        foreach (array_slice($parcalar, 0, 2) as $p) {
            $harfler .= mb_strtoupper(mb_substr(trim($p), 0, 1));
        }
        return $harfler;
    }
}

// Üye artış trendi — Son 12 ay
$aylik_artis = [];
try {
    $artis_sorgu = $db_baglanti->query(
        "SELECT DATE_FORMAT(uyelik_tarihi, '%Y-%m') as ay, COUNT(*) as adet
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND uyelik_tarihi IS NOT NULL
            AND uyelik_tarihi >= DATE_SUB(CURDATE(), INTERVAL 12 MONTH)
          GROUP BY DATE_FORMAT(uyelik_tarihi, '%Y-%m')
          ORDER BY ay ASC"
    );
    $aylik_artis = $artis_sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $aylik_artis = [];
}

$ay_kisa = ['01'=>'Oca','02'=>'Şub','03'=>'Mar','04'=>'Nis','05'=>'May','06'=>'Haz','07'=>'Tem','08'=>'Ağu','09'=>'Eyl','10'=>'Eki','11'=>'Kas','12'=>'Ara'];
$artis_etiketler = [];
$artis_sayilar   = [];
foreach ($aylik_artis as $a) {
    $ay_num = substr($a['ay'], 5, 2);
    $artis_etiketler[] = ($ay_kisa[$ay_num] ?? $ay_num) . ' ' . substr($a['ay'], 2, 2);
    $artis_sayilar[]   = (int) $a['adet'];
}

// Türkiye'nin 7 coğrafi bölgesine göre ikamet_ili dağılımı
$bolge_harita = [
    'Marmara Bölgesi' => [
        'İstanbul','Tekirdağ','Edirne','Kırklareli','Çanakkale',
        'Balıkesir','Bursa','Yalova','Kocaeli','Sakarya',
        'Düzce','Bolu','Bilecik',
    ],
    'Ege Bölgesi' => [
        'İzmir','Manisa','Aydın','Denizli','Muğla',
        'Uşak','Afyonkarahisar','Kütahya',
    ],
    'Akdeniz Bölgesi' => [
        'Antalya','Isparta','Burdur','Mersin','Adana',
        'Hatay','Osmaniye','Kahramanmaraş','Karaman',
    ],
    'İç Anadolu Bölgesi' => [
        'Ankara','Konya','Eskişehir','Kırıkkale','Kırşehir',
        'Nevşehir','Aksaray','Niğde','Kayseri','Sivas',
        'Yozgat','Çankırı',
    ],
    'Karadeniz Bölgesi' => [
        'Trabzon','Rize','Artvin','Giresun','Ordu','Samsun',
        'Sinop','Kastamonu','Bartın','Zonguldak','Karabük',
        'Amasya','Tokat','Gümüşhane','Bayburt',
    ],
    'Doğu Anadolu Bölgesi' => [
        'Erzurum','Erzincan','Ağrı','Kars','Ardahan','Iğdır',
        'Muş','Bitlis','Van','Hakkari','Elazığ','Malatya',
        'Bingöl','Tunceli',
    ],
    'Güneydoğu Anadolu Bölgesi' => [
        'Gaziantep','Şanlıurfa','Diyarbakır','Mardin',
        'Batman','Şırnak','Siirt','Adıyaman','Kilis',
    ],
];

// il_verileri (ikamet_ili bazlı) üzerinden bölge toplamlarını hesapla
$bolge_sayilari = array_fill_keys(array_keys($bolge_harita), 0);
$bolge_sayilari['Diğer'] = 0;

foreach ($il_verileri as $iv) {
    $il = $iv['ikamet_ili'] ?? '';
    $bulunan = false;
    foreach ($bolge_harita as $bolge => $iller) {
        if (in_array($il, $iller, true)) {
            $bolge_sayilari[$bolge] += (int) $iv['adet'];
            $bulunan = true;
            break;
        }
    }
    if (!$bulunan && $il !== '') {
        $bolge_sayilari['Diğer'] += (int) $iv['adet'];
    }
}

// Sıfır olan bölgeleri filtrele
$bolge_sayilari = array_filter($bolge_sayilari, fn($v) => $v > 0);
$bolge_etiketler = array_keys($bolge_sayilari);
$bolge_degerler  = array_values($bolge_sayilari);
?>

<!-- ═══════════════════════════════════════════════════════════════
     K: MOTİVASYON BANNER
     ═══════════════════════════════════════════════════════════════ -->
<div class="dash-banner mb-4">
    <div class="dash-banner__text">
        <h3>Trabzon'un gücü, gönül veren insanlarında…</h3>
        <p>Bugün derneğimizde <strong><?= number_format($toplam_uye) ?></strong> kayıtlı üye,
           <strong><?= $toplam_il ?></strong> aktif ilde teşkilatlanma ile güçlenmeye devam ediyoruz.</p>
    </div>
    <div class="dash-banner__quote">
        “Memleket sevdası,<br>insana en güzel hizmeti yaptırır.”
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     A: İSTATİSTİK KARTLARI (Tasarım 1 — 8 kart, 4'lü satırlar)
     ═══════════════════════════════════════════════════════════════ -->
<div class="row row-cols-1 row-cols-sm-2 row-cols-xl-4 g-3 mb-4">

    <!-- Toplam Üye -->
    <div class="col">
        <a href="index.php?sayfa=uyeler" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #3b82f6;">
                <div class="dash-stat-card__info">
                    <h6>Toplam Üye</h6>
                    <h3><?= number_format($toplam_uye) ?></h3>
                    <?php if ($admin_son30_sayisi > 0): ?>
                    <span class="dash-stat-sub"><i class="fa-solid fa-arrow-up text-success me-1"></i>+<?= $admin_son30_sayisi ?> son 30 gün</span>
                    <?php endif; ?>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                    <i class="fa-solid fa-users"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Aktif İller -->
    <div class="col">
        <a href="index.php?sayfa=uyeler&filtre=aktif_iller" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #ef4444;">
                <div class="dash-stat-card__info">
                    <h6>Aktif İller</h6>
                    <h3><?= $toplam_il ?></h3>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                    <i class="fa-solid fa-map-location-dot"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Yönetim Kurulu -->
    <div class="col">
        <a href="index.php?sayfa=uyeler&filtre=yonetim_kurulu" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #6366f1;">
                <div class="dash-stat-card__info">
                    <h6>Yönetim Kurulu</h6>
                    <h3><?= $yonetim_kurulu ?></h3>
                    <span class="dash-stat-sub">Aktif görevde</span>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(99,102,241,0.1); color: #6366f1;">
                    <i class="fa-solid fa-user-shield"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Bekleyen Başvuru -->
    <div class="col">
        <a href="index.php?sayfa=bekleyen-uyeler" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #ef4444;">
                <div class="dash-stat-card__info">
                    <h6>Bekleyen Başvuru</h6>
                    <h3><?= $bekleyen_uye_sayisi ?></h3>
                    <span class="dash-stat-sub"><?= $bekleyen_uye_sayisi > 0 ? 'İncelenmesi gerekiyor' : 'Bekleyen yok' ?></span>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                    <i class="fa-solid fa-user-clock"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- İl Başkanları -->
    <div class="col">
        <a href="index.php?sayfa=uyeler&filtre=il_baskani" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #10b981;">
                <div class="dash-stat-card__info">
                    <h6>İl Başkanları</h6>
                    <h3><?= $il_baskanlari ?></h3>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                    <i class="fa-solid fa-building-flag"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- İlçe Başkanları -->
    <div class="col">
        <a href="index.php?sayfa=uyeler&filtre=ilce_baskani" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #8b5cf6;">
                <div class="dash-stat-card__info">
                    <h6>İlçe Başkanları</h6>
                    <h3><?= $ilce_baskanlari ?></h3>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                    <i class="fa-solid fa-route"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Kurum Temsilcileri -->
    <div class="col">
        <a href="index.php?sayfa=uyeler&filtre=kurum_temsilcisi" class="text-decoration-none d-block h-100">
            <div class="dash-stat-card" style="border-left-color: #f59e0b;">
                <div class="dash-stat-card__info">
                    <h6>Kurum Temsilcileri</h6>
                    <h3><?= $kurum_temsilcileri ?></h3>
                </div>
                <div class="dash-stat-card__icon" style="background: rgba(245,158,11,0.1); color: #f59e0b;">
                    <i class="fa-solid fa-building-user"></i>
                </div>
            </div>
        </a>
    </div>

    <!-- Tamamlanan Başvuru (Son 30 gün) -->
    <div class="col">
        <div class="dash-stat-card" style="border-left-color: #14b8a6;">
            <div class="dash-stat-card__info">
                <h6>Son 30 Günde Eklenen</h6>
                <h3><?= $admin_son30_sayisi ?></h3>
                <span class="dash-stat-sub">Onaylanan üyeler</span>
            </div>
            <div class="dash-stat-card__icon" style="background: rgba(20,184,166,0.1); color: #14b8a6;">
                <i class="fa-solid fa-user-check"></i>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     B: ÜYE ARTIŞ TRENDİ (Tasarım 2 — çizgi grafik)  +
     C: BÖLGE DAĞILIMI (donut)
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <!-- Sol: Aylık Artış Trendi -->
    <div class="col-lg-5">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-chart-line text-primary"></i> Aylık Üye Artış Trendi</h5>
                <span class="dash-card__action">Son 12 Ay</span>
            </div>
            <div class="dash-card__body">
                <div style="position:relative; height:240px; max-height:240px;">
                    <canvas id="uyeArtisTrendi"></canvas>
                </div>
            </div>
        </div>
    </div>

    <!-- Orta: Bölge Dağılımı -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-pie-chart text-success"></i> Bölge Dağılımı</h5>
            </div>
            <div class="dash-card__body text-center">
                <div style="position:relative; height:200px; max-height:200px;">
                    <canvas id="bolgeDagilimi"></canvas>
                </div>
                <?php
                $bolge_renk_harita = [
                    'Marmara Bölgesi'           => '#3b82f6',
                    'Ege Bölgesi'               => '#06b6d4',
                    'Akdeniz Bölgesi'           => '#f97316',
                    'İç Anadolu Bölgesi'        => '#f59e0b',
                    'Karadeniz Bölgesi'         => '#10b981',
                    'Doğu Anadolu Bölgesi'      => '#8b5cf6',
                    'Güneydoğu Anadolu Bölgesi' => '#ef4444',
                    'Diğer'                     => '#6b7280',
                ];
                ?>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2">
                    <?php foreach ($bolge_sayilari as $bolge => $sayi):
                        $renk = $bolge_renk_harita[$bolge] ?? '#6b7280';
                    ?>
                    <span class="d-flex align-items-center gap-1 px-2 py-1 rounded border bg-light" style="font-size:0.72rem;">
                        <span style="width:8px;height:8px;border-radius:50%;background:<?= $renk ?>;flex-shrink:0;display:inline-block;"></span>
                        <?= htmlspecialchars($bolge) ?>: <strong><?= $sayi ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>

                <?php if ($is_gelistirici): ?>
                <!-- PDF İndirme Bölümü — Sadece Geliştirici -->
                <div class="mt-3 pt-3 border-top">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <span style="font-size:0.75rem; font-weight:700; color:#555; text-transform:uppercase; letter-spacing:0.5px;">
                            <i class="fa-solid fa-file-pdf text-danger me-1"></i> PDF Raporu
                        </span>
                        <div class="d-flex gap-1">
                            <button type="button"
                                class="btn btn-sm py-0 px-2 rounded-pill"
                                style="font-size:0.7rem; background:#e8f5e9; color:#1b5e20; border:1px solid #a5d6a7;"
                                onclick="bolgePdfAc('Tümü', 0)">
                                <i class="fa-solid fa-globe me-1"></i>Tümü
                            </button>
                        </div>
                    </div>
                    <div class="d-flex flex-column gap-1" style="max-height:180px; overflow-y:auto;">
                        <?php foreach (array_keys($bolge_sayilari) as $bolge):
                            $renk = $bolge_renk_harita[$bolge] ?? '#6b7280';
                            $renk_bg = $renk . '15';
                        ?>
                        <div class="d-flex align-items-center justify-content-between px-2 py-1 rounded"
                             style="background:<?= $renk_bg ?>; border:1px solid <?= $renk ?>30;">
                            <span style="font-size:0.72rem; font-weight:600; color:#333;">
                                <span style="width:7px;height:7px;border-radius:50%;background:<?= $renk ?>;display:inline-block;margin-right:4px;"></span>
                                <?= htmlspecialchars($bolge) ?>
                            </span>
                            <div class="d-flex gap-1">
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    style="font-size:0.65rem; background:#dc3545; color:#fff; border:none; border-radius:20px;"
                                    title="İletişim bilgileri açık PDF"
                                    onclick="bolgePdfAc('<?= addslashes($bolge) ?>', 0, 0)">
                                    <i class="fa-solid fa-eye me-1"></i>Açık
                                </button>
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    style="font-size:0.65rem; background:#6c757d; color:#fff; border:none; border-radius:20px;"
                                    title="İletişim bilgileri gizli PDF"
                                    onclick="bolgePdfAc('<?= addslashes($bolge) ?>', 1, 0)">
                                    <i class="fa-solid fa-eye-slash me-1"></i>Gizli
                                </button>
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    style="font-size:0.65rem; background:#6a1b9a; color:#fff; border:none; border-radius:20px;"
                                    title="Sadece kurum temsilcileri — iletişim açık"
                                    onclick="bolgePdfAc('<?= addslashes($bolge) ?>', 0, 1)">
                                    <i class="fa-solid fa-building-user me-1"></i>KT Açık
                                </button>
                                <button type="button"
                                    class="btn btn-sm py-0 px-2"
                                    style="font-size:0.65rem; background:#4a148c; color:#fff; border:none; border-radius:20px;"
                                    title="Sadece kurum temsilcileri — iletişim gizli"
                                    onclick="bolgePdfAc('<?= addslashes($bolge) ?>', 1, 1)">
                                    <i class="fa-solid fa-building-lock me-1"></i>KT Gizli
                                </button>
                            </div>
                        </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <script>
                function bolgePdfAc(bolge, gizli, sadeceKt) {
                    var url = 'inc/bolge-pdf.php?bolge=' + encodeURIComponent(bolge)
                            + '&gizli=' + (gizli || 0)
                            + '&sadece_kt=' + (sadeceKt || 0);
                    window.open(url, '_blank');
                }
                </script>
                <?php endif; ?>

            </div>
        </div>
    </div>

    <!-- Sağ: Doğum Günleri -->
    <div class="col-lg-3">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title">
                    <i class="fa-solid fa-cake-candles text-warning"></i> Doğum Günleri
                </h5>
                <span class="dash-card__action"><?= date('d.m.Y') ?></span>
            </div>
            <div class="dash-card__body">
                <?php if (count($dogum_gunu_uyeleri) > 0): ?>
                <div class="d-flex flex-column gap-2">
                    <?php foreach ($dogum_gunu_uyeleri as $dg_uye): ?>
                    <?php
                        $dg_renk = avatarRengi($dg_uye['adi_soyadi'], $avatar_renkleri);
                        $dg_harf = basHarfleri($dg_uye['adi_soyadi']);
                    ?>
                    <?php
                        $dg_link_yetkili = $is_gelistirici
                            || (($_SESSION['kullanici_adi'] ?? '') === 'admin61');
                    ?>
                    <?php if ($dg_link_yetkili): ?>
                    <a href="index.php?sayfa=uye-detay&id=<?= (int)$dg_uye['id'] ?>"
                       class="d-flex align-items-center gap-2 p-2 rounded-3 text-decoration-none dg-kart"
                       style="background:<?= $dg_renk ?>10;border:1px solid <?= $dg_renk ?>28;transition:background 0.18s,box-shadow 0.18s,transform 0.15s;display:block;"
                       title="<?= htmlspecialchars($dg_uye['adi_soyadi']) ?> — Üye Kartına Git">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:36px;height:36px;background:<?= $dg_renk ?>;color:#fff;font-size:0.8rem;">
                            <?= htmlspecialchars($dg_harf) ?>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-truncate" style="font-size:0.82rem;color:#1a1a2e;"><?= htmlspecialchars($dg_uye['adi_soyadi']) ?></div>
                        </div>
                        <span style="font-size:1.1rem;">🎂</span>
                    </a>
                    <?php else: ?>
                    <div class="d-flex align-items-center gap-2 p-2 rounded-3"
                         style="background:<?= $dg_renk ?>10;border:1px solid <?= $dg_renk ?>28;">
                        <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:36px;height:36px;background:<?= $dg_renk ?>;color:#fff;font-size:0.8rem;">
                            <?= htmlspecialchars($dg_harf) ?>
                        </div>
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-semibold text-truncate" style="font-size:0.82rem;"><?= htmlspecialchars($dg_uye['adi_soyadi']) ?></div>
                        </div>
                        <span style="font-size:1.1rem;">🎂</span>
                    </div>
                    <?php endif; ?>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <div class="text-center py-4 text-muted">
                    <i class="fa-regular fa-calendar-xmark fa-2x mb-2 d-block"></i>
                    <span style="font-size:0.82rem;">Bugün doğum günü<br>olan üye yok.</span>
                </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     HAFTALIK QUIZ ŞAMPİYONLARI
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dash-card">
            <div class="dash-card__header">
                <h5 class="dash-card__title">
                    <i class="fa-solid fa-trophy text-warning"></i> Haftanın TS Bilgi Yarışması Şampiyonlar Ligi
                </h5>
                <a href="index.php?sayfa=quiz" class="dash-card__action" style="text-decoration:none; color:#e94560;">
                    <i class="fa-solid fa-futbol me-1"></i> Yarışmaya Katıl
                </a>
            </div>
            <div class="dash-card__body">
                <?php if (empty($quiz_liderleri)): ?>
                    <div class="text-center py-4">
                        <div style="font-size:2.5rem; margin-bottom:0.5rem;">⚽</div>
                        <p class="text-muted mb-1">Henüz bu hafta kimse yarışmaya katılmadı.</p>
                        <a href="index.php?sayfa=quiz" class="btn btn-sm px-3 py-1 fw-bold rounded-pill" style="background:linear-gradient(135deg,#e94560,#c72c41); color:#fff; border:none;">
                            İlk Sen Katıl!
                        </a>
                    </div>
                <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover mb-0" style="font-size:0.9rem;">
                            <thead>
                                <tr style="border-bottom:2px solid #e94560;">
                                    <th style="width:50px;">#</th>
                                    <th>Kullanıcı</th>
                                    <th class="text-center">Toplam Puan</th>
                                    <th class="text-center">Oynama</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($quiz_liderleri as $qi => $ql): ?>
                                <tr>
                                    <td>
                                        <?php if ($qi === 0): ?>
                                            <span style="font-size:1.3rem;">🥇</span>
                                        <?php elseif ($qi === 1): ?>
                                            <span style="font-size:1.3rem;">🥈</span>
                                        <?php elseif ($qi === 2): ?>
                                            <span style="font-size:1.3rem;">🥉</span>
                                        <?php else: ?>
                                            <span class="fw-bold text-muted"><?= $qi + 1 ?></span>
                                        <?php endif; ?>
                                    </td>
                                    <td class="fw-bold"><?= htmlspecialchars($ql['kullanici_adi'], ENT_QUOTES, 'UTF-8') ?></td>
                                    <td class="text-center">
                                        <span class="badge rounded-pill px-3 py-1" style="background:linear-gradient(135deg,#e94560,#c72c41); font-size:0.85rem;">
                                            <?= (int)$ql['en_yuksek_puan'] ?> puan
                                        </span>
                                    </td>
                                    <td class="text-center text-muted"><?= (int)$ql['oynama_sayisi'] ?> kez</td>
                                </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     C: TÜRKİYE HARİTASI — İl Bazlı Üye Dağılımı
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">
    <div class="col-12">
        <div class="dash-card">
            <div class="dash-card__header">
                <h5 class="dash-card__title">
                    <i class="fa-solid fa-location-dot text-danger"></i> Üyelerin İllere Göre Dağılımı
                </h5>
                <span class="dash-card__action">Canlı Veri</span>
            </div>
            <div class="dash-card__body">
                <div class="row g-4 align-items-center">
                    <!-- Sol: Türkiye Haritası -->
                    <div class="col-lg-8">
                        <div id="turkiyeHaritasi" style="height: 520px; width: 100%;"></div>
                    </div>
                    <!-- Sağ: Top İller Listesi -->
                    <div class="col-lg-4">
                        <div class="p-3 rounded-3" style="background: #f8fafc;">
                            <h6 class="fw-bold mb-3" style="font-size:0.85rem; color:#1e293b;">
                                <i class="fa-solid fa-trophy text-warning me-1"></i> En Çok Üye Olan İller
                            </h6>
                            <div class="d-flex flex-column gap-2">
                            <?php foreach (array_slice($il_verileri, 0, 5) as $si => $il):
                                $il_max = (int)($il_verileri[0]['adet'] ?? 1);
                                $il_adet = (int)$il['adet'];
                                $il_yuzde = $il_max > 0 ? round(($il_adet / $il_max) * 100) : 0;
                                $rank_renkler = ['#c0392b','#e74c3c','#e67e22','#f39c12','#2980b9'];
                                $rank_renk = $rank_renkler[$si] ?? '#6b7280';
                            ?>
                            <div class="d-flex align-items-center gap-3 py-2 px-2 rounded-2 hover-bg"
                                 style="transition:background 0.15s;">
                                <span class="fw-bold d-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:28px;height:28px;background:<?= $rank_renk ?>;color:#fff;font-size:0.75rem;">
                                    <?= $si + 1 ?>
                                </span>
                                <div class="flex-grow-1">
                                    <div class="d-flex justify-content-between align-items-center mb-1">
                                        <span class="fw-semibold" style="font-size:0.88rem;"><?= htmlspecialchars($il['ikamet_ili']) ?></span>
                                        <span class="fw-bold" style="color:<?= $rank_renk ?>;font-size:0.88rem;"><?= $il_adet ?></span>
                                    </div>
                                    <div style="height:4px;background:#e2e8f0;border-radius:2px;overflow:hidden;">
                                        <div style="height:100%;width:<?= $il_yuzde ?>%;background:<?= $rank_renk ?>;border-radius:2px;transition:width 0.6s ease;"></div>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                            </div>
                            <?php if (count($il_verileri) > 5): ?>
                            <a href="index.php?sayfa=uyeler" class="d-block text-center mt-4 py-2 px-4 rounded-2 fw-semibold text-decoration-none"
                               style="background:#eef2ff;color:#3b82f6;font-size:0.85rem;">
                                Tüm İlleri Gör <i class="fa-solid fa-arrow-right ms-1"></i>
                            </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>


<!-- ═══════════════════════════════════════════════════════════════
     E: KAN GRUBU  +  F: KURUMLAR
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Kan Grubu Dağılımı -->
    <div class="col-lg-6">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-droplet text-danger"></i> Kan Grubu Dağılımı</h5>
            </div>
            <div class="dash-card__body text-center">
                <?php if (!empty($admin_kan_etiketler)): ?>
                <div style="position:relative; height:200px; max-height:200px;">
                    <canvas id="kanGrubuGrafik"></canvas>
                </div>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2">
                    <?php
                    $kan_toplam = array_sum($admin_kan_sayilar);
                    foreach ($admin_kan_etiketler as $ki => $etiket):
                        $yuzde = $kan_toplam > 0 ? round(($admin_kan_sayilar[$ki] / $kan_toplam) * 100) : 0;
                    ?>
                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.7rem;">
                        <?= htmlspecialchars($etiket) ?>: %<?= $yuzde ?>
                    </span>
                    <?php endforeach; ?>
                </div>
                <?php else: ?>
                <p class="text-muted small py-4">Kan grubu verisi bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- En Çok Üye Olan Kurumlar -->
    <div class="col-lg-6">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-building text-info"></i> En Çok Üye Olan Kurumlar</h5>
                <span class="badge bg-info bg-opacity-10 text-info"><?= $admin_toplam_kurum ?> kurum</span>
            </div>
            <div class="dash-card__body" style="max-height:360px; overflow-y:auto;">
                <table class="dash-kurum-table">
                    <thead><tr><th>#</th><th>Kurum</th><th>Üye</th></tr></thead>
                    <tbody>
                    <?php foreach ($admin_kurum_verileri as $ki => $kurum): ?>
                    <tr>
                        <td class="kurum-rank"><?= $ki + 1 ?></td>
                        <td><?= htmlspecialchars($kurum['kurum']) ?></td>
                        <td><strong><?= $kurum['adet'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     F: SON EKLENEN ÜYELER  +  G: YAKLAŞAN ETKİNLİKLER  +  J: HIZLI İŞLEMLER
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Son Eklenen Üyeler (Tasarım 1) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-user-plus text-primary"></i> Son Eklenen Üyeler</h5>
                <a href="index.php?sayfa=son-onaylananlar" class="dash-card__action">Tümünü Gör →</a>
            </div>
            <div class="dash-card__body">
                <?php if (!empty($admin_son_uyeler)): ?>
                <?php foreach (array_slice($admin_son_uyeler, 0, 6) as $su): ?>
                <a href="index.php?sayfa=uye-detay&id=<?= $su['id'] ?>" class="text-decoration-none">
                    <div class="dash-member-item">
                        <div class="dash-member-item__avatar" style="background: <?= avatarRengi($su['adi_soyadi'], $avatar_renkleri) ?>">
                            <?= basHarfleri($su['adi_soyadi']) ?>
                        </div>
                        <div class="dash-member-item__info">
                            <div class="dash-member-item__name"><?= htmlspecialchars($su['adi_soyadi']) ?></div>
                            <div class="dash-member-item__meta"><?= htmlspecialchars($su['kurum'] ?? '—') ?></div>
                        </div>
                        <span class="dash-member-item__date"><?= $su['uyelik_tarihi'] ? date('d.m.Y', strtotime($su['uyelik_tarihi'])) : '—' ?></span>
                    </div>
                </a>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small text-center py-3">Henüz üye kaydı bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yaklaşan Etkinlikler (Tasarım 2 — placeholder) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-calendar-days text-warning"></i> Yaklaşan Etkinlikler</h5>
            </div>
            <div class="dash-card__body text-center py-5">
                <i class="fa-solid fa-calendar-plus fa-3x text-muted mb-3 d-block" style="opacity:0.2;"></i>
                <p class="text-muted small mb-1">Etkinlik modülü yakında aktif olacak.</p>
                <span class="badge bg-warning bg-opacity-10 text-warning">Geliştirme Aşamasında</span>
            </div>
        </div>
    </div>

    <!-- Hızlı İşlemler (Tasarım 1) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-bolt text-warning"></i> Hızlı İşlemler</h5>
            </div>
            <div class="dash-card__body">
                <div class="row row-cols-2 g-3">
                    <?php if (!$is_kisitli_rol): ?>
                    <div class="col">
                        <a href="index.php?sayfa=uye-ekle" class="dash-quick-btn">
                            <div class="dash-quick-btn__icon" style="background: rgba(59,130,246,0.1); color: #3b82f6;">
                                <i class="fa-solid fa-user-plus"></i>
                            </div>
                            Yeni Üye Ekle
                        </a>
                    </div>
                    <div class="col">
                        <a href="index.php?sayfa=bekleyen-uyeler" class="dash-quick-btn">
                            <div class="dash-quick-btn__icon" style="background: rgba(239,68,68,0.1); color: #ef4444;">
                                <i class="fa-solid fa-user-clock"></i>
                            </div>
                            Başvurular
                        </a>
                    </div>
                    <?php endif; ?>
                    <div class="col">
                        <a href="index.php?sayfa=uyeler" class="dash-quick-btn">
                            <div class="dash-quick-btn__icon" style="background: rgba(16,185,129,0.1); color: #10b981;">
                                <i class="fa-solid fa-users"></i>
                            </div>
                            Üye Listesi
                        </a>
                    </div>
                    <div class="col">
                        <a href="index.php?sayfa=son-onaylananlar" class="dash-quick-btn">
                            <div class="dash-quick-btn__icon" style="background: rgba(139,92,246,0.1); color: #8b5cf6;">
                                <i class="fa-solid fa-user-check"></i>
                            </div>
                            Son Onaylananlar
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     H: DUYURULAR  +  I: SON FAALİYETLER  +  L: DİKKAT UYARILARI
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- Duyurular (Tasarım 1 — geliştirici CRUD) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-bullhorn text-info"></i> Duyurular</h5>
                <?php if ($is_gelistirici): ?>
                <a href="index.php?sayfa=duyurular" class="dash-card__action">Yönet →</a>
                <?php endif; ?>
            </div>
            <div class="dash-card__body">
                <?php if (!empty($duyurular)): ?>
                <?php foreach ($duyurular as $duyuru): ?>
                <div class="border-bottom pb-2 mb-2">
                    <div class="fw-semibold small"><?= htmlspecialchars($duyuru['baslik']) ?></div>
                    <div class="text-muted" style="font-size:0.75rem;"><?= date('d.m.Y', strtotime($duyuru['tarih'])) ?></div>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small text-center py-3">Henüz duyuru bulunmuyor.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Son Faaliyetler (Log) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-clock-rotate-left text-secondary"></i> Son Faaliyetler</h5>
                <?php if ($is_gelistirici): ?>
                <a href="index.php?sayfa=loglar" class="dash-card__action">Tümünü Gör →</a>
                <?php endif; ?>
            </div>
            <div class="dash-card__body" style="max-height:360px; overflow-y:auto;">
                <?php if (!empty($son_faaliyetler)): ?>
                <?php foreach ($son_faaliyetler as $fa): ?>
                <div class="dash-activity-item">
                    <div class="dash-activity-item__avatar" style="background: <?= avatarRengi($fa['kullanici_adi'], $avatar_renkleri) ?>">
                        <?= basHarfleri($fa['kullanici_adi']) ?>
                    </div>
                    <div class="dash-activity-item__info">
                        <div class="dash-activity-item__user"><?= htmlspecialchars($fa['kullanici_adi']) ?></div>
                        <div class="dash-activity-item__action"><?= htmlspecialchars(mb_strimwidth($fa['aciklama'] ?? $fa['islem_turu'], 0, 60, '…')) ?></div>
                    </div>
                    <span class="dash-activity-item__time"><?= $fa['tarih'] ? date('d.m H:i', strtotime($fa['tarih'])) : '' ?></span>
                </div>
                <?php endforeach; ?>
                <?php else: ?>
                <p class="text-muted small text-center py-3">Faaliyet kaydı bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Dikkat Gerektiren Uyarılar (Tasarım 2) -->
    <div class="col-lg-4">
        <div class="dash-alert-card h-100">
            <div class="dash-alert-card__title">
                <i class="fa-solid fa-triangle-exclamation me-2"></i>Dikkat Gerektirenler
            </div>
            <ul class="dash-alert-card__list">
                <?php if ($bekleyen_uye_sayisi > 0): ?>
                <li><?= $bekleyen_uye_sayisi ?> yeni bir üye başvurusu onay bekliyor.</li>
                <?php endif; ?>

                <?php
                // Kan grubu eksik olanları hesapla
                try {
                    $kan_eksik = $db_baglanti->query(
                        "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (kan_grubu IS NULL OR kan_grubu = '')"
                    )->fetchColumn();
                } catch (\PDOException $e) { $kan_eksik = 0; }
                if ($kan_eksik > 0): ?>
                <li><?= $kan_eksik ?> üyenin kan grubu bilgisi eksik.</li>
                <?php endif; ?>

                <?php
                // 3 ilçe başkanığına atanmamış ilçe var mı
                try {
                    $il_eksik = $db_baglanti->query(
                        "SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'onayli' AND (ikamet_ili IS NULL OR ikamet_ili = '')"
                    )->fetchColumn();
                } catch (\PDOException $e) { $il_eksik = 0; }
                if ($il_eksik > 0): ?>
                <li><?= $il_eksik ?> üyenin ikamet ili bilgisi girilmemiş.</li>
                <?php endif; ?>

                <?php if (count($dogum_gunu_uyeleri) > 0): ?>
                <li>Bugün <?= count($dogum_gunu_uyeleri) ?> üyenin doğum günü! 🎂</li>
                <?php endif; ?>

                <?php if ($bekleyen_uye_sayisi === 0 && $kan_eksik === 0 && $il_eksik === 0 && count($dogum_gunu_uyeleri) === 0): ?>
                <li style="color: #10b981;"><i class="fa-solid fa-check-circle me-1"></i>Her şey yolunda görünüyor!</li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</div>

<?php if ($is_yonetim || $is_gelistirici): ?>
<!-- ═══════════════════════════════════════════════════════════════
     SON GİRİŞ TAKİBİ (Yönetim/Geliştirici rolüne özel)
     ═══════════════════════════════════════════════════════════════ -->
<?php if (!empty($son_giris_verileri)): ?>
<div class="row g-4 mb-4">
    <?php
    $rol_baslik_harita = [
        'yonetim'      => ['Yönetim Hesapları', '#3b82f6'],
        'il_baskani'   => ['İl Başkanları', '#10b981'],
        'ilce_baskani' => ['İlçe Başkanları', '#8b5cf6'],
    ];
    foreach ($son_giris_verileri as $sg_rol => $hesaplar):
        if (empty($hesaplar)) continue;
        $baslik_bilgi = $rol_baslik_harita[$sg_rol] ?? [$sg_rol, '#6b7280'];
    ?>
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title">
                    <i class="fa-solid fa-clock" style="color: <?= $baslik_bilgi[1] ?>"></i>
                    <?= $baslik_bilgi[0] ?> — Son Giriş
                </h5>
            </div>
            <div class="dash-card__body" style="max-height:300px; overflow-y:auto;">
                <?php foreach ($hesaplar as $hesap):
                    $gun_farki = null;
                    if ($hesap['son_giris_tarihi']) {
                        $fark = (new DateTime())->diff(new DateTime($hesap['son_giris_tarihi']));
                        $gun_farki = $fark->days;
                    }
                    $durum_renk = $gun_farki === null ? '#ef4444' : ($gun_farki <= 7 ? '#10b981' : ($gun_farki <= 30 ? '#f59e0b' : '#ef4444'));
                ?>
                <div class="dash-member-item">
                    <div class="dash-member-item__avatar" style="background: <?= $durum_renk ?>">
                        <?= basHarfleri($hesap['kullanici_adi']) ?>
                    </div>
                    <div class="dash-member-item__info">
                        <div class="dash-member-item__name"><?= htmlspecialchars($hesap['kullanici_adi']) ?></div>
                        <div class="dash-member-item__meta">
                            <?= $hesap['sorumlu_il'] ? htmlspecialchars($hesap['sorumlu_il']) : '' ?>
                            <?= $hesap['sorumlu_ilce'] ? htmlspecialchars($hesap['sorumlu_ilce']) : '' ?>
                        </div>
                    </div>
                    <span class="dash-member-item__date" style="color: <?= $durum_renk ?>">
                        <?= $hesap['son_giris_tarihi'] ? date('d.m.Y', strtotime($hesap['son_giris_tarihi'])) : 'Hiç girmedi' ?>
                    </span>
                </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
    <?php endforeach; ?>
</div>
<?php endif; ?>
<?php endif; ?>

<!-- ═══════════════════════════════════════════════════════════════
     CHART.JS GRAFİKLER
     ═══════════════════════════════════════════════════════════════ -->
<!-- Leaflet.js — Yerel kurulum -->
<link rel="stylesheet" href="/yonetim/assets/leaflet/leaflet.min.css">
<script src="/yonetim/assets/leaflet/leaflet.min.js"></script>
<script>
window.addEventListener('load', function () {
    var haritaEl = document.getElementById('turkiyeHaritasi');
    if (!haritaEl || typeof L === 'undefined') return;

    // PHP'den gelen il verileri: { "İstanbul": 235, ... }
    var ilVerileri = <?php
        $il_map = [];
        foreach ($il_verileri as $iv) {
            if (!empty($iv['ikamet_ili'])) {
                $il_map[trim($iv['ikamet_ili'])] = (int) $iv['adet'];
            }
        }
        echo json_encode($il_map, JSON_UNESCAPED_UNICODE);
    ?>;

    // GeoJSON — ayrı PHP endpoint üzerinden sunuluyor (büyük dosya, inline gömmek yerine)
    var haritaGeojsonUrl = '/yonetim/api/harita-geojson.php';

    var maksUye = 0;
    Object.values(ilVerileri).forEach(function(v) { if (v > maksUye) maksUye = v; });
    if (maksUye < 1) maksUye = 1;

    function uyeRengi(sayi) {
        if (!sayi || sayi === 0) return '#fce4ec';
        var oran = Math.pow(sayi / maksUye, 0.4);
        return 'rgb(198,' + Math.round(228 - 208 * oran) + ',' + Math.round(236 - 196 * oran) + ')';
    }

    delete L.Icon.Default.prototype._getIconUrl;
    L.Icon.Default.mergeOptions({ iconUrl: '', shadowUrl: '', iconRetinaUrl: '' });

    var harita = L.map('turkiyeHaritasi', {
        center: [39.0, 35.5],
        zoom: 5,
        zoomControl: false,
        dragging: false,
        scrollWheelZoom: false,
        doubleClickZoom: false,
        boxZoom: false,
        keyboard: false,
        touchZoom: false,
        attributionControl: false,
    });

    var geojsonLayer;

    // Natural Earth adları → DB Türkçe adları eşleme tablosu
    var ilEsleme = {
        'Adana':'Adana','Adiyaman':'Adıyaman','Afyonkarahisar':'Afyonkarahisar',
        'Agri':'Ağrı','Aksaray':'Aksaray','Amasya':'Amasya','Ankara':'Ankara',
        'Antalya':'Antalya','Ardahan':'Ardahan','Artvin':'Artvin','Aydin':'Aydın',
        'Balikesir':'Balıkesir','Bartın':'Bartın','Batman':'Batman','Bayburt':'Bayburt',
        'Bilecik':'Bilecik','Bingöl':'Bingöl','Bitlis':'Bitlis','Bolu':'Bolu',
        'Burdur':'Burdur','Bursa':'Bursa','Denizli':'Denizli','Diyarbakir':'Diyarbakır',
        'Düzce':'Düzce','Edirne':'Edirne','Elazig':'Elazığ','Erzincan':'Erzincan',
        'Erzurum':'Erzurum','Eskisehir':'Eskişehir','Gaziantep':'Gaziantep',
        'Giresun':'Giresun','Gümüshane':'Gümüşhane','Hakkari':'Hakkari',
        'Hatay':'Hatay','Isparta':'Isparta','Istanbul':'İstanbul','Izmir':'İzmir',
        'Iğdir':'Iğdır','K. Maras':'Kahramanmaraş','Karabük':'Karabük',
        'Karaman':'Karaman','Kars':'Kars','Kastamonu':'Kastamonu','Kayseri':'Kayseri',
        'Kilis':'Kilis','Kinkkale':'Kırıkkale','Kirklareli':'Kırklareli',
        'Kirsehir':'Kırşehir','Kocaeli':'Kocaeli','Konya':'Konya','Kütahya':'Kütahya',
        'Malatya':'Malatya','Manisa':'Manisa','Mardin':'Mardin','Mersin':'Mersin',
        'Mugla':'Muğla','Mus':'Muş','Nevsehir':'Nevşehir','Nigde':'Niğde',
        'Ordu':'Ordu','Osmaniye':'Osmaniye','Rize':'Rize','Sakarya':'Sakarya',
        'Samsun':'Samsun','Sanliurfa':'Şanlıurfa','Siirt':'Siirt','Sinop':'Sinop',
        'Sirnak':'Şırnak','Sivas':'Sivas','Tekirdag':'Tekirdağ','Tokat':'Tokat',
        'Trabzon':'Trabzon','Tunceli':'Tunceli','Usak':'Uşak','Van':'Van',
        'Yalova':'Yalova','Yozgat':'Yozgat','Zinguldak':'Zonguldak',
        'Çanakkale':'Çanakkale','Çankiri':'Çankırı','Çorum':'Çorum'
    };

    function turkceAd(feature) {
        var eng = (feature.properties && feature.properties.name) ? feature.properties.name : '';
        return ilEsleme[eng] || eng;
    }

    function stilFonksiyonu(feature) {
        var tr = turkceAd(feature);
        var sayi = ilVerileri[tr] || 0;
        return { fillColor: uyeRengi(sayi), weight: 0.6, opacity: 1, color: '#fff', fillOpacity: 0.92 };
    }

    function onEachFeature(feature, layer) {
        var tr   = turkceAd(feature);
        var sayi = ilVerileri[tr] || 0;
        layer.bindTooltip(
            '<strong style="font-size:13px;">' + tr + '</strong>' +
            '<br><span style="color:#c62828;font-weight:600;">' + sayi + ' üye</span>',
            { sticky: true, className: 'il-tooltip' }
        );
        layer.on({
            mouseover: function(e) { e.target.setStyle({ weight: 2, color: '#c62828', fillOpacity: 1 }); },
            mouseout:  function(e) { geojsonLayer.resetStyle(e.target); }
        });
    }

    fetch(haritaGeojsonUrl)
        .then(function(r) {
            if (!r.ok) throw new Error('HTTP ' + r.status);
            return r.json();
        })
        .then(function(data) {
            geojsonLayer = L.geoJSON(data, {
                style: stilFonksiyonu,
                onEachFeature: onEachFeature,
            }).addTo(harita);
            harita.fitBounds(geojsonLayer.getBounds(), { padding: [8, 8] });
            setTimeout(function() { harita.invalidateSize(); }, 100);
        })
        .catch(function(err) {
            console.error('Harita GeoJSON yüklenemedi:', err);
        });
});
</script>
<style>
.il-tooltip {
    background: #fff !important;
    border: none !important;
    border-radius: 8px !important;
    padding: 6px 10px !important;
    box-shadow: 0 4px 16px rgba(0,0,0,0.15) !important;
    font-family: inherit !important;
}
#turkiyeHaritasi { background: transparent !important; }
#turkiyeHaritasi .leaflet-container { background: transparent !important; }
/* Doğum günü kart hover */
.dg-kart { cursor: pointer; }
.dg-kart:hover {
    background-color: rgba(0,0,0,0.04) !important;
    box-shadow: 0 3px 10px rgba(0,0,0,0.1);
    transform: translateY(-1px);
}
</style>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function() {
    'use strict';

    var chartDefaults = {
        responsive: true,
        maintainAspectRatio: false,
        plugins: {
            legend: { display: false }
        }
    };

    // B: Aylık Üye Artış Trendi — çizgi grafik
    var artisCtx = document.getElementById('uyeArtisTrendi');
    if (artisCtx) {
        new Chart(artisCtx, {
            type: 'line',
            data: {
                labels: <?= json_encode($artis_etiketler) ?>,
                datasets: [{
                    label: 'Yeni Üye',
                    data: <?= json_encode($artis_sayilar) ?>,
                    borderColor: '#3b82f6',
                    backgroundColor: 'rgba(59, 130, 246, 0.08)',
                    borderWidth: 2.5,
                    fill: true,
                    tension: 0.4,
                    pointBackgroundColor: '#3b82f6',
                    pointBorderColor: '#fff',
                    pointBorderWidth: 2,
                    pointRadius: 4,
                    pointHoverRadius: 6
                }]
            },
            options: Object.assign({}, chartDefaults, {
                scales: {
                    y: {
                        beginAtZero: true,
                        grid: { color: 'rgba(0,0,0,0.04)' },
                        ticks: { font: { size: 11 } }
                    },
                    x: {
                        grid: { display: false },
                        ticks: { font: { size: 11 } }
                    }
                }
            })
        });
    }

    // C: Bölge Dağılımı — donut (Türkiye'nin 7 coğrafi bölgesi)
    var bolgeCtx = document.getElementById('bolgeDagilimi');
    if (bolgeCtx) {
        // Her bölge için özgün renk — 7 bölge + Diğer
        var bolgeRenkler = {
            'Marmara Bölgesi':            '#3b82f6',
            'Ege Bölgesi':                '#06b6d4',
            'Akdeniz Bölgesi':            '#f97316',
            'İç Anadolu Bölgesi':         '#f59e0b',
            'Karadeniz Bölgesi':          '#10b981',
            'Doğu Anadolu Bölgesi':       '#8b5cf6',
            'Güneydoğu Anadolu Bölgesi':  '#ef4444',
            'Diğer':                      '#6b7280',
        };
        var bolgeLabels = <?= json_encode($bolge_etiketler) ?>;
        var bolgeBgRenkler = bolgeLabels.map(function(l) {
            return bolgeRenkler[l] || '#6b7280';
        });
        var bolgeToplam = <?= json_encode($bolge_degerler) ?>.reduce(function(a, b) { return a + b; }, 0);

        new Chart(bolgeCtx, {
            type: 'doughnut',
            data: {
                labels: bolgeLabels,
                datasets: [{
                    data: <?= json_encode($bolge_degerler) ?>,
                    backgroundColor: bolgeBgRenkler,
                    borderWidth: 2,
                    borderColor: '#fff',
                    hoverBorderWidth: 3
                }]
            },
            options: Object.assign({}, chartDefaults, {
                cutout: '60%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: {
                            padding: 10,
                            font: { size: 10.5 },
                            usePointStyle: true,
                            pointStyleWidth: 10
                        }
                    },
                    tooltip: {
                        callbacks: {
                            label: function(ctx) {
                                var yuzde = ((ctx.raw / bolgeToplam) * 100).toFixed(1);
                                return ' ' + ctx.raw + ' üye (%' + yuzde + ')';
                            }
                        }
                    }
                }
            })
        });
    }

    // D: Kan Grubu — donut
    var kanCtx = document.getElementById('kanGrubuGrafik');
    if (kanCtx) {
        new Chart(kanCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($admin_kan_etiketler) ?>,
                datasets: [{
                    data: <?= json_encode($admin_kan_sayilar) ?>,
                    backgroundColor: ['#ef4444','#f97316','#f59e0b','#10b981','#06b6d4','#3b82f6','#8b5cf6','#ec4899'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: Object.assign({}, chartDefaults, {
                cutout: '55%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { padding: 8, font: { size: 10 } }
                    }
                }
            })
        });
    }
})();
</script>
