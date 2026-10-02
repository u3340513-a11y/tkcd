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
 * Eşleşme öncelik sırası:
 *   1. sorumlu_bolge alanı (kişi farklı ilde ikamet etse bile)
 *   2. ikamet_ilcesi veya trabzon_ilcesi (kişi o ilde ikamet ediyorsa)
 *
 * Neden sorumlu_bolge öncelikli: İlçe başkanları kendi ilçelerinde
 * ikamet etmeyebilir; yetkili oldukları ilçe sorumlu_bolge alanına yazılır.
 *
 * @param PDO    $db    Veritabanı bağlantısı
 * @param string $il    İl adı
 * @param string $ilce  İlçe adı
 * @return array|null Başkan bilgisi veya null
 */
function ilce_baskanini_bul(PDO $db, string $il, string $ilce): ?array
{
    $sql = "SELECT id, adi_soyadi, telefon, ikamet_ili, ikamet_ilcesi,
                   temsilci_turu, ek_gorev, ek_roller, kurum, sorumlu_bolge
              FROM dernek_uyeler
             WHERE onay_durumu = 'onayli'
               AND (
                   temsilci_turu IN ('İlçe Başkanı', 'İlçe Temsilcisi')
                   OR ek_gorev IN ('İlçe Başkanı', 'İlçe Temsilcisi')
                   OR JSON_CONTAINS(ek_roller, '\"İlçe Başkanı\"')
               )
               AND (
                   /* sorumlu_bolge — kişi farklı ilde yaşıyor olsa bile */
                   LOWER(TRIM(sorumlu_bolge)) = LOWER(:ilce_sb)
                   /* ikamet ilçesi eşleşmesi — ikamet ili de eşleşmeli */
                   OR (
                       LOWER(TRIM(ikamet_ili)) = LOWER(:il)
                       AND (
                           LOWER(TRIM(ikamet_ilcesi)) = LOWER(:ilce)
                           OR LOWER(TRIM(trabzon_ilcesi)) = LOWER(:ilce2)
                       )
                   )
               )
             ORDER BY
               CASE WHEN LOWER(TRIM(sorumlu_bolge)) = LOWER(:ilce_sb2) THEN 0 ELSE 1 END,
               CASE
                 WHEN temsilci_turu = 'İlçe Başkanı' THEN 1
                 WHEN ek_gorev = 'İlçe Başkanı' THEN 2
                 WHEN temsilci_turu = 'İlçe Temsilcisi' THEN 3
                 ELSE 4
               END
             LIMIT 1";

    $stmt = $db->prepare($sql);
    $stmt->execute([
        ':il'       => $il,
        ':ilce'     => $ilce,
        ':ilce2'    => $ilce,
        ':ilce_sb'  => $ilce,
        ':ilce_sb2' => $ilce,
    ]);
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
            <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden ily-ilce-kart-ic"
                 style="transition:transform 0.2s,box-shadow 0.2s;<?= $ilce_uye_s > 0 ? 'cursor:pointer;' : '' ?>"
                 <?php if ($ilce_uye_s > 0): ?>
                 data-il="<?= htmlspecialchars($secili_il, ENT_QUOTES) ?>"
                 data-ilce="<?= htmlspecialchars($ilce_adi, ENT_QUOTES) ?>"
                 data-uye="<?= $ilce_uye_s ?>"
                 role="button"
                 tabindex="0"
                 title="<?= htmlspecialchars($ilce_adi) ?> ilçesi üyelerini gör"
                 <?php endif; ?>>
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
                                <i class="fa-solid fa-users me-1" style="font-size:0.6rem;"></i>
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
                               title="Üye Kartı"
                               onclick="event.stopPropagation();">
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

                    <?php if ($ilce_uye_s > 0): ?>
                    <div class="mt-2 pt-2 border-top d-flex align-items-center gap-1"
                         style="font-size:0.7rem;color:#94a3b8;">
                        <i class="fa-solid fa-eye" style="font-size:0.65rem;"></i>
                        Üyeleri görüntülemek için tıklayın
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

    <!-- ── İLÇE ÜYE LİSTESİ MODAL ──────────────────────────────────── -->
    <div class="modal fade" id="ilyUyeModal" tabindex="-1"
         aria-labelledby="ilyUyeModalLabel" aria-modal="true" role="dialog">
        <div class="modal-dialog modal-lg modal-dialog-scrollable modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg rounded-4 overflow-hidden">

                <!-- Başlık -->
                <div class="modal-header border-0 pb-0"
                     style="background:linear-gradient(135deg,#6a1b9a,#9c27b0);padding:1.2rem 1.5rem;">
                    <div class="d-flex align-items-center gap-2 flex-grow-1">
                        <i class="fa-solid fa-users text-white" style="font-size:1.1rem;"></i>
                        <h5 class="modal-title text-white fw-bold mb-0" id="ilyUyeModalLabel">
                            <span id="ilyModalIlce">İlçe</span> Üyeleri
                        </h5>
                        <span id="ilyModalBadge"
                              class="badge rounded-pill ms-2"
                              style="background:rgba(255,255,255,0.2);color:#fff;font-size:0.75rem;">
                        </span>
                    </div>
                    <button type="button" class="btn-close btn-close-white ms-2"
                            data-bs-dismiss="modal" aria-label="Kapat"></button>
                </div>

                <!-- Gövde -->
                <div class="modal-body p-0">

                    <!-- Yükleniyor -->
                    <div id="ilyModalYukleniyor" class="text-center py-5">
                        <div class="spinner-border text-purple" role="status"
                             style="color:#6a1b9a;width:2.5rem;height:2.5rem;">
                            <span class="visually-hidden">Yükleniyor…</span>
                        </div>
                        <div class="text-muted mt-2" style="font-size:0.85rem;">Üyeler getiriliyor…</div>
                    </div>

                    <!-- Hata -->
                    <div id="ilyModalHata" class="d-none text-center py-5">
                        <i class="fa-solid fa-circle-exclamation text-danger mb-2" style="font-size:2rem;"></i>
                        <p class="text-danger mb-0" style="font-size:0.85rem;">Veriler yüklenemedi.</p>
                    </div>

                    <!-- Boş -->
                    <div id="ilyModalBos" class="d-none text-center py-5">
                        <i class="fa-solid fa-user-slash text-muted mb-2" style="font-size:2rem;opacity:0.4;"></i>
                        <p class="text-muted mb-0" style="font-size:0.85rem;">Bu ilçede kayıtlı üye bulunamadı.</p>
                    </div>

                    <!-- Üye tablosu -->
                    <div id="ilyModalTablo" class="d-none">
                        <!-- İçi arama -->
                        <div class="px-3 pt-3 pb-2">
                            <input type="text" id="ilyModalArama"
                                   class="form-control form-control-sm rounded-pill"
                                   placeholder="Ad, kurum veya unvan ile ara…"
                                   autocomplete="off">
                        </div>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0" id="ilyModalUyeTable">
                                <thead style="background:#f8fafc;">
                                    <tr>
                                        <th class="ps-3 py-2" style="font-size:0.75rem;font-weight:600;color:#64748b;white-space:nowrap;">#</th>
                                        <th class="py-2" style="font-size:0.75rem;font-weight:600;color:#64748b;">Ad Soyad</th>
                                        <th class="py-2" style="font-size:0.75rem;font-weight:600;color:#64748b;">Kurum</th>
                                        <th class="py-2" style="font-size:0.75rem;font-weight:600;color:#64748b;">Ünvan</th>
                                        <th class="py-2" style="font-size:0.75rem;font-weight:600;color:#64748b;">Statü</th>
                                        <th class="pe-3 py-2"></th>
                                    </tr>
                                </thead>
                                <tbody id="ilyModalTbody"></tbody>
                            </table>
                        </div>
                    </div>
                </div>

            </div>
        </div>
    </div>
    <!-- ─────────────────────────────────────────────────────────────── -->

    <script>
    (function() {
        'use strict';

        /* ── Arama ── */
        var aramaInput = document.getElementById('ily-ilce-arama');
        var kartlar    = document.querySelectorAll('.ily-ilce-karti');
        var sonucYok   = document.getElementById('ily-ilce-sonuc-yok');

        if (aramaInput && kartlar.length > 0) {
            aramaInput.addEventListener('input', function() {
                var filtre     = this.value.toLowerCase().trim();
                var gorunenSay = 0;
                kartlar.forEach(function(kart) {
                    var ilceAdi = kart.getAttribute('data-ilce') || '';
                    var eslesme = filtre === '' || ilceAdi.indexOf(filtre) !== -1;
                    kart.style.display = eslesme ? '' : 'none';
                    if (eslesme) gorunenSay++;
                });
                if (sonucYok) sonucYok.classList.toggle('d-none', gorunenSay > 0);
            });
        }

        /* ── Modal ── */
        var modal        = document.getElementById('ilyUyeModal');
        var bsModal      = modal ? new bootstrap.Modal(modal) : null;
        var modalIlce    = document.getElementById('ilyModalIlce');
        var modalBadge   = document.getElementById('ilyModalBadge');
        var modalYuk     = document.getElementById('ilyModalYukleniyor');
        var modalHata    = document.getElementById('ilyModalHata');
        var modalBos     = document.getElementById('ilyModalBos');
        var modalTablo   = document.getElementById('ilyModalTablo');
        var modalTbody   = document.getElementById('ilyModalTbody');
        var modalArama   = document.getElementById('ilyModalArama');

        if (!bsModal) return;

        /**
         * Belirtilen bölümü göster, diğerlerini gizle.
         * @param {'yukleniyor'|'hata'|'bos'|'tablo'} durum
         */
        function goster(durum) {
            modalYuk.classList.toggle('d-none',   durum !== 'yukleniyor');
            modalHata.classList.toggle('d-none',  durum !== 'hata');
            modalBos.classList.toggle('d-none',   durum !== 'bos');
            modalTablo.classList.toggle('d-none', durum !== 'tablo');
        }

        /**
         * Baş harfleri hesaplar.
         * @param {string} ad
         * @returns {string}
         */
        function basHarf(ad) {
            var parcalar = ad.trim().split(/\s+/).slice(0, 2);
            return parcalar.map(function(p) { return p.charAt(0).toUpperCase(); }).join('');
        }

        /**
         * Belirleyici bir avatar rengi döndürür.
         * @param {string} ad
         * @returns {string}
         */
        function avatarRenk(ad) {
            var renkler = ['#c62828','#1565c0','#2e7d32','#6a1b9a','#e65100','#00838f','#37474f','#ad1457'];
            var hash = 0;
            for (var i = 0; i < ad.length; i++) { hash = ad.charCodeAt(i) + ((hash << 5) - hash); }
            return renkler[Math.abs(hash) % renkler.length];
        }

        /**
         * Tüm tıklanabilir ilçe kartlarına event listener ekler.
         */
        document.querySelectorAll('.ily-ilce-kart-ic[data-il]').forEach(function(kart) {
            kart.addEventListener('click', function() {
                var il   = this.getAttribute('data-il')   || '';
                var ilce = this.getAttribute('data-ilce') || '';
                var uye  = this.getAttribute('data-uye')  || '0';

                // Modal başlığı güncelle
                modalIlce.textContent = ilce;
                modalBadge.textContent = uye + ' üye';
                if (modalArama) modalArama.value = '';

                goster('yukleniyor');
                bsModal.show();

                // AJAX isteği
                var xhr = new XMLHttpRequest();
                var url = '/yonetim/api/ilce-uyeler.php?il=' + encodeURIComponent(il)
                        + '&ilce=' + encodeURIComponent(ilce);
                xhr.open('GET', url, true);
                xhr.setRequestHeader('X-Requested-With', 'XMLHttpRequest');

                xhr.onload = function() {
                    if (xhr.status !== 200) { goster('hata'); return; }
                    var veri;
                    try { veri = JSON.parse(xhr.responseText); } catch(e) { goster('hata'); return; }
                    if (veri.hata) { goster('hata'); return; }
                    if (!veri.uyeler || veri.uyeler.length === 0) { goster('bos'); return; }

                    // Tabloyu doldur
                    var satirlar = veri.uyeler.map(function(u, i) {
                        var renk    = avatarRenk(u.ad);
                        var harfler = basHarf(u.ad);
                        var statüHtml = u['statü']
                            ? '<span class="badge rounded-pill px-2 py-1" style="background:rgba(106,27,154,0.1);color:#6a1b9a;font-size:0.68rem;">'
                              + u['statü'] + '</span>'
                            : '<span class="text-muted" style="font-size:0.75rem;">—</span>';
                        return '<tr data-ara="' + (u.ad + ' ' + u.kurum + ' ' + u.unvan).toLowerCase() + '">' +
                            '<td class="ps-3" style="font-size:0.78rem;color:#94a3b8;">' + (i + 1) + '</td>' +
                            '<td>' +
                              '<div class="d-flex align-items-center gap-2">' +
                                '<div class="rounded-circle d-flex align-items-center justify-content-center fw-bold flex-shrink-0" '
                                  + 'style="width:32px;height:32px;background:' + renk + '18;color:' + renk + ';font-size:0.68rem;">' +
                                  harfler +
                                '</div>' +
                                '<span class="fw-semibold" style="font-size:0.82rem;">' + u.ad + '</span>' +
                              '</div>' +
                            '</td>' +
                            '<td style="font-size:0.78rem;color:#475569;">' + (u.kurum || '<span class="text-muted">—</span>') + '</td>' +
                            '<td style="font-size:0.78rem;color:#475569;">' + (u.unvan || '<span class="text-muted">—</span>') + '</td>' +
                            '<td>' + statüHtml + '</td>' +
                            '<td class="pe-3">' +
                              '<a href="index.php?sayfa=uye-detay&id=' + u.id + '" '
                                + 'class="btn btn-sm rounded-pill px-2 py-0" '
                                + 'style="font-size:0.7rem;background:rgba(21,101,192,0.08);color:#1565c0;" '
                                + 'title="Üye Kartı" target="_blank">' +
                                '<i class="fa-solid fa-arrow-up-right-from-square me-1"></i>Kart' +
                              '</a>' +
                            '</td>' +
                            '</tr>';
                    }).join('');

                    modalTbody.innerHTML = satirlar;
                    goster('tablo');

                    // Modal içi arama
                    if (modalArama) {
                        modalArama.oninput = function() {
                            var f = this.value.toLowerCase().trim();
                            modalTbody.querySelectorAll('tr').forEach(function(tr) {
                                var ara = tr.getAttribute('data-ara') || '';
                                tr.style.display = (f === '' || ara.indexOf(f) !== -1) ? '' : 'none';
                            });
                        };
                    }
                };

                xhr.onerror = function() { goster('hata'); };
                xhr.send();
            });

            // Klavye erişilebilirliği
            kart.addEventListener('keydown', function(e) {
                if (e.key === 'Enter' || e.key === ' ') {
                    e.preventDefault();
                    this.click();
                }
            });
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

/* Tıklanabilir kart vurgusu */
.ily-ilce-kart-ic[data-il]:hover {
    box-shadow: 0 0 0 2px #6a1b9a44 !important;
}

/* Modal başlık gradient */
#ilyUyeModal .modal-header { border-radius: 0; }
</style>
