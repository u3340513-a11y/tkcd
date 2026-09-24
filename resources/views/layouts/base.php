<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Ana yerleşim şablonu.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var string $content Sayfa şablonundan gelen işlenmiş içerik
 * @var array<string, mixed> $site
 * @var string $language
 * @var list<string> $styles  Sayfaya özel ek stil dosyaları
 * @var list<string> $scripts Sayfaya özel ek JavaScript dosyaları
 */

$styles      = $styles      ?? [];
$scripts     = $scripts     ?? [];
$headScripts = $headScripts ?? [];
?>
<!DOCTYPE html>
<html lang="<?= $view->e($language) ?>" prefix="og: https://ogp.me/ns#">
<head>
<?= $view->partial('partials/head', ['seo' => $seo, 'styles' => $styles]) ?>
<?php foreach ($headScripts as $hs): ?>
    <script src="<?= $view->e($hs) ?>" async defer></script>
<?php endforeach; ?>
</head>
<body>
    <a class="atlama-baglantisi" href="#ana-icerik">İçeriğe geç</a>

<?= $view->partial('partials/header') ?>

    <main id="ana-icerik">
<?= $content ?>
    </main>

<?= $view->partial('partials/footer') ?>

    <button type="button" class="yukari-cik" data-yukari-cik>
        <span class="gorsel-gizli">Sayfanın başına dön</span>
        <?= $view->icon('arrow-up') ?>
    </button>

    <script src="<?= $view->e($view->asset('assets/js/app.js')) ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= $view->e($view->asset('assets/js/' . $script)) ?>" defer></script>
<?php endforeach; ?>

<!-- ── Türkiye Görseli Popup ─────────────────────────────────── -->
<div id="turkiye-popup" role="dialog" aria-modal="true" aria-label="Türkiye" style="
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.78);
    align-items:center;justify-content:center;z-index:99999;
    padding:16px;box-sizing:border-box;
">
    <div style="position:relative;width:100%;max-width:680px;border-radius:14px;overflow:hidden;
                box-shadow:0 24px 60px rgba(0,0,0,.6);">

        <!-- Geri sayım + kapat çubuğu -->
        <div style="position:absolute;top:0;left:0;right:0;display:flex;align-items:center;
                    justify-content:space-between;padding:10px 12px;
                    background:linear-gradient(to bottom,rgba(0,0,0,.65),transparent);z-index:2;">
            <span id="tkp-sayac" style="
                background:rgba(0,0,0,.55);color:#fff;font-size:.85rem;font-weight:700;
                padding:4px 12px;border-radius:20px;letter-spacing:.04em;
            ">5</span>
            <button id="tkp-kapat-btn" aria-label="Kapat" style="
                background:rgba(0,0,0,.55);border:none;color:#fff;
                font-size:1.2rem;width:36px;height:36px;border-radius:50%;cursor:pointer;
                display:flex;align-items:center;justify-content:center;
            ">&#x2715;</button>
        </div>

        <img src="/assets/img/turkiye.jpeg" alt="Türkiye" style="
            display:block;width:100%;height:auto;
            max-height:calc(100svh - 80px);object-fit:contain;vertical-align:top;
        ">
    </div>
</div>

<script>
(function () {
    'use strict';
    var KEY      = 'tkp_shown_v4';
    var popup    = document.getElementById('turkiye-popup');
    var kapatBtn = document.getElementById('tkp-kapat-btn');
    var sayacEl  = document.getElementById('tkp-sayac');
    if (!popup) return;

    function kapat() { popup.style.display = 'none'; clearInterval(timer); }

    var timer;
    if (!sessionStorage.getItem(KEY)) {
        popup.style.display = 'flex';
        sessionStorage.setItem(KEY, '1');

        var kalan = 5;
        timer = setInterval(function () {
            kalan--;
            if (sayacEl) sayacEl.textContent = kalan;
            if (kalan <= 0) kapat();
        }, 1000);
    }

    if (kapatBtn) kapatBtn.addEventListener('click', kapat);
    popup.addEventListener('click', function (e) {
        if (e.target === popup) kapat();
    });
})();
</script>

</body>
</html>

