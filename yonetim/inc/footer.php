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

    // ESC tuşu ile kapat
    document.addEventListener('keydown', function (e) {
        if (e.key === 'Escape' && sidebar.classList.contains('open')) {
            closeSidebar();
        }
    });
})();
</script>

<!-- ── Türkiye Görseli Popup ─────────────────────────────────── -->
<div id="tkp-overlay" role="dialog" aria-modal="true" aria-label="Türkiye" style="
    display:none;position:fixed;inset:0;background:rgba(0,0,0,.72);
    align-items:center;justify-content:center;z-index:9999;backdrop-filter:blur(3px);
">
    <div style="position:relative;max-width:min(92vw,720px);border-radius:12px;overflow:hidden;box-shadow:0 24px 60px rgba(0,0,0,.55);">
        <button id="tkp-kapat" aria-label="Kapat" style="
            position:absolute;top:10px;right:12px;background:rgba(0,0,0,.6);border:none;
            color:#fff;font-size:1.1rem;width:32px;height:32px;border-radius:50%;cursor:pointer;
        ">&#x2715;</button>
        <img src="assets/turkiye.jpeg" alt="Türkiye"
             style="display:block;width:100%;height:auto;max-height:85vh;object-fit:contain;">
    </div>
</div>

<!-- ── Müzik + Popup Script ───────────────────────────────────── -->
<script>
(function () {
    'use strict';

    /* ── Popup ── */
    var tkpOverlay = document.getElementById('tkp-overlay');
    var tkpKapat   = document.getElementById('tkp-kapat');
    var TKP_KEY    = 'tkp_panel_v3';

    if (tkpOverlay && tkpKapat) {
        function kapatPopup() { tkpOverlay.style.display = 'none'; }

        if (!sessionStorage.getItem(TKP_KEY)) {
            tkpOverlay.style.display = 'flex';
            sessionStorage.setItem(TKP_KEY, '1');
            setTimeout(kapatPopup, 5000);
        }

        tkpKapat.addEventListener('click', kapatPopup);
        tkpOverlay.addEventListener('click', function (e) {
            if (e.target === tkpOverlay) kapatPopup();
        });
    }

    /* ── Müzik Butonu ── */
    var muzikBtn   = document.getElementById('tb-muzik-btn');
    var muzikIcon  = document.getElementById('tb-muzik-icon');
    var muzikAudio = document.getElementById('tb-muzik-audio');

    if (muzikBtn && muzikAudio) {
        muzikBtn.addEventListener('click', function () {
            if (muzikAudio.paused) {
                muzikAudio.play().then(function () {
                    muzikIcon.className = 'fa-solid fa-pause';
                    muzikBtn.style.background = 'rgba(255,255,255,.15)';
                }).catch(function () {});
            } else {
                muzikAudio.pause();
                muzikIcon.className = 'fa-solid fa-music';
                muzikBtn.style.background = 'none';
            }
        });

        muzikAudio.addEventListener('ended', function () {
            muzikIcon.className = 'fa-solid fa-music';
            muzikBtn.style.background = 'none';
        });
    }
})();
</script>

</body>
</html>