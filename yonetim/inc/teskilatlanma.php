<?php
/**
 * Teşkilatlanma sayfası — Yönetim Kurulu hiyerarşi görünümü.
 *
 * Board verisi resources/data/board.php dosyasından okunur.
 * Fotoğraflar /assets/img/ dizinindedir.
 * Sadece admin, yonetim ve gelistirici rolü erişebilir (index.php'de kontrol edilir).
 */

declare(strict_types=1);

$board_gruplar = require dirname(__DIR__, 2) . '/resources/data/board.php';

// Fotoğraf URL'si oluşturucu
$foto_url = static function (string $fotograf): string {
    $img_dir = dirname(__DIR__, 2) . '/public/assets/img/' . $fotograf;
    if ($fotograf === 'placeholder-kisi.svg' || !file_exists($img_dir)) {
        return '/assets/img/placeholder-kisi.svg';
    }
    return '/assets/img/' . $fotograf;
};

// Baş harfleri renk için hash
$avatar_renk = static function (string $ad): string {
    $renkler = ['#c62828','#1565c0','#2e7d32','#6a1b9a','#e65100','#00838f','#37474f','#ad1457'];
    return $renkler[abs(crc32($ad)) % count($renkler)];
};
?>
<div class="container-fluid py-4 px-md-4">

    <!-- ── BAŞLIK ── -->
    <div class="row mb-5">
        <div class="col-12">
            <div class="d-flex align-items-center gap-3 mb-1">
                <div class="rounded-3 d-flex align-items-center justify-content-center"
                     style="width:52px;height:52px;background:linear-gradient(135deg,#c62828,#8b0000);">
                    <i class="fa-solid fa-sitemap text-white fa-lg"></i>
                </div>
                <div>
                    <h1 class="fw-bold text-dark mb-0" style="font-size:1.6rem;">Teşkilatlanma</h1>
                    <p class="text-muted mb-0 small">Yönetim Kurulu hiyerarşisi ve görev dağılımı</p>
                </div>
            </div>
        </div>
    </div>

    <!-- ── HİYERARŞİ GRUPLARI ── -->
    <?php foreach ($board_gruplar as $grupIndex => $grup): ?>
    <?php
        $uyeler    = $grup['uyeler'];
        $baslik    = $grup['baslik'] ?? null;
        $uyeSayisi = count($uyeler);
        // Kolon belirle
        if ($uyeSayisi === 1)      { $col = 'col-md-4 mx-auto'; }
        elseif ($uyeSayisi === 2)  { $col = 'col-md-5'; }
        elseif ($uyeSayisi >= 4)   { $col = 'col-sm-6 col-lg-3'; }
        else                       { $col = 'col-sm-6 col-md-4'; }
    ?>

    <!-- Bölüm ayırıcı ok (ilk bölüm hariç) -->
    <?php if ($grupIndex > 0): ?>
    <div class="text-center my-3">
        <i class="fa-solid fa-chevron-down" style="color:#dee2e6;font-size:1.4rem;"></i>
    </div>
    <?php endif; ?>

    <div class="mb-2">
        <?php if ($baslik): ?>
        <div class="d-flex align-items-center gap-2 mb-3 px-1">
            <div style="width:4px;height:22px;background:#c62828;border-radius:2px;flex-shrink:0;"></div>
            <h2 class="fw-bold mb-0" style="font-size:1rem;color:#1e293b;letter-spacing:0.01em;">
                <?= htmlspecialchars($baslik); ?>
            </h2>
        </div>
        <?php endif; ?>

        <div class="row g-3 <?= $uyeSayisi <= 2 ? 'justify-content-center' : '' ?>">
            <?php foreach ($uyeler as $uye): ?>
            <div class="<?= $col ?>">
                <?php
                    $fotoUrl    = $foto_url($uye['fotograf']);
                    $isPlaceholder = ($uye['fotograf'] === 'placeholder-kisi.svg' || !file_exists(dirname(__DIR__, 2) . '/public/assets/img/' . $uye['fotograf']));
                    $renk       = $avatar_renk($uye['ad']);
                    $initials   = implode('', array_map(
                        fn($p) => mb_substr($p, 0, 1, 'UTF-8'),
                        array_slice(explode(' ', $uye['ad']), 0, 2)
                    ));
                    $yonetim_db_sql  = "SELECT id, temsilci_turu, ek_gorev FROM dernek_uyeler WHERE adi_soyadi = ? AND onay_durumu = 'onayli' LIMIT 1";
                    try {
                        $stmt = $db_baglanti->prepare($yonetim_db_sql);
                        $stmt->execute([$uye['ad']]);
                        $db_match = $stmt->fetch(PDO::FETCH_ASSOC);
                    } catch (\PDOException $e) {
                        $db_match = false;
                    }
                ?>
                <div class="card border-0 shadow-sm rounded-4 h-100 overflow-hidden"
                     style="transition:transform 0.2s,box-shadow 0.2s;"
                     onmouseover="this.style.transform='translateY(-4px)';this.style.boxShadow='0 12px 32px rgba(0,0,0,0.12)'"
                     onmouseout="this.style.transform='';this.style.boxShadow=''">

                    <!-- Üst Renk Şeridi -->
                    <div style="height:6px;background:linear-gradient(90deg,<?= $renk ?>,<?= $renk ?>aa);"></div>

                    <div class="card-body p-3 d-flex align-items-start gap-3">
                        <!-- Fotoğraf / Avatar -->
                        <?php if (!$isPlaceholder): ?>
                        <img src="<?= htmlspecialchars($fotoUrl) ?>"
                             alt="<?= htmlspecialchars($uye['ad']) ?>"
                             class="rounded-3 flex-shrink-0 object-fit-cover"
                             style="width:64px;height:64px;object-fit:cover;border:2px solid <?= $renk ?>22;"
                             loading="lazy">
                        <?php else: ?>
                        <div class="rounded-3 d-flex align-items-center justify-content-center fw-bold flex-shrink-0"
                             style="width:64px;height:64px;background:<?= $renk ?>18;color:<?= $renk ?>;font-size:1.3rem;letter-spacing:-0.02em;border:2px solid <?= $renk ?>22;">
                            <?= htmlspecialchars($initials) ?>
                        </div>
                        <?php endif; ?>

                        <!-- Bilgi -->
                        <div class="flex-grow-1 min-width-0">
                            <div class="fw-bold text-dark" style="font-size:0.92rem;line-height:1.3;">
                                <?= htmlspecialchars($uye['ad']) ?>
                            </div>
                            <div class="mt-1">
                                <span class="badge rounded-pill px-2 py-1"
                                      style="background:<?= $renk ?>18;color:<?= $renk ?>;font-size:0.72rem;font-weight:600;">
                                    <?= htmlspecialchars($uye['unvan']) ?>
                                </span>
                            </div>

                            <?php if (!empty($uye['biyografi'])): ?>
                            <div class="text-muted mt-1" style="font-size:0.78rem;">
                                <?= htmlspecialchars($uye['biyografi']) ?>
                            </div>
                            <?php endif; ?>

                            <?php if (!empty($uye['gorevler'])): ?>
                            <div class="mt-1 d-flex flex-wrap gap-1">
                                <?php foreach ($uye['gorevler'] as $gorev): ?>
                                <span class="badge bg-light text-secondary border" style="font-size:0.68rem;">
                                    <?= htmlspecialchars($gorev) ?>
                                </span>
                                <?php endforeach; ?>
                            </div>
                            <?php endif; ?>

                            <!-- DB Eşleşmesi -->
                            <?php if ($db_match): ?>
                            <div class="mt-2">
                                <a href="index.php?sayfa=uye-detay&id=<?= $db_match['id'] ?>"
                                   class="btn btn-outline-secondary btn-sm py-0 px-2 rounded-pill"
                                   style="font-size:0.7rem;">
                                    <i class="fa-solid fa-id-card me-1"></i>Üye Kartı
                                </a>
                            </div>
                            <?php endif; ?>
                        </div>
                    </div>

                    <?php if (!empty($uye['sosyal'])): ?>
                    <div class="card-footer bg-transparent border-top-0 pt-0 pb-2 px-3">
                        <div class="d-flex gap-2">
                            <?php foreach ($uye['sosyal'] as $platform => $url): ?>
                            <?php
                                $sosyalIkon = match($platform) {
                                    'facebook'  => 'fa-brands fa-facebook-f',
                                    'instagram' => 'fa-brands fa-instagram',
                                    'linkedin'  => 'fa-brands fa-linkedin-in',
                                    'x'         => 'fa-brands fa-x-twitter',
                                    'youtube'   => 'fa-brands fa-youtube',
                                    default     => 'fa-solid fa-link',
                                };
                            ?>
                            <a href="<?= htmlspecialchars($url) ?>" target="_blank" rel="noopener noreferrer"
                               class="btn btn-sm rounded-circle d-flex align-items-center justify-content-center"
                               style="width:28px;height:28px;background:#f1f5f9;color:#64748b;font-size:0.7rem;padding:0;"
                               title="<?= htmlspecialchars($platform) ?>">
                                <i class="<?= $sosyalIkon ?>"></i>
                            </a>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    </div>
    <?php endforeach; ?>

</div>
