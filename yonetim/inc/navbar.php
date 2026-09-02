<nav class="navbar navbar-expand-lg navbar-trabzon mb-4 shadow-sm">
  <div class="container-fluid px-4">
    <a class="navbar-brand d-flex align-items-center fw-bold" href="/yonetim/">
      <img src="assets/logo.webp" alt="Logo" width="45" height="45" class="d-inline-block align-text-top me-2">
      T.K.Ç.D. Panel
    </a>
    
    <button class="navbar-toggler text-white" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav" aria-controls="navbarNav" aria-expanded="false" aria-label="Toggle navigation">
      <span class="navbar-toggler-icon" style="filter: invert(1);"></span>
    </button>
    
    <div class="collapse navbar-collapse" id="navbarNav">
      <ul class="navbar-nav me-auto mb-2 mb-lg-0 gap-1">
        <li class="nav-item">
          <a class="nav-link" href="/yonetim/">
            <i class="fa-solid fa-chart-pie me-1"></i> Dashboard
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=uyeler">
            <i class="fa-solid fa-users me-1"></i> Üye Listesi & Temsilciler
          </a>
        </li>
        <?php if (!$is_kisitli_rol): ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=bekleyen-uyeler">
            <i class="fa-solid fa-user-clock me-1"></i> Bekleyen Başvurular
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=uye-ekle">
            <i class="fa-solid fa-user-plus me-1"></i> Yeni Üye Ekle
          </a>
        </li>
        <?php endif; ?>
        <?php if ($is_admin || $is_gelistirici): ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=hesap-yonetimi">
            <i class="fa-solid fa-users-gear me-1"></i> Hesap Yönetimi
          </a>
        </li>
        <?php endif; ?>
        <?php if ($is_gelistirici): ?>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=loglar" style="color:#00c9a7 !important;">
            <i class="fa-solid fa-terminal me-1"></i> Sistem Logları
          </a>
        </li>
        <li class="nav-item">
          <a class="nav-link" href="index.php?sayfa=kurum-birlestir" style="color:#00c9a7 !important;">
            <i class="fa-solid fa-code-merge me-1"></i> Kurum Birleştir
          </a>
        </li>
        <?php endif; ?>
      </ul>
      
      <div class="d-flex align-items-center gap-3">
        <span class="text-white">
          <i class="fa-solid fa-user-shield me-1"></i> 
          Hoş geldin, <strong><?= htmlspecialchars($_SESSION['kullanici_adi']); ?></strong>
          <?php
          $rol_etiketleri = [
              'admin'           => ['Tam Yetkili', 'success'],
              'yonetim'         => ['Yönetim', 'primary'],
              'gelistirici'     => ['Geliştirici', 'info'],
              'il_baskani'      => ['İl Başkanı', 'success'],
              'ilce_baskani'    => ['İlçe Başkanı', 'purple'],
              'kurum_temsilcisi' => ['Kurum Temsilcisi', 'warning'],
          ];
          $etiket = $rol_etiketleri[$kullanici_rolu] ?? ['Bilinmeyen', 'secondary'];
          $sorumluluk = '';
          if ($is_il_baskani && !empty($_SESSION['sorumlu_il'])) {
              $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_il']);
          } elseif ($is_ilce_baskani && !empty($_SESSION['sorumlu_ilce'])) {
              $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_ilce']);
          } elseif ($is_kurum_temsilcisi && !empty($_SESSION['sorumlu_kurum'])) {
              $sorumluluk = ' — ' . htmlspecialchars($_SESSION['sorumlu_kurum']);
          }
          ?>
          <span class="badge bg-<?= $etiket[1]; ?> ms-1" <?= $etiket[1] === 'purple' ? 'style="background-color: #6a1b9a !important;"' : ''; ?>><?= $etiket[0] . $sorumluluk; ?></span>
        </span>
        <a href="/yonetim/?islem=cikis" class="btn btn-outline-light btn-sm fw-bold px-3">
          <i class="fa-solid fa-right-from-bracket me-1"></i> Çıkış
        </a>
      </div>
    </div>
  </div>
</nav>
<div class="container-fluid px-4">