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
<!-- ── Duyuru Popup ── -->
<div id="hiyerPopupOverlay" style="
    position: fixed;
    inset: 0;
    background: rgba(0,0,0,0.55);
    z-index: 9999;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 16px;
    backdrop-filter: blur(3px);
    animation: popupFadeIn 0.35s ease;
">
    <div id="hiyerPopupBox" style="
        position: relative;
        max-width: 680px;
        width: 100%;
        border-radius: 14px;
        overflow: hidden;
        box-shadow: 0 24px 60px rgba(0,0,0,0.4);
        animation: popupSlideUp 0.35s ease;
    ">
        <!-- Kapat butonu -->
        <button id="hiyerCloseBtn" onclick="kapatHiyerPopup()" style="
            position: absolute;
            top: 10px;
            right: 10px;
            z-index: 10;
            width: 34px;
            height: 34px;
            border-radius: 50%;
            border: none;
            background: rgba(0,0,0,0.5);
            color: #fff;
            font-size: 16px;
            cursor: pointer;
            display: flex;
            align-items: center;
            justify-content: center;
            line-height: 1;
            transition: background 0.2s;
        " onmouseover="this.style.background='rgba(0,0,0,0.75)'"
           onmouseout="this.style.background='rgba(0,0,0,0.5)'"
           aria-label="Kapat">
            <i class="fa-solid fa-xmark"></i>
        </button>

        <!-- Görsel -->
        <img src="/assets/img/hiyer.jpeg"
             alt="Duyuru"
             style="display:block; width:100%; height:auto; max-height:80vh; object-fit:contain; background:#000;">

        <!-- Countdown bar -->
        <div style="background:#1a1a2e; padding:8px 16px; display:flex; align-items:center; gap:10px;">
            <span id="hiyerCountdownText" style="color:rgba(255,255,255,0.7); font-size:0.78rem; white-space:nowrap;">
                5 saniye içinde kapanıyor
            </span>
            <div style="flex:1; height:3px; background:rgba(255,255,255,0.15); border-radius:2px; overflow:hidden;">
                <div id="hiyerProgressBar" style="
                    height:100%;
                    width:100%;
                    background: linear-gradient(90deg, #3b82f6, #06b6d4);
                    border-radius:2px;
                    transition: width linear;
                "></div>
            </div>
            <button onclick="kapatHiyerPopup()" style="
                background: none;
                border: 1px solid rgba(255,255,255,0.3);
                color: rgba(255,255,255,0.7);
                font-size: 0.72rem;
                border-radius: 4px;
                padding: 3px 10px;
                cursor: pointer;
                white-space: nowrap;
            ">Kapat</button>
        </div>
    </div>
</div>

<style>
@keyframes popupFadeIn {
    from { opacity: 0; }
    to   { opacity: 1; }
}
@keyframes popupSlideUp {
    from { transform: translateY(24px) scale(0.97); opacity: 0; }
    to   { transform: translateY(0) scale(1);       opacity: 1; }
}
</style>

<script>
(function () {
    'use strict';

    var SURE = 5; // saniye
    var kalan = SURE;
    var overlay = document.getElementById('hiyerPopupOverlay');
    var bar     = document.getElementById('hiyerProgressBar');
    var txt     = document.getElementById('hiyerCountdownText');

    if (!overlay) return;

    // Countdown başlat
    bar.style.transitionDuration = SURE + 's';
    // rAF ile smooth başlat
    requestAnimationFrame(function() {
        requestAnimationFrame(function() {
            bar.style.width = '0%';
        });
    });

    var interval = setInterval(function () {
        kalan--;
        if (txt) txt.textContent = kalan + ' saniye içinde kapanıyor';
        if (kalan <= 0) {
            clearInterval(interval);
            kapatHiyerPopup();
        }
    }, 1000);

    // ESC ile kapat
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape') kapatHiyerPopup();
    });

    // Overlay dışına tıklayınca kapat
    overlay.addEventListener('click', function (e) {
        if (e.target === overlay) kapatHiyerPopup();
    });

    window.kapatHiyerPopup = function () {
        clearInterval(interval);
        if (overlay) {
            overlay.style.transition = 'opacity 0.25s ease';
            overlay.style.opacity = '0';
            setTimeout(function () {
                overlay.remove();
            }, 260);
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