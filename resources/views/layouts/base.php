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

<!-- ── GS-TS Açılış Popup (Public) ── -->
<!-- Oturumda bir kez: sessionStorage ile JS tarafında kontrol edilir -->
<div id="gsTsPopupOverlay" style="
    display:none;
    position:fixed;inset:0;
    background:rgba(0,0,0,0.65);
    z-index:99999;
    align-items:center;justify-content:center;
    padding:12px;
    backdrop-filter:blur(4px);-webkit-backdrop-filter:blur(4px);
">
    <div id="gsTsPopupBox" style="
        position:relative;
        max-width:720px;width:100%;
        border-radius:16px;overflow:hidden;
        box-shadow:0 28px 70px rgba(0,0,0,0.55);
        animation:gsTsSlideUp 0.4s cubic-bezier(0.34,1.56,0.64,1);
    ">
        <button onclick="kapatGsTsPopup()" style="
            position:absolute;top:12px;right:12px;z-index:10;
            width:36px;height:36px;border-radius:50%;border:none;
            background:rgba(0,0,0,0.55);color:#fff;font-size:17px;
            cursor:pointer;display:flex;align-items:center;justify-content:center;
            transition:background 0.2s,transform 0.2s;
        "
        onmouseover="this.style.background='rgba(0,0,0,0.82)';this.style.transform='scale(1.1)'"
        onmouseout="this.style.background='rgba(0,0,0,0.55)';this.style.transform='scale(1)'"
        aria-label="Kapat">&#x2715;</button>

        <img src="<?= $view->asset('assets/img/gs-ts.jpeg') ?>"
             alt="Galatasaray - Trabzonspor"
             style="display:block;width:100%;height:auto;max-height:78vh;object-fit:contain;background:#0a0a0a;">

        <div style="background:#0a0a0a;padding:9px 16px;display:flex;align-items:center;gap:10px;">
            <span id="gsTsCountdown" style="color:rgba(255,255,255,0.65);font-size:0.78rem;white-space:nowrap;flex-shrink:0;">
                7 saniye içinde kapanıyor
            </span>
            <div style="flex:1;height:3px;background:rgba(255,255,255,0.12);border-radius:2px;overflow:hidden;">
                <div id="gsTsBar" style="height:100%;width:100%;background:linear-gradient(90deg,#e8251e,#ffd700);border-radius:2px;transition:width linear;"></div>
            </div>
            <button onclick="kapatGsTsPopup()" style="
                background:none;border:1px solid rgba(255,255,255,0.28);
                color:rgba(255,255,255,0.65);font-size:0.72rem;
                border-radius:6px;padding:3px 12px;cursor:pointer;
                white-space:nowrap;flex-shrink:0;
            ">Kapat</button>
        </div>
    </div>
</div>
<audio id="gsTsAudio" preload="auto" style="display:none;">
    <source src="<?= $view->asset('assets/video/dalga-dalga.mp3') ?>" type="audio/mpeg">
</audio>

<style>
@keyframes gsTsFadeIn { from{opacity:0} to{opacity:1} }
@keyframes gsTsSlideUp {
    from{transform:translateY(32px) scale(0.95);opacity:0}
    to{transform:translateY(0) scale(1);opacity:1}
}
@media(max-width:480px){#gsTsPopupBox{border-radius:10px;}}
</style>
<script>
(function(){
    'use strict';

    // sessionStorage: tarayıcı sekmesi kapatılınca sıfırlanır — oturumda bir kez göster
    var ANAHTAR = 'gsts_popup_gosterildi';
    if (sessionStorage.getItem(ANAHTAR)) return; // zaten gösterildi
    sessionStorage.setItem(ANAHTAR, '1');

    var SURE = 7, kalan = SURE;
    var ov  = document.getElementById('gsTsPopupOverlay');
    var bar = document.getElementById('gsTsBar');
    var txt = document.getElementById('gsTsCountdown');
    var aud = document.getElementById('gsTsAudio');

    if (!ov) return;

    // Göster
    ov.style.display = 'flex';
    ov.style.animation = 'gsTsFadeIn 0.4s ease';

    // Müzik — muted autoplay trick: sessiz başlat, kullanıcı tıklayınca sesl yap
    var muzikBasladi = false;
    function muzikBaslat() {
        if (muzikBasladi || !aud) return;
        muzikBasladi = true;
        aud.volume = 0.6;
        aud.muted = false;
        aud.play().catch(function(){});
    }

    if (aud) {
        // Muted olarak başlat — tarayıcı bunu otomatik olarak kabul eder
        aud.muted = true;
        aud.volume = 0.6;
        aud.play().then(function(){
            // Sessiz çalıyor — ilk etkileşimde unmute et
            document.addEventListener('click',    function h(){ aud.muted=false; document.removeEventListener('click',h); }, {once:true});
            document.addEventListener('touchstart',function h(){ aud.muted=false; document.removeEventListener('touchstart',h); }, {once:true});
            document.addEventListener('keydown',  function h(){ aud.muted=false; document.removeEventListener('keydown',h); }, {once:true});
        }).catch(function(){
            // Tamamen engellendi — etkileşimde başlat
            ['click','touchstart','keydown'].forEach(function(evt){
                document.addEventListener(evt, function h(){
                    muzikBaslat();
                    document.removeEventListener(evt, h);
                }, {once:true});
            });
        });
    }

    // Progress bar
    bar.style.transitionDuration = SURE + 's';
    requestAnimationFrame(function(){
        requestAnimationFrame(function(){ bar.style.width = '0%'; });
    });

    // Geri sayım
    var iv = setInterval(function(){
        kalan--;
        if (txt) txt.textContent = kalan + ' saniye içinde kapanıyor';
        if (kalan <= 0) { clearInterval(iv); kapatGsTsPopup(); }
    }, 1000);

    document.addEventListener('keydown', function(e){ if(e.key==='Escape') kapatGsTsPopup(); });
    ov.addEventListener('click', function(e){ if(e.target===ov) kapatGsTsPopup(); });

    window.kapatGsTsPopup = function(){
        clearInterval(iv);
        if (aud) { aud.pause(); aud.currentTime = 0; }
        if (ov) {
            ov.style.transition = 'opacity 0.3s ease';
            ov.style.opacity = '0';
            setTimeout(function(){ ov.style.display = 'none'; ov.style.opacity = ''; }, 320);
        }
    };
})();
</script>
</body>
</html>
