    </div><!-- .panel-main -->

    <footer class="panel-footer">
        &copy; <?= date('Y'); ?> Trabzonlu Kamu Çalışanları Derneği — Tüm Hakları Saklıdır.
    </footer>

</div><!-- .panel-content -->
</div><!-- .panel-layout -->

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

    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>

<!-- ── Türkiye Görseli Popup ─────────────────────────────────── -->
<div id="tkp-overlay" role="dialog" aria-modal="true" aria-label="Türkiye" style="
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
            <button id="tkp-kapat" aria-label="Kapat" style="
                background:rgba(0,0,0,.55);border:none;color:#fff;
                font-size:1.2rem;width:36px;height:36px;border-radius:50%;cursor:pointer;
                display:flex;align-items:center;justify-content:center;
            ">&#x2715;</button>
        </div>

        <img src="assets/turkiye.jpeg" alt="Türkiye" style="
            display:block;width:100%;height:auto;
            max-height:calc(100svh - 80px);object-fit:contain;vertical-align:top;
        ">
    </div>
</div>

<!-- ── Müzik + Popup Script ───────────────────────────────────── -->
<script>
(function () {
    'use strict';

    /* ── Popup ── */
    var tkpOverlay = document.getElementById('tkp-overlay');
    var tkpKapat   = document.getElementById('tkp-kapat');
    var sayacEl    = document.getElementById('tkp-sayac');
    var TKP_KEY    = 'tkp_panel_v4';
    var popupTimer;

    function kapatPopup() {
        if (tkpOverlay) tkpOverlay.style.display = 'none';
        clearInterval(popupTimer);
    }

    if (tkpOverlay && !sessionStorage.getItem(TKP_KEY)) {
        tkpOverlay.style.display = 'flex';
        sessionStorage.setItem(TKP_KEY, '1');

        var kalan = 5;
        popupTimer = setInterval(function () {
            kalan--;
            if (sayacEl) sayacEl.textContent = kalan;
            if (kalan <= 0) kapatPopup();
        }, 1000);
    }

    if (tkpKapat) tkpKapat.addEventListener('click', kapatPopup);
    if (tkpOverlay) {
        tkpOverlay.addEventListener('click', function (e) {
            if (e.target === tkpOverlay) kapatPopup();
        });
    }

    /* ── Müzik — otomatik başlar, topbar butonu durdurur/başlatır ── */
    var muzikBtn   = document.getElementById('tb-muzik-btn');
    var muzikIcon  = document.getElementById('tb-muzik-icon');
    var muzikAudio = document.getElementById('tb-muzik-audio');

    function ikonGuncelle() {
        if (!muzikAudio) return;
        if (muzikAudio.paused) {
            if (muzikIcon) muzikIcon.className = 'fa-solid fa-music';
            if (muzikBtn)  muzikBtn.style.background = 'none';
        } else {
            if (muzikIcon) muzikIcon.className = 'fa-solid fa-pause';
            if (muzikBtn)  muzikBtn.style.background = 'rgba(255,255,255,.15)';
        }
    }

    if (muzikAudio) {
        /* Tarayıcı autoplay politikası: kullanıcı etkileşimi sonrası oynat.
           İlk kullanıcı etkileşimini (click/keydown/touchstart) yakalayıp
           o an başlatıyoruz — böylece autoplay engeli aşılır.           */
        var otomatikBasladi = false;
        function otomatikBaslat() {
            if (otomatikBasladi) return;
            otomatikBasladi = true;
            muzikAudio.play().then(ikonGuncelle).catch(function () {});
            document.removeEventListener('click',      otomatikBaslat);
            document.removeEventListener('keydown',    otomatikBaslat);
            document.removeEventListener('touchstart', otomatikBaslat);
        }

        /* Doğrudan autoplay dene (bazı tarayıcılarda işe yarar) */
        muzikAudio.play().then(function () {
            otomatikBasladi = true;
            ikonGuncelle();
        }).catch(function () {
            /* Engellendiyse ilk kullanıcı etkileşimini bekle */
            document.addEventListener('click',      otomatikBaslat, { once: true });
            document.addEventListener('keydown',    otomatikBaslat, { once: true });
            document.addEventListener('touchstart', otomatikBaslat, { once: true });
        });

        muzikAudio.addEventListener('ended', ikonGuncelle);

        if (muzikBtn) {
            muzikBtn.addEventListener('click', function () {
                otomatikBasladi = true;
                if (muzikAudio.paused) {
                    muzikAudio.play().then(ikonGuncelle).catch(function () {});
                } else {
                    muzikAudio.pause();
                    ikonGuncelle();
                }
            });
        }
    }
})();
</script>

</body>
</html>