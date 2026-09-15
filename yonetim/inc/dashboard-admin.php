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
    $son_faaliyetler = $faaliyet_sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    $son_faaliyetler = [];
}

// Duyurular
$duyurular = [];
try {
    $duyuru_sorgu = $db_baglanti->query(
        "SELECT baslik, icerik, tarih FROM duyurular WHERE aktif = 1 ORDER BY tarih DESC LIMIT 5"
    );
    $duyurular = $duyuru_sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    // Tablo henüz yoksa boş bırak
    $duyurular = [];
}

// Avatar renk paleti (hash'e göre)
$avatar_renkleri = ['#3b82f6','#ef4444','#f59e0b','#10b981','#8b5cf6','#ec4899','#06b6d4','#f97316','#14b8a6','#6366f1'];
function avatarRengi(string $isim, array $renkler): string {
    return $renkler[crc32($isim) % count($renkler)];
}
function basHarfleri(string $isim): string {
    $parcalar = explode(' ', trim($isim));
    $harfler = '';
    foreach (array_slice($parcalar, 0, 2) as $p) {
        $harfler .= mb_strtoupper(mb_substr(trim($p), 0, 1));
    }
    return $harfler;
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

// Bölge dağılımı (Trabzon ilçelerini 4 bölgeye ayır)
$bolge_harita = [
    'Doğu'  => ['Arsin','Araklı','Sürmene','Of','Çaykara','Hayrat','Dernekpazarı','Köprübaşı'],
    'Batı'  => ['Akçaabat','Vakfıkebir','Çarşıbaşı','Beşikdüzü','Şalpazarı','Tonya','Eynesil','Görele'],
    'Merkez'=> ['Ortahisar','Yomra'],
    'Güney' => ['Maçka','Düzköy','Torul'],
];
$bolge_sayilari = ['Doğu'=>0,'Batı'=>0,'Merkez'=>0,'Güney'=>0,'Diğer'=>0];
foreach ($ilce_verileri as $iv) {
    $ilce = $iv['trabzon_ilcesi'] ?? '';
    $bulunan = false;
    foreach ($bolge_harita as $bolge => $ilceler) {
        if (in_array($ilce, $ilceler, true)) {
            $bolge_sayilari[$bolge] += (int)$iv['adet'];
            $bulunan = true;
            break;
        }
    }
    if (!$bulunan) {
        $bolge_sayilari['Diğer'] += (int)$iv['adet'];
    }
}
// Sıfır bölgeleri kaldır
$bolge_sayilari = array_filter($bolge_sayilari, fn($v) => $v > 0);
$bolge_etiketler = array_keys($bolge_sayilari);
$bolge_degerler  = array_values($bolge_sayilari);
?>

<!-- ═══════════════════════════════════════════════════════════════
     K: MOTİVASYON BANNER
     ═══════════════════════════════════════════════════════════════ -->
<div class="dash-banner mb-4">
    <div class="dash-banner__text">
        <h3><i class="fa-solid fa-flag me-2"></i>Trabzon'un gücü, gönül veren insanlarında…</h3>
        <p>Bugün derneğimizde <strong><?= number_format($toplam_uye) ?></strong> kayıtlı üye,
           <strong><?= $toplam_il ?></strong> aktif ilde teşkilatlanma ile güçlenmeye devam ediyoruz.</p>
    </div>
    <div class="dash-banner__quote">
        "Memleket sevdası,<br>insana en güzel hizmeti yaptırır."
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
    <div class="col-lg-8">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-chart-line text-primary"></i> Aylık Üye Artış Trendi</h5>
                <span class="dash-card__action">Son 12 Ay</span>
            </div>
            <div class="dash-card__body">
                <canvas id="uyeArtisTrendi" height="260"></canvas>
            </div>
        </div>
    </div>

    <!-- Sağ: Bölge Dağılımı -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-pie-chart text-success"></i> Bölge Dağılımı</h5>
            </div>
            <div class="dash-card__body text-center">
                <canvas id="bolgeDagilimi" height="220"></canvas>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2">
                    <?php foreach ($bolge_sayilari as $bolge => $sayi): ?>
                    <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.75rem;">
                        <?= htmlspecialchars($bolge) ?>: <strong><?= $sayi ?></strong>
                    </span>
                    <?php endforeach; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- ═══════════════════════════════════════════════════════════════
     C (devam): İL DAĞILIMI  +  D: KAN GRUBU  +  E: KURUMLAR
     ═══════════════════════════════════════════════════════════════ -->
<div class="row g-4 mb-4">

    <!-- İllere Göre Üye Dağılımı -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-map text-danger"></i> İllere Göre Dağılım</h5>
                <a href="index.php?sayfa=uyeler" class="dash-card__action">Tümü →</a>
            </div>
            <div class="dash-card__body" style="max-height:360px; overflow-y:auto;">
                <table class="dash-kurum-table">
                    <thead><tr><th>#</th><th>İl</th><th>Üye</th></tr></thead>
                    <tbody>
                    <?php foreach (array_slice($il_verileri, 0, 10) as $i => $il): ?>
                    <tr>
                        <td class="kurum-rank"><?= $i + 1 ?></td>
                        <td><?= htmlspecialchars($il['ikamet_ili']) ?></td>
                        <td><strong><?= $il['adet'] ?></strong></td>
                    </tr>
                    <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>

    <!-- Kan Grubu Dağılımı (Tasarım 1 — donut + yüzde) -->
    <div class="col-lg-4">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-droplet text-danger"></i> Kan Grubu Dağılımı</h5>
            </div>
            <div class="dash-card__body text-center">
                <?php if (!empty($admin_kan_etiketler)): ?>
                <canvas id="kanGrubuGrafik" height="200"></canvas>
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

    <!-- En Çok Üye Olan Kurumlar (Tasarım 1 — tablo) -->
    <div class="col-lg-4">
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

    // C: Bölge Dağılımı — donut
    var bolgeCtx = document.getElementById('bolgeDagilimi');
    if (bolgeCtx) {
        new Chart(bolgeCtx, {
            type: 'doughnut',
            data: {
                labels: <?= json_encode($bolge_etiketler) ?>,
                datasets: [{
                    data: <?= json_encode($bolge_degerler) ?>,
                    backgroundColor: ['#3b82f6','#f59e0b','#10b981','#8b5cf6','#6b7280'],
                    borderWidth: 2,
                    borderColor: '#fff'
                }]
            },
            options: Object.assign({}, chartDefaults, {
                cutout: '60%',
                plugins: {
                    legend: {
                        display: true,
                        position: 'bottom',
                        labels: { padding: 12, font: { size: 11 } }
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
