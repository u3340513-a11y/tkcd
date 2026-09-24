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
<div id="turkiye-popup" class="tkp-overlay" role="dialog" aria-modal="true" aria-labelledby="tkp-baslik" hidden>
    <div class="tkp-kutu">
        <button class="tkp-kapat" id="tkp-kapat-btn" aria-label="Kapat">&#x2715;</button>
        <img
            src="<?= $view->e($view->asset('assets/img/turkiye.jpeg')) ?>"
            alt="Türkiye"
            id="tkp-baslik"
            class="tkp-gorsel"
            loading="eager"
        >
    </div>
</div>

<style>
.tkp-overlay {
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,.72);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999;
    backdrop-filter: blur(3px);
    animation: tkpFadeIn .3s ease;
}
.tkp-overlay[hidden] { display: none !important; }
.tkp-kutu {
    position: relative;
    max-width: min(92vw, 720px);
    border-radius: 12px;
    overflow: hidden;
    box-shadow: 0 24px 60px rgba(0,0,0,.55);
    animation: tkpSlideUp .35s ease;
}
.tkp-gorsel {
    display: block;
    width: 100%;
    height: auto;
    max-height: 85vh;
    object-fit: contain;
}
.tkp-kapat {
    position: absolute;
    top: 10px;
    right: 12px;
    background: rgba(0,0,0,.55);
    border: none;
    color: #fff;
    font-size: 1.1rem;
    width: 32px;
    height: 32px;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: background .2s;
    z-index: 1;
}
.tkp-kapat:hover { background: rgba(0,0,0,.85); }
@keyframes tkpFadeIn  { from { opacity: 0; } to { opacity: 1; } }
@keyframes tkpSlideUp { from { transform: translateY(30px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>

<script>
(function () {
    'use strict';
    var KEY    = 'tkp_shown_v1';
    var popup  = document.getElementById('turkiye-popup');
    var kapatBtn = document.getElementById('tkp-kapat-btn');
    if (!popup) return;

    if (!sessionStorage.getItem(KEY)) {
        popup.removeAttribute('hidden');
        sessionStorage.setItem(KEY, '1');
    }

    function kapat() { popup.setAttribute('hidden', ''); }

    kapatBtn.addEventListener('click', kapat);
    popup.addEventListener('click', function (e) {
        if (e.target === popup) kapat();
    });
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kapat();
    });
})();
</script>

</body>
</html>
