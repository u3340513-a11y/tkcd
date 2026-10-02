<?php
/**
 * İl-İlçe Yönetimi sayfası.
 *
 * Neden: Yönetim kadrosunun, tüm Türkiye'deki il ve ilçe başkanlarını
 * tek bir arayüzde görmesini sağlar. Hangi illerde il başkanı atanmış,
 * hangi ilçelerde ilçe başkanı var — hepsi bir bakışta görülür.
 *
 * Erişim: admin, yonetim, gelistirici rolleri (index.php'de kontrol edilir)
 *
 * Veri kaynakları:
 *   - turkiye-ilce-verileri.php → 81 il + ilçeleri (statik)
 *   - dernek_uyeler tablosu → il/ilçe başkanları (dinamik)
 *
 * Değişkenler: index.php'den $db_baglanti, $is_admin, $is_yonetim,
 *              $is_gelistirici, $sayfa
 *
 * @var PDO $db_baglanti
 */

declare(strict_types=1);

// ── İl-İlçe statik verisi ────────────────────────────────────────────────
$turkiye_ilce_verileri = require __DIR__ . '/turkiye-ilce-verileri.php';

// ── Seçili il (GET parametresi) ──────────────────────────────────────────
$secili_il = trim((string) ($_GET['il'] ?? ''));

// Güvenlik: Sadece geçerli il adlarını kabul et
if ($secili_il !== '' && !array_key_exists($secili_il, $turkiye_ilce_verileri)) {
    $secili_il = '';
}

// ── İl başkanı sorgulama fonksiyonu ──────────────────────────────────────
/**
 * Belirtilen ildeki il başkanını veritabanından çeker.
 *
 * Kontrol sırası: temsilci_turu, ek_gorev, ek_roller JSON
 *
 * @param PDO    $db Veritabanı bağlantısı
 * @param string $il İl adı
 * @return array|null Başkan bilgisi veya null
 */
function il_baskanini_bul(PDO $db, string $il): ?array
{
    $sql = "SELECT id, adi_soyadi, telefon, ikamet_ili, ikamet_ilcesi,
                   temsilci_turu, ek_gorev, ek_roller, kurum
              FROM dernek_uyeler
             WHERE onay_durumu = 'onayli'
               AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
               AND (
                   temsilci_turu IN ('İl Başkanı', 'İl Temsilcisi')
                   OR ek_gorev IN ('İl Başkanı', 'İl Temsilcisi')
                   OR JSON_CONTAINS(ek_roller, '\"İl Başkanı\"')
               )
             ORDER BY
               CASE
                 WHEN temsilci_turu = 'İl Başkanı' THEN 1
                 WHEN ek_gorev = 'İl Başkanı' THEN 2
                 WHEN temsilci_turu = 'İl Temsilcisi' THEN 3
                 ELSE 4
               END
             LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([':il' => $il]);
    $sonuc = $stmt->fetch(PDO::FETCH_ASSOC);

    return $sonuc ?: null;
}

/**
 * Belirtilen il ve ilçedeki ilçe başkanını veritabanından çeker.
 *
 * @param PDO    $db    Veritabanı bağlantısı
 * @param string $il    İl adı
 * @param string $ilce  İlçe adı
 * @return array|null Başkan bilgisi veya null
 */
function ilce_baskanini_bul(PDO $db, string $il, string $ilce): ?array
{
    $sql = "SELECT id, adi_soyadi, telefon, ikamet_ili, ikamet_ilcesi,
                   temsilci_turu, ek_gorev, ek_roller, kurum
              FROM dernek_uyeler
             WHERE onay_durumu = 'onayli'
               AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
               AND (
                   LOWER(TRIM(ikamet_ilcesi)) = LOWER(:ilce)
                   OR LOWER(TRIM(trabzon_ilcesi)) = LOWER(:ilce2)
               )
               AND (
                   temsilci_turu IN ('İlçe Başkanı', 'İlçe Temsilcisi')
                   OR ek_gorev IN ('İlçe Başkanı', 'İlçe Temsilcisi')
                   OR JSON_CONTAINS(ek_roller, '\"İlçe Başkanı\"')
               )
             ORDER BY
               CASE
                 WHEN temsilci_turu = 'İlçe Başkanı' THEN 1
                 WHEN ek_gorev = 'İlçe Başkanı' THEN 2
                 WHEN temsilci_turu = 'İlçe Temsilcisi' THEN 3
                 ELSE 4
               END
             LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([':il' => $il, ':ilce' => $ilce, ':ilce2' => $ilce]);
    $sonuc = $stmt->fetch(PDO::FETCH_ASSOC);

    return $sonuc ?: null;
}

/**
 * Belirtilen ildeki toplam onaylı üye sayısını döndürür.
 *
 * @param PDO    $db Veritabanı bağlantısı
 * @param string $il İl adı
 * @return int
 */
function il_uye_sayisi(PDO $db, string $il): int
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)"
    );
    $stmt->execute([':il' => $il]);
    return (int) $stmt->fetchColumn();
}

/**
 * Belirtilen il ve ilçedeki toplam onaylı üye sayısını döndürür.
 *
 * @param PDO    $db   Veritabanı bağlantısı
 * @param string $il   İl adı
 * @param string $ilce İlçe adı
 * @return int
 */
function ilce_uye_sayisi(PDO $db, string $il, string $ilce): int
{
    $stmt = $db->prepare(
        "SELECT COUNT(*) FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND LOWER(TRIM(ikamet_ili)) = LOWER(:il)
            AND (
                LOWER(TRIM(ikamet_ilcesi)) = LOWER(:ilce)
                OR LOWER(TRIM(trabzon_ilcesi)) = LOWER(:ilce2)
            )"
    );
    $stmt->execute([':il' => $il, ':ilce' => $ilce, ':ilce2' => $ilce]);
    return (int) $stmt->fetchColumn();
}

// ── Tüm iller için üye sayıları (il listesi görünümü için) ───────────────
$il_uye_sayilari = [];
if ($secili_il === '') {
    try {
        $stmt = $db_baglanti->query(
            "SELECT TRIM(ikamet_ili) AS il, COUNT(*) AS adet
               FROM dernek_uyeler
              WHERE onay_durumu = 'onayli'
                AND ikamet_ili IS NOT NULL
                AND TRIM(ikamet_ili) != ''
              GROUP BY TRIM(ikamet_ili)"
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $satir) {
            $il_uye_sayilari[mb_convert_case(trim($satir['il']), MB_CASE_TITLE, 'UTF-8')] = (int) $satir['adet'];
        }
    } catch (PDOException $e) {
        error_log('İl üye sayıları sorgu hatası: ' . $e->getMessage());
    }
}

// ── Tüm iller için il başkanı var mı kontrolü ───────────────────────────
$il_baskanlari_haritasi = [];
if ($secili_il === '') {
    try {
        $stmt = $db_baglanti->query(
            "SELECT TRIM(ikamet_ili) AS il, adi_soyadi
               FROM dernek_uyeler
              WHERE onay_durumu = 'onayli'
                AND (
                    temsilci_turu IN ('İl Başkanı', 'İl Temsilcisi')
                    OR ek_gorev IN ('İl Başkanı', 'İl Temsilcisi')
                    OR JSON_CONTAINS(ek_roller, '\"İl Başkanı\"')
                )
              ORDER BY
                CASE
                  WHEN temsilci_turu = 'İl Başkanı' THEN 1
                  WHEN ek_gorev = 'İl Başkanı' THEN 2
                  ELSE 3
                END"
        );
        foreach ($stmt->fetchAll(PDO::FETCH_ASSOC) as $satir) {
            $ilAdi = mb_convert_case(trim($satir['il']), MB_CASE_TITLE, 'UTF-8');
            if (!isset($il_baskanlari_haritasi[$ilAdi])) {
                $il_baskanlari_haritasi[$ilAdi] = $satir['adi_soyadi'];
            }
        }
    } catch (PDOException $e) {
        error_log('İl başkanları sorgu hatası: ' . $e->getMessage());
    }
}

// ── Baş harfleri initials ve renk fonksiyonları ──────────────────────────
$avatar_renk = static function (string $ad): string {
    $renkler = ['#c62828','#1565c0','#2e7d32','#6a1b9a','#e65100','#00838f','#37474f','#ad1457'];
    return $renkler[abs(crc32($ad)) % count($renkler)];
};

$bas_harfler = static function (string $ad): string {
    $parcalar = array_filter(explode(' ', trim($ad)));
    $harfler = array_map(
        fn(string $parca) => mb_substr($parca, 0, 1, 'UTF-8'),
        array_slice($parcalar, 0, 2)
    );
    return mb_strtoupper(implode('', $harfler), 'UTF-8');
};
?>

<div class="container-fluid py-4 px-md-4">

    <!-- ── SAYFA BAŞLIĞI ── -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:linear-gradient(135deg,#1565c0,#0d47a1);">
                    <i class="fa-solid fa-map-location-dot text-white fa-lg"></i>
                </div>
                <div>
                    <h1 class="fw-bold text-dark mb-0" style="font-size:1.6rem;">
                        İl-İlçe Yönetimi
                    </h1>
                    <p class="text-muted mb-0 small">
                        <?php if ($secili_il !== ''): ?>
                            <a href="index.php?sayfa=il-ilce-yonetimi" class="text-decoration-none text-primary">
                                <i class="fa-solid fa-arrow-left me-1"></i>Tüm İller
                            </a>
                            <span class="mx-1 text-muted">/</span>
                            <span class="fw-semibold"><?= htmlspecialchars($secili_il) ?></span> ili detayı
                        <?php else: ?>
                            81 il ve ilçe bazında başkan atamaları
                        <?php endif; ?>
                    </p>
                </div>
            </div>
        </div>
    </div>

<?php if ($secili_il === ''): ?>
    <!-- ╔══════════════════════════════════════════════════════════════════╗ -->
    <!-- ║  İL LİSTESİ GÖRÜNÜMÜ                                           ║ -->
    <!-- ╚══════════════════════════════════════════════════════════════════╝ -->

    <!-- Arama kutusu -->
    <div class="row mb-4">
        <div class="col-lg-6 col-md-8">
            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                <span class="input-group-text bg-white border-end-0 px-3">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text"
                       id="ily-arama"
                       class="form-control border-start-0 py-2"
                       placeholder="İl adı ile ara…"
                       autocomplete="off">
            </div>
        </div>

        <!-- Özet istatistik -->
        <div class="col-lg-6 col-md-4 d-flex align-items-center justify-content-end gap-3">
            <?php
            $atanmis_il_sayisi = count($il_baskanlari_haritasi);
            $toplam_il_sayisi  = count($turkiye_ilce_verileri);
            ?>
            <div class="text-end">
                <span class="badge rounded-pill px-3 py-2 fs-6"
                      style="background:rgba(21,101,192,0.1);color:#1565c0;">
                    <i class="fa-solid fa-user-tie me-1"></i>
                    <?= $atanmis_il_sayisi ?> / <?= $toplam_il_sayisi ?> ilde başkan atanmış
                </span>
            </div>
        </div>
    </div>

    <!-- İl kartları grid -->
    <div class="row g-3" id="ily-il-listesi">
        <?php foreach ($turkiye_ilce_verileri as $il_adi => $ilceleri): ?>
            <?php
            $uye_sayisi  = $il_uye_sayilari[$il_adi] ?? 0;
            $il_baskani  = $il_baskanlari_haritasi[$il_adi] ?? null;
            $ilce_sayisi = count($ilceleri);
            ?>
            <div class="col-sm-6 col-md-4 col-lg-3 ily-il-karti"
                 data-il="<?= htmlspecialchars(mb_strtolower($il_adi, 'UTF-8')) ?>">
                <a href="index.php?sayfa=il-ilce-yonetimi&il=<?= rawurlencode($il_adi) ?>"
                   class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden text-decoration-none ily-kart"
                   style="transition:transform 0.2s,box-shadow 0.2s;">
                    <!-- Üst renk şeridi -->
                    <div style="height:4px;background:<?= $il_baskani ? 'linear-gradient(90deg,#2e7d32,#43a047)' : 'linear-gradient(90deg,#ef5350,#e53935)' ?>;"></div>

                    <div class="card-body p-3">
                        <div class="d-flex align-items-center justify-content-between mb-2">
                            <h3 class="fw-bold text-dark mb-0" style="font-size:1rem;">
                                <?= htmlspecialchars($il_adi) ?>
                            </h3>
                            <?php if ($uye_sayisi > 0): ?>
                                <span class="badge rounded-pill px-2 py-1"
                                      style="background:rgba(21,101,192,0.1);color:#1565c0;font-size:0.72rem;">
                                    <?= $uye_sayisi ?> üye
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- İl başkanı durumu -->
                        <div class="d-flex align-items-center gap-2 mb-2">
                            <?php if ($il_baskani): ?>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:28px;height:28px;background:rgba(46,125,50,0.1);color:#2e7d32;font-size:0.65rem;font-weight:700;">
                                    <?= htmlspecialchars($bas_harfler($il_baskani)) ?>
                                </span>
                                <div class="flex-grow-1 min-width-0">
                                    <div class="text-dark" style="font-size:0.78rem;line-height:1.2;font-weight:600;">
                                        <?= htmlspecialchars($il_baskani) ?>
                                    </div>
                                    <div style="font-size:0.68rem;color:#2e7d32;">İl Başkanı</div>
                                </div>
                            <?php else: ?>
                                <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                      style="width:28px;height:28px;background:rgba(239,83,80,0.1);color:#e53935;font-size:0.7rem;">
                                    <i class="fa-solid fa-user-slash"></i>
                                </span>
                                <span style="font-size:0.75rem;color:#e53935;font-weight:500;">
                                    Başkan atanmamış
                                </span>
                            <?php endif; ?>
                        </div>

                        <!-- İlçe sayısı -->
                        <div class="d-flex align-items-center gap-1" style="font-size:0.72rem;color:#94a3b8;">
                            <i class="fa-solid fa-layer-group" style="font-size:0.65rem;"></i>
                            <?= $ilce_sayisi ?> ilçe
                        </div>
                    </div>
                </a>
            </div>
        <?php endforeach; ?>
    </div>

    <!-- Sonuç bulunamadı -->
    <div id="ily-sonuc-yok" class="text-center py-5 d-none">
        <i class="fa-solid fa-search text-muted mb-3" style="font-size:2.5rem;opacity:0.3;"></i>
        <p class="text-muted mb-0">Aramanıza uygun il bulunamadı.</p>
    </div>

    <script>
    (function() {
        'use strict';
        var aramaInput = document.getElementById('ily-arama');
        var kartlar    = document.querySelectorAll('.ily-il-karti');
        var sonucYok   = document.getElementById('ily-sonuc-yok');

        if (!aramaInput || kartlar.length === 0) return;

        aramaInput.addEventListener('input', function() {
            var filtre     = this.value.toLowerCase().trim();
            var gorunenSay = 0;

            kartlar.forEach(function(kart) {
                var ilAdi = kart.getAttribute('data-il') || '';
                var eslesme = filtre === '' || ilAdi.indexOf(filtre) !== -1;
                kart.style.display = eslesme ? '' : 'none';
                if (eslesme) gorunenSay++;
            });

            if (sonucYok) {
                sonucYok.classList.toggle('d-none', gorunenSay > 0);
            }
        });
    })();
    </script>

<?php else: ?>
    <!-- ╔══════════════════════════════════════════════════════════════════╗ -->
    <!-- ║  İL DETAY GÖRÜNÜMÜ                                              ║ -->
    <!-- ╚══════════════════════════════════════════════════════════════════╝ -->

    <?php
    $ilceler = $turkiye_ilce_verileri[$secili_il] ?? [];
    $il_toplam_uye = 0;

    try {
        $il_toplam_uye = il_uye_sayisi($db_baglanti, $secili_il);
        $il_baskan_veri = il_baskanini_bul($db_baglanti, $secili_il);
    } catch (PDOException $e) {
        error_log('İl detay sorgu hatası: ' . $e->getMessage());
        $il_baskan_veri = null;
    }
    ?>

    <!-- İl Başkanı kartı -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="card border-0 shadow-sm rounded-4 overflow-hidden">
                <div style="height:5px;background:linear-gradient(90deg,#1565c0,#42a5f5);"></div>
                <div class="card-body p-4">
                    <div class="d-flex align-items-center gap-2 mb-3">
                        <i class="fa-solid fa-crown" style="color:#f59e0b;font-size:1.1rem;"></i>
                        <h2 class="fw-bold mb-0" style="font-size:1.15rem;color:#1e293b;">
                            <?= htmlspecialchars($secili_il) ?> İl Başkanı
                        </h2>
                        <span class="badge rounded-pill px-2 py-1 ms-auto"
                              style="background:rgba(21,101,192,0.1);color:#1565c0;font-size:0.75rem;">
                            <?= $il_toplam_uye ?> kayıtlı üye
                        </span>
                    </div>

                    <?php if ($il_baskan_veri): ?>
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3"
                             style="background:rgba(46,125,50,0.05);border:1px solid rgba(46,125,50,0.15);">
                            <?php $renk = $avatar_renk($il_baskan_veri['adi_soyadi']); ?>
                            <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width:56px;height:56px;background:<?= $renk ?>18;color:<?= $renk ?>;font-size:1.2rem;border:2px solid <?= $renk ?>22;">
                                <?= htmlspecialchars($bas_harfler($il_baskan_veri['adi_soyadi'])) ?>
                            </div>
                            <div class="flex-grow-1">
                                <div class="fw-bold text-dark" style="font-size:1.05rem;">
                                    <?= htmlspecialchars($il_baskan_veri['adi_soyadi']) ?>
                                </div>
                                <div class="d-flex flex-wrap gap-2 mt-1">
                                    <span class="badge rounded-pill px-2 py-1"
                                          style="background:rgba(46,125,50,0.12);color:#2e7d32;font-size:0.72rem;">
                                        <i class="fa-solid fa-user-tie me-1"></i>İl Başkanı
                                    </span>
                                    <?php if (!empty($il_baskan_veri['kurum'])): ?>
                                        <span class="badge rounded-pill px-2 py-1"
                                              style="background:rgba(100,116,139,0.1);color:#64748b;font-size:0.7rem;">
                                            <i class="fa-solid fa-building me-1"></i>
                                            <?= htmlspecialchars($il_baskan_veri['kurum']) ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                            </div>
                            <a href="index.php?sayfa=uye-detay&id=<?= (int) $il_baskan_veri['id'] ?>"
                               class="btn btn-outline-primary btn-sm rounded-pill px-3 flex-shrink-0"
                               style="font-size:0.78rem;">
                                <i class="fa-solid fa-id-card me-1"></i>Profil
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-3 p-3 rounded-3"
                             style="background:rgba(239,68,68,0.04);border:1px dashed rgba(239,68,68,0.3);">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                  style="width:56px;height:56px;background:rgba(239,68,68,0.08);color:#ef4444;font-size:1.3rem;">
                                <i class="fa-solid fa-user-slash"></i>
                            </span>
                            <div>
                                <div class="fw-semibold" style="color:#ef4444;font-size:0.95rem;">
                                    Henüz il başkanı atanmamış
                                </div>
                                <div class="text-muted" style="font-size:0.78rem;">
                                    Bu il için bir il başkanı tanımlanmamıştır.
                                </div>
                            </div>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>

    <!-- İlçe arama -->
    <div class="row mb-3">
        <div class="col-lg-5 col-md-6">
            <div class="input-group shadow-sm rounded-3 overflow-hidden">
                <span class="input-group-text bg-white border-end-0 px-3">
                    <i class="fa-solid fa-search text-muted"></i>
                </span>
                <input type="text"
                       id="ily-ilce-arama"
                       class="form-control border-start-0 py-2"
                       placeholder="İlçe adı ile ara…"
                       autocomplete="off">
            </div>
        </div>
        <div class="col-lg-7 col-md-6 d-flex align-items-center justify-content-end">
            <span class="text-muted" style="font-size:0.82rem;">
                <i class="fa-solid fa-layer-group me-1"></i>
                <?= count($ilceler) ?> ilçe listeleniyor
            </span>
        </div>
    </div>

    <!-- İlçe listesi -->
    <div class="row g-3" id="ily-ilce-listesi">
        <?php
        foreach ($ilceler as $ilce_adi):
            $ilce_baskan_veri = null;
            $ilce_uye_s = 0;

            try {
                $ilce_baskan_veri = ilce_baskanini_bul($db_baglanti, $secili_il, $ilce_adi);
                $ilce_uye_s = ilce_uye_sayisi($db_baglanti, $secili_il, $ilce_adi);
            } catch (PDOException $e) {
                error_log('İlçe detay sorgu hatası (' . $ilce_adi . '): ' . $e->getMessage());
            }
        ?>
        <div class="col-sm-6 col-lg-4 ily-ilce-karti"
             data-ilce="<?= htmlspecialchars(mb_strtolower($ilce_adi, 'UTF-8')) ?>">
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden"
                 style="transition:transform 0.2s,box-shadow 0.2s;">
                <div style="height:3px;background:<?= $ilce_baskan_veri ? 'linear-gradient(90deg,#6a1b9a,#ab47bc)' : 'linear-gradient(90deg,#ef5350,#e53935)' ?>;"></div>

                <div class="card-body p-3">
                    <div class="d-flex align-items-center justify-content-between mb-2">
                        <h4 class="fw-bold mb-0" style="font-size:0.92rem;color:#1e293b;">
                            <i class="fa-solid fa-map-pin me-1" style="font-size:0.7rem;color:#94a3b8;"></i>
                            <?= htmlspecialchars($ilce_adi) ?>
                        </h4>
                        <?php if ($ilce_uye_s > 0): ?>
                            <span class="badge rounded-pill px-2 py-1"
                                  style="background:rgba(106,27,154,0.08);color:#6a1b9a;font-size:0.68rem;">
                                <?= $ilce_uye_s ?> üye
                            </span>
                        <?php endif; ?>
                    </div>

                    <?php if ($ilce_baskan_veri): ?>
                        <?php $ilce_renk = $avatar_renk($ilce_baskan_veri['adi_soyadi']); ?>
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3"
                             style="background:rgba(106,27,154,0.04);border:1px solid rgba(106,27,154,0.12);">
                            <div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                                 style="width:36px;height:36px;background:<?= $ilce_renk ?>18;color:<?= $ilce_renk ?>;font-size:0.72rem;border:1px solid <?= $ilce_renk ?>22;">
                                <?= htmlspecialchars($bas_harfler($ilce_baskan_veri['adi_soyadi'])) ?>
                            </div>
                            <div class="flex-grow-1 min-width-0">
                                <div class="fw-semibold text-dark" style="font-size:0.82rem;line-height:1.2;">
                                    <?= htmlspecialchars($ilce_baskan_veri['adi_soyadi']) ?>
                                </div>
                                <div style="font-size:0.68rem;color:#6a1b9a;font-weight:500;">İlçe Başkanı</div>
                            </div>
                            <a href="index.php?sayfa=uye-detay&id=<?= (int) $ilce_baskan_veri['id'] ?>"
                               class="btn btn-sm p-0 text-primary flex-shrink-0"
                               style="font-size:0.72rem;"
                               title="Üye Kartı">
                                <i class="fa-solid fa-arrow-up-right-from-square"></i>
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="d-flex align-items-center gap-2 p-2 rounded-3"
                             style="background:rgba(239,68,68,0.03);border:1px dashed rgba(239,68,68,0.2);">
                            <span class="d-inline-flex align-items-center justify-content-center rounded-circle flex-shrink-0"
                                  style="width:36px;height:36px;background:rgba(239,68,68,0.06);color:#ef4444;font-size:0.75rem;">
                                <i class="fa-solid fa-user-slash"></i>
                            </span>
                            <span style="font-size:0.78rem;color:#ef4444;font-weight:500;">
                                Henüz ilçe başkanı atanmadı
                            </span>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        <?php endforeach; ?>
    </div>

    <!-- İlçe sonuç bulunamadı -->
    <div id="ily-ilce-sonuc-yok" class="text-center py-5 d-none">
        <i class="fa-solid fa-search text-muted mb-3" style="font-size:2.5rem;opacity:0.3;"></i>
        <p class="text-muted mb-0">Aramanıza uygun ilçe bulunamadı.</p>
    </div>

    <script>
    (function() {
        'use strict';
        var aramaInput = document.getElementById('ily-ilce-arama');
        var kartlar    = document.querySelectorAll('.ily-ilce-karti');
        var sonucYok   = document.getElementById('ily-ilce-sonuc-yok');

        if (!aramaInput || kartlar.length === 0) return;

        aramaInput.addEventListener('input', function() {
            var filtre     = this.value.toLowerCase().trim();
            var gorunenSay = 0;

            kartlar.forEach(function(kart) {
                var ilceAdi = kart.getAttribute('data-ilce') || '';
                var eslesme = filtre === '' || ilceAdi.indexOf(filtre) !== -1;
                kart.style.display = eslesme ? '' : 'none';
                if (eslesme) gorunenSay++;
            });

            if (sonucYok) {
                sonucYok.classList.toggle('d-none', gorunenSay > 0);
            }
        });
    })();
    </script>

<?php endif; ?>

</div>

<style>
/* İl kartı hover efekti */
.ily-kart:hover {
    transform: translateY(-3px) !important;
    box-shadow: 0 8px 28px rgba(0, 0, 0, 0.10) !important;
}

/* İlçe kartı hover efekti */
.ily-ilce-karti .card:hover {
    transform: translateY(-2px);
    box-shadow: 0 6px 24px rgba(0, 0, 0, 0.08);
}
</style>
