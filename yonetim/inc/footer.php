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
        <img src="../public/assets/img/turkiye.jpeg" alt="Türkiye"
             style="display:block;width:100%;height:auto;max-height:85vh;object-fit:contain;">
    </div>
</div>

<!-- ── Floating Müzik Player ─────────────────────────────────── -->
<div id="panel-player" style="
    position:fixed;bottom:20px;right:20px;z-index:8888;
    background:linear-gradient(135deg,#1e2a3a,#2d3f55);
    border-radius:16px;padding:12px 16px;min-width:260px;
    box-shadow:0 8px 32px rgba(0,0,0,.45);color:#fff;
    font-family:inherit;
">
    <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:10px;">
        <span style="font-size:.75rem;font-weight:700;letter-spacing:.08em;opacity:.7;text-transform:uppercase;">🎵 Müzik</span>
        <button id="pp-minimize" title="Küçült" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1rem;padding:0;opacity:.7;">&#x2212;</button>
    </div>
    <div id="pp-body">
        <div style="margin-bottom:8px;">
            <div id="pp-track-name" style="font-size:.85rem;font-weight:600;white-space:nowrap;overflow:hidden;text-overflow:ellipsis;max-width:220px;">—</div>
        </div>
        <audio id="pp-audio" preload="none"></audio>
        <div style="display:flex;align-items:center;gap:8px;margin-bottom:10px;">
            <button id="pp-prev" title="Önceki" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1rem;">&#x23EE;</button>
            <button id="pp-play" title="Oynat" style="
                background:#e63946;border:none;color:#fff;cursor:pointer;
                width:36px;height:36px;border-radius:50%;font-size:1rem;
                display:flex;align-items:center;justify-content:center;
            ">&#x25B6;</button>
            <button id="pp-next" title="Sonraki" style="background:none;border:none;color:#fff;cursor:pointer;font-size:1rem;">&#x23ED;</button>
            <input id="pp-volume" type="range" min="0" max="1" step="0.05" value="0.7"
                style="flex:1;accent-color:#e63946;cursor:pointer;" title="Ses seviyesi">
        </div>
        <div style="display:flex;flex-direction:column;gap:4px;" id="pp-list"></div>
    </div>
</div>

<script>
(function () {
    'use strict';

    /* ── Popup ── */
    var tkpOverlay = document.getElementById('tkp-overlay');
    var tkpKapat   = document.getElementById('tkp-kapat');
    var TKP_KEY    = 'tkp_panel_v1';

    if (tkpOverlay) {
        if (!sessionStorage.getItem(TKP_KEY)) {
            tkpOverlay.style.display = 'flex';
            sessionStorage.setItem(TKP_KEY, '1');
        }
        function kapatPopup() { tkpOverlay.style.display = 'none'; }
        tkpKapat.addEventListener('click', kapatPopup);
        tkpOverlay.addEventListener('click', function (e) { if (e.target === tkpOverlay) kapatPopup(); });
        document.addEventListener('keydown', function (e) { if (e.key === 'Escape') kapatPopup(); });
    }

    /* ── Müzik Player ── */
    var playlist = [
        { ad: 'Skaso',                src: '../public/assets/video/skaso.mp3' },
        { ad: 'Dalga Dalga',          src: '../public/assets/video/dalga-dalga.mp3' },
        { ad: 'Semicenk — Tek Yürek', src: '../public/assets/video/Semicenk - Tek Yürek.mp3' },
    ];

    var audio      = document.getElementById('pp-audio');
    var playBtn    = document.getElementById('pp-play');
    var prevBtn    = document.getElementById('pp-prev');
    var nextBtn    = document.getElementById('pp-next');
    var volSlider  = document.getElementById('pp-volume');
    var trackName  = document.getElementById('pp-track-name');
    var listEl     = document.getElementById('pp-list');
    var minimizeBtn= document.getElementById('pp-minimize');
    var ppBody     = document.getElementById('pp-body');
    var current    = 0;
    var minimized  = false;

    /* Liste oluştur */
    playlist.forEach(function (t, i) {
        var btn = document.createElement('button');
        btn.textContent = (i + 1) + '. ' + t.ad;
        btn.id = 'pp-track-' + i;
        btn.style.cssText = 'background:rgba(255,255,255,.08);border:none;color:#fff;cursor:pointer;' +
            'text-align:left;padding:5px 8px;border-radius:6px;font-size:.78rem;width:100%;transition:background .2s;';
        btn.addEventListener('mouseenter', function () { this.style.background = 'rgba(255,255,255,.18)'; });
        btn.addEventListener('mouseleave', function () { this.style.background = i === current ? 'rgba(230,57,70,.35)' : 'rgba(255,255,255,.08)'; });
        btn.addEventListener('click', function () { yukle(i); oyna(); });
        listEl.appendChild(btn);
    });

    function yukle(idx) {
        current = idx;
        audio.src = playlist[idx].src;
        audio.volume = parseFloat(volSlider.value);
        trackName.textContent = playlist[idx].ad;
        listEl.querySelectorAll('button').forEach(function (b, i) {
            b.style.background = i === idx ? 'rgba(230,57,70,.35)' : 'rgba(255,255,255,.08)';
        });
    }

    function oyna() {
        audio.play().then(function () {
            playBtn.innerHTML = '&#x23F8;';
        }).catch(function () {});
    }

    function durdur() {
        audio.pause();
        playBtn.innerHTML = '&#x25B6;';
    }

    playBtn.addEventListener('click', function () {
        if (!audio.src) yukle(current);
        if (audio.paused) oyna(); else durdur();
    });

    prevBtn.addEventListener('click', function () {
        yukle((current - 1 + playlist.length) % playlist.length);
        oyna();
    });

    nextBtn.addEventListener('click', function () {
        yukle((current + 1) % playlist.length);
        oyna();
    });

    audio.addEventListener('ended', function () {
        yukle((current + 1) % playlist.length);
        oyna();
    });

    volSlider.addEventListener('input', function () {
        audio.volume = parseFloat(this.value);
    });

    minimizeBtn.addEventListener('click', function () {
        minimized = !minimized;
        ppBody.style.display = minimized ? 'none' : 'block';
        minimizeBtn.innerHTML = minimized ? '&#x002B;' : '&#x2212;';
    });

    yukle(0);
})();
</script>

</body>
</html>