<?php
/**
 * Sol dikey menü — Tasarım 2 (koyu lacivert, ikonlu).
 *
 * Roller:
 *   - admin, yonetim, gelistirici → Tam menü
 *   - il_baskani, ilce_baskani, kurum_temsilcisi, kadin_kollari_baskani → Kısıtlı menü
 *
 * Yeni menü başlıkları disabled (görünür, tıklanamaz).
 *
 * Değişkenler navbar.php'den / index.php'den geliyor:
 * @var bool $is_admin
 * @var bool $is_yonetim
 * @var bool $is_gelistirici
 * @var bool $is_kisitli_rol
 * @var bool $is_kadin_kollari
 * @var string $kullanici_rolu
 * @var string $sayfa  Aktif sayfa slug'ı
 * @var int|null $bekleyen_badge  Bekleyen başvuru sayısı (opsiyonel)
 */

// Bekleyen başvuru sayısını hesapla (badge için)
$bekleyen_badge = 0;
if (!$is_kisitli_rol) {
    try {
        $badge_sorgu = $db_baglanti->query("SELECT COUNT(*) FROM dernek_uyeler WHERE onay_durumu = 'bekleyen'");
        $bekleyen_badge = (int) $badge_sorgu->fetchColumn();
    } catch (\PDOException $e) {
        $bekleyen_badge = 0;
    }
}
?>
<aside class="panel-sidebar" id="panelSidebar">

    <!-- ── MARKA ──────────────────────────────────────────────── -->
    <a class="sb-brand" href="/yonetim/">
        <img class="sb-brand__logo" src="assets/logo.webp"
             alt="T.K.Ç.D. Logo" width="42" height="42">
        <span class="sb-brand__text">
            <span class="sb-brand__name">T.K.Ç.D.</span>
            <span class="sb-brand__sub">Trabzonlu Kamu Çalışanları Derneği</span>
        </span>
    </a>

    <!-- ── ANA MENÜ ───────────────────────────────────────────── -->
    <nav class="sb-nav" aria-label="Ana menü">

        <!-- Ana Sayfa -->
        <div class="sb-section">
            <a class="sb-link <?= $sayfa === 'dashboard' ? 'sb-link--active' : '' ?>" href="/yonetim/">
                <i class="sb-link__icon fa-solid fa-house"></i>
                <span>Dashboard</span>
            </a>
        </div>

        <!-- Yönetim -->
        <div class="sb-section">
            <div class="sb-section__label">Yönetim</div>

            <a class="sb-link <?= $sayfa === 'uyeler' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=uyeler">
                <i class="sb-link__icon fa-solid fa-users"></i>
                <span>Üyeler</span>
            </a>

            <?php if (!$is_kisitli_rol || ($_SESSION['kullanici_adi'] ?? '') === 'kk_by'): ?>
            <a class="sb-link <?= $sayfa === 'bekleyen-uyeler' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=bekleyen-uyeler">
                <i class="sb-link__icon fa-solid fa-user-clock"></i>
                <span>Başvurular</span>
                <?php if ($bekleyen_badge > 0): ?>
                <span class="sb-link__badge"><?= $bekleyen_badge ?></span>
                <?php endif; ?>
            </a>
            <?php endif; ?>

            <?php if (!$is_kisitli_rol): ?>
            <a class="sb-link <?= $sayfa === 'teskilatlanma' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=teskilatlanma">
                <i class="sb-link__icon fa-solid fa-sitemap"></i>
                <span>Teşkilatlanma</span>
            </a>
            <?php else: ?>
            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-sitemap"></i>
                <span>Teşkilatlanma</span>
            </a>
            <?php endif; ?>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-building-columns"></i>
                <span>Kurullar</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-id-card-clip"></i>
                <span>Temsilciler</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>
        </div>

        <!-- İçerik -->
        <div class="sb-section">
            <div class="sb-section__label">İçerik</div>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-calendar-days"></i>
                <span>Etkinlikler</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>

            <?php if ($is_gelistirici): ?>
            <a class="sb-link <?= $sayfa === 'duyurular' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=duyurular">
                <i class="sb-link__icon fa-solid fa-bullhorn"></i>
                <span>Duyurular</span>
            </a>
            <?php else: ?>
            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-bullhorn"></i>
                <span>Duyurular</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>
            <?php endif; ?>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-file-lines"></i>
                <span>Dokümanlar</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>
        </div>

        <!-- Finans & Raporlar -->
        <div class="sb-section">
            <div class="sb-section__label">Finans & Raporlar</div>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-coins"></i>
                <span>Finans / Aidat</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-map-location-dot"></i>
                <span>Bölgeler</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-chart-bar"></i>
                <span>Raporlar</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>
        </div>

        <!-- Sistem -->
        <div class="sb-section">
            <div class="sb-section__label">Sistem</div>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-envelope"></i>
                <span>Mesajlar</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>

            <?php if (!$is_kisitli_rol): ?>
            <a class="sb-link <?= $sayfa === 'uye-ekle' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=uye-ekle">
                <i class="sb-link__icon fa-solid fa-user-plus"></i>
                <span>Yeni Üye Ekle</span>
            </a>

            <a class="sb-link <?= $sayfa === 'son-onaylananlar' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=son-onaylananlar">
                <i class="sb-link__icon fa-solid fa-user-check"></i>
                <span>Son Onaylananlar</span>
            </a>
            <?php endif; ?>

            <?php if ($is_admin || $is_gelistirici): ?>
            <a class="sb-link <?= $sayfa === 'hesap-yonetimi' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=hesap-yonetimi">
                <i class="sb-link__icon fa-solid fa-users-gear"></i>
                <span>Hesap Yönetimi</span>
            </a>
            <?php endif; ?>

            <a class="sb-link sb-link--disabled" href="#" aria-disabled="true" tabindex="-1">
                <i class="sb-link__icon fa-solid fa-gear"></i>
                <span>Ayarlar</span>
                <span class="sb-link__badge sb-link__badge--soon">Yakında</span>
            </a>
        </div>

        <!-- Geliştirici Araçları -->
        <?php if ($is_gelistirici): ?>
        <div class="sb-section">
            <div class="sb-section__label" style="color: #34d399;">Geliştirici Araçları</div>

            <a class="sb-link sb-link--dev <?= $sayfa === 'loglar' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=loglar">
                <i class="sb-link__icon fa-solid fa-terminal"></i>
                <span>Sistem Logları</span>
            </a>

            <a class="sb-link sb-link--dev <?= $sayfa === 'kurum-birlestir' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=kurum-birlestir">
                <i class="sb-link__icon fa-solid fa-code-merge"></i>
                <span>Kurum Birleştir</span>
            </a>

            <a class="sb-link sb-link--dev <?= $sayfa === 'yas-filtresi' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=yas-filtresi">
                <i class="sb-link__icon fa-solid fa-filter"></i>
                <span>Yaş Filtresi</span>
            </a>

            <a class="sb-link sb-link--dev <?= $sayfa === 'cinsiyet-ata' ? 'sb-link--active' : '' ?>" href="index.php?sayfa=cinsiyet-ata">
                <i class="sb-link__icon fa-solid fa-venus-mars"></i>
                <span>Cinsiyet Ata</span>
            </a>
        </div>
        <?php endif; ?>

    </nav>

    <!-- ── ALT BİLGİ ──────────────────────────────────────────── -->
    <div class="sb-footer">
        <div class="sb-footer__text">
            Kökümüz Trabzon,<br>Gücümüz Birliktelik
        </div>
    </div>

</aside>

<!-- Mobil overlay -->
<div class="sb-overlay" id="sbOverlay"></div>
