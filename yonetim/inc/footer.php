    </div><!-- .panel-main -->

    <footer class="panel-footer">
        &copy; <?= date('Y'); ?> Trabzonlu Kamu Çalışanları Derneği — Tüm Hakları Saklıdır.
    </footer>

</div><!-- .panel-content -->
</div><!-- .panel-layout -->

<?php
// Popup: Her oturumda yalnızca bir kez göster
if (!isset($_SESSION['hiyer_popup_gosterildi'])) {
    $_SESSION['hiyer_popup_gosterildi'] = true;
    $goster_popup = true;
} else {
    $goster_popup = false;
}
?>

<?php if ($goster_popup): ?>
<!-- ── GS-TS Açılış Popup ── -->
<div id="gsTsPopupOverlay" style="
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.65);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 12px;
    backdrop-filter: blur(4px);
    -webkit-backdrop-filter: blur(4px);
    animation: gsTsFadeIn 0.4s ease;
">
    <div id="gsTsPopupBox" style="
        position: relative;
        max-width: 720px;
        width: 100%;
        border-radius: 16px;
        overflow: hidden;
        box-shadow: 0 28px 70px rgba(0,0,0,0.55);
        animation: gsTsSlideUp 0.4s cubic-bezier(0.34,1.56,0.64,1);
    ">
        <!-- Kapat butonu -->
        <button id="gsTsCloseBtn" onclick="kapatGsTsPopup()" style="
            position: absolute;
            top: 12px;
            right: 12px;
            z-index: 10;
            width: 36px;
            height: 36px;
            border-radius: 50%;
            border: none;
            background: rgba(0,0,0,0.55);
            color: #fff;
            font-size: 17px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            transition: background 0.2s, transform 0.2s;
        "
        onmouseover="this.style.background='rgba(0,0,0,0.82)';this.style.transform='scale(1.1)'"
        onmouseout="this.style.background='rgba(0,0,0,0.55)';this.style.transform='scale(1)'"
        aria-label="Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Görsel -->
        <img src="/assets/img/gs-ts.jpeg"
             alt="Galatasaray - Trabzonspor"
             style="display:block; width:100%; height:auto; max-height:78vh; object-fit:contain; background:#0a0a0a;">

        <!-- Countdown bar -->
        <div style="background:#0a0a0a; padding:9px 16px; display:flex; align-items:center; gap:10px;">
            <span id="gsTsCountdownText" style="color:rgba(255,255,255,0.65); font-size:0.78rem; white-space:nowrap; flex-shrink:0;">
                5 saniye içinde kapanıyor
            </span>
            <div style="flex:1; height:3px; background:rgba(255,255,255,0.12); border-radius:2px; overflow:hidden;">
                <div id="gsTsProgressBar" style="
                    height:100%;
                    width:100%;
                    background: linear-gradient(90deg, #e8251e, #ffd700);
                    border-radius:2px;
                    transition: width linear;
                "></div>
            </div>
            <button onclick="kapatGsTsPopup()" style="
                background: none;
                border: 1px solid rgba(255,255,255,0.28);
                color: rgba(255,255,255,0.65);
                font-size: 0.72rem;
                border-radius: 6px;
                padding: 3px 12px;
                cursor: pointer;
                white-space: nowrap;
                transition: border-color 0.2s, color 0.2s;
                flex-shrink: 0;
            ">Kapat</button>
        </div>
    </div>
</div>

<!-- Müzik -->
<audio id="gsTsAudio" preload="auto" style="display:none;">
    <source src="/assets/video/dalga-dalga.mp3" type="audio/mpeg">
</audio>

<style>
@keyframes gsTsFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
@keyframes gsTsSlideUp {
    from { transform: translateY(32px) scale(0.95); opacity: 0; }
    to   { transform: translateY(0) scale(1);       opacity: 1; }
}
@media (max-width: 480px) {
    #gsTsPopupBox { border-radius: 10px; }
}
</style>

<script>
(function () {
    'use strict';

    var SURE = 5;
    var kalan = SURE;
    var overlay = document.getElementById('gsTsPopupOverlay');
    var bar     = document.getElementById('gsTsProgressBar');
    var txt     = document.getElementById('gsTsCountdownText');
    var audio   = document.getElementById('gsTsAudio');

    if (!overlay) return;

    // Müzik — ilk kullanıcı etkileşiminde çal (autoplay politikası)
    var muzikCalindi = false;
    function muzikCal() {
        if (muzikCalindi || !audio) return;
        muzikCalindi = true;
        audio.volume = 0.6;
        audio.play().catch(function(){});
    }
    // Önce direkt autoplay dene
    if (audio) {
        audio.volume = 0.6;
        var autoPromise = audio.play();
        if (autoPromise !== undefined) {
            autoPromise.then(function(){ muzikCalindi = true; }).catch(function(){
                // Engellendi — ilk etkileşimde çal
                var olaylar = ['click','touchstart','keydown','scroll'];
                function ilkEtkilesim() {
                    muzikCal();
                    olaylar.forEach(function(o){ document.removeEventListener(o, ilkEtkilesim); });
                }
                olaylar.forEach(function(o){ document.addEventListener(o, ilkEtkilesim, {once:true}); });
            });
        }
    }

    // Progress bar
    bar.style.transitionDuration = SURE + 's';
    requestAnimationFrame(function () {
        requestAnimationFrame(function () {
            bar.style.width = '0%';
        });
    });

    var interval = setInterval(function () {
        kalan--;
        if (txt) txt.textContent = kalan + ' saniye içinde kapanıyor';
        if (kalan <= 0) {
            clearInterval(interval);
            kapatGsTsPopup();
        }
    }, 1000);

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kapatGsTsPopup();
    });

    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) kapatGsTsPopup();
    });

    window.kapatGsTsPopup = function () {
        clearInterval(interval);
        if (audio) { audio.pause(); audio.currentTime = 0; }
        if (overlay) {
            overlay.style.transition = 'opacity 0.3s ease';
            overlay.style.opacity = '0';
            setTimeout(function () { overlay.remove(); }, 320);
        }
    };
})();
</script>
<?php endif; ?>

<!-- Bootstrap 5 ve FontAwesome JS -->
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.2/dist/js/bootstrap.bundle.min.js"></script>

<!-- Sidebar Toggle Script -->
<script>
(function () {
    'use strict';
    var toggle  = document.getElementById('sidebarToggle');
    var sidebar = document.getElementById('panelSidebar');
    var overlay = document.getElementById('sbOverlay');

    if (!toggle || !sidebar) return;

    function openSidebar() {
        sidebar.classList.add('open');
        if (overlay) overlay.classList.add('visible');
        document.body.style.overflow = 'hidden';
    }

    function closeSidebar() {
        sidebar.classList.remove('open');
        if (overlay) overlay.classList.remove('visible');
        document.body.style.overflow = '';
    }

    toggle.addEventListener('click', function () {
        if (sidebar.classList.contains('open')) {
            closeSidebar();
        } else {
            openSidebar();
        }
    });

    if (overlay) {
        overlay.addEventListener('click', closeSidebar);
    }

    // ESC tuşu ile kapat
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>

</body>
</html>