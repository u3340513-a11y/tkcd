<?php
/**
 * TS Bilgi Yarışması — Quiz sayfası.
 *
 * 8 rastgele soru, 10 sn/soru, 5 şıklı çoktan seçmeli.
 * Tüm soruları tamamlamadan çıkılırsa puan yansıtılmaz.
 * Sonuçlar sunucu tarafında doğrulanır.
 *
 * @var PDO    $db_baglanti   baglan.php'den
 * @var string $kullanici_adi Mevcut kullanıcı
 */

// CSRF token üret
if (!isset($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_token'];

// Soruları yükle ve 8 tanesini rastgele seç
$tum_sorular = require __DIR__ . '/quiz-sorulari.php';
$toplam = count($tum_sorular);
$secilen_indexler = array_rand($tum_sorular, 8);
shuffle($secilen_indexler);

// JSON olarak hazırla (client'a sadece soru metni ve seçenekler gönderilir, cevap GÖNDERİLMEZ)
$client_sorular = [];
foreach ($secilen_indexler as $idx) {
    $s = $tum_sorular[$idx];
    $client_sorular[] = [
        'index'      => $idx,
        'soru'       => $s['soru'],
        'secenekler' => $s['secenekler'],
        'zorluk'     => $s['zorluk'],
    ];
}

// Günlük oynama sayısı
$bugun = date('Y-m-d');
$kullanici_adi = $_SESSION['kullanici_adi'] ?? '';
$gunluk_oynama = 0;
try {
    $gs = $db_baglanti->prepare("SELECT COUNT(*) FROM quiz_sonuclari WHERE kullanici_adi = ? AND DATE(oynama_tarihi) = ?");
    $gs->execute([$kullanici_adi, $bugun]);
    $gunluk_oynama = (int) $gs->fetchColumn();
} catch (PDOException $e) {
    // Tablo henüz yoksa 0
}

// Haftalık en iyi skor
$hafta_kodu = date('Y-W');
$kisisel_en_iyi = 0;
try {
    $ks = $db_baglanti->prepare("SELECT MAX(puan) FROM quiz_sonuclari WHERE kullanici_adi = ? AND hafta_kodu = ?");
    $ks->execute([$kullanici_adi, $hafta_kodu]);
    $kisisel_en_iyi = (int) $ks->fetchColumn();
} catch (PDOException $e) {}
?>

<style>
/* ── Quiz Container ─────────────────────────────────────────────────── */
.quiz-wrapper {
    max-width: 800px;
    margin: 0 auto;
    padding: 1.5rem;
}

.quiz-card {
    background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%);
    border-radius: 20px;
    padding: 2.5rem;
    color: #fff;
    box-shadow: 0 20px 60px rgba(0,0,0,0.3);
    position: relative;
    overflow: hidden;
}

.quiz-card::before {
    content: '';
    position: absolute;
    top: -50%;
    left: -50%;
    width: 200%;
    height: 200%;
    background: radial-gradient(circle, rgba(106,17,16,0.15) 0%, transparent 70%);
    pointer-events: none;
}

/* ── Hoşgeldin Ekranı ───────────────────────────────────────────────── */
.quiz-welcome {
    text-align: center;
    padding: 2rem 0;
}

.quiz-welcome__icon {
    font-size: 4rem;
    margin-bottom: 1rem;
    animation: bounce 2s infinite;
}

@keyframes bounce {
    0%, 100% { transform: translateY(0); }
    50% { transform: translateY(-15px); }
}

.quiz-welcome__title {
    font-size: 2rem;
    font-weight: 800;
    background: linear-gradient(135deg, #e94560, #f5a623);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
    margin-bottom: 1rem;
}

.quiz-welcome__rules {
    text-align: left;
    background: rgba(255,255,255,0.08);
    border-radius: 12px;
    padding: 1.25rem 1.5rem;
    margin: 1.5rem 0;
    font-size: 0.95rem;
    line-height: 1.8;
}

.quiz-welcome__rules li {
    margin-bottom: 0.3rem;
}

.quiz-welcome__rules i {
    color: #e94560;
    width: 20px;
    margin-right: 8px;
}

.quiz-welcome__stats {
    display: flex;
    gap: 1rem;
    justify-content: center;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.quiz-stat {
    background: rgba(255,255,255,0.1);
    border-radius: 10px;
    padding: 0.8rem 1.2rem;
    text-align: center;
    min-width: 120px;
}

.quiz-stat__value {
    font-size: 1.5rem;
    font-weight: 700;
    color: #e94560;
}

.quiz-stat__label {
    font-size: 0.75rem;
    color: rgba(255,255,255,0.6);
    text-transform: uppercase;
    letter-spacing: 1px;
}

.btn-quiz-start {
    background: linear-gradient(135deg, #e94560, #c72c41);
    border: none;
    color: #fff;
    font-size: 1.2rem;
    font-weight: 700;
    padding: 1rem 3rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s;
    text-transform: uppercase;
    letter-spacing: 2px;
    box-shadow: 0 8px 25px rgba(233,69,96,0.4);
}

.btn-quiz-start:hover {
    transform: translateY(-3px);
    box-shadow: 0 12px 35px rgba(233,69,96,0.5);
}

.btn-quiz-start:disabled {
    opacity: 0.5;
    cursor: not-allowed;
    transform: none;
}

/* ── Soru Ekranı ────────────────────────────────────────────────────── */
.quiz-question { display: none; }
.quiz-question.active { display: block; }

.quiz-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 1.5rem;
}

.quiz-progress {
    font-size: 0.85rem;
    color: rgba(255,255,255,0.7);
}

.quiz-progress__current {
    font-size: 1.1rem;
    font-weight: 700;
    color: #e94560;
}

.quiz-timer {
    display: flex;
    align-items: center;
    gap: 8px;
    font-size: 1.2rem;
    font-weight: 700;
}

.quiz-timer__icon {
    color: #f5a623;
    animation: pulse 1s infinite;
}

@keyframes pulse {
    0%, 100% { opacity: 1; }
    50% { opacity: 0.5; }
}

.quiz-timer__bar {
    width: 100%;
    height: 6px;
    background: rgba(255,255,255,0.15);
    border-radius: 3px;
    margin-bottom: 1.5rem;
    overflow: hidden;
}

.quiz-timer__fill {
    height: 100%;
    background: linear-gradient(90deg, #e94560, #f5a623);
    border-radius: 3px;
    transition: width 0.1s linear;
    width: 100%;
}

.quiz-timer__fill.warning {
    background: linear-gradient(90deg, #ff4444, #ff6b6b);
}

.quiz-zorluk {
    display: inline-block;
    padding: 3px 10px;
    border-radius: 20px;
    font-size: 0.7rem;
    font-weight: 600;
    text-transform: uppercase;
    letter-spacing: 1px;
}

.quiz-zorluk--kolay { background: rgba(46, 213, 115, 0.2); color: #2ed573; }
.quiz-zorluk--orta  { background: rgba(245, 166, 35, 0.2);  color: #f5a623; }
.quiz-zorluk--zor   { background: rgba(233, 69, 96, 0.2);   color: #e94560; }

.quiz-soru-text {
    font-size: 1.3rem;
    font-weight: 600;
    margin: 1rem 0 1.5rem;
    line-height: 1.5;
}

/* ── Şıklar ─────────────────────────────────────────────────────────── */
.quiz-options {
    display: flex;
    flex-direction: column;
    gap: 0.75rem;
}

.quiz-option {
    background: rgba(255,255,255,0.08);
    border: 2px solid rgba(255,255,255,0.15);
    border-radius: 12px;
    padding: 1rem 1.25rem;
    cursor: pointer;
    transition: all 0.25s;
    display: flex;
    align-items: center;
    gap: 12px;
    font-size: 1rem;
}

.quiz-option:hover {
    background: rgba(255,255,255,0.15);
    border-color: rgba(233,69,96,0.5);
    transform: translateX(5px);
}

.quiz-option.selected {
    background: rgba(233,69,96,0.2);
    border-color: #e94560;
}

.quiz-option.correct {
    background: rgba(46,213,115,0.2);
    border-color: #2ed573;
}

.quiz-option.wrong {
    background: rgba(255,68,68,0.2);
    border-color: #ff4444;
}

.quiz-option.disabled {
    pointer-events: none;
    opacity: 0.6;
}

.quiz-option__letter {
    width: 32px;
    height: 32px;
    border-radius: 50%;
    background: rgba(255,255,255,0.1);
    display: flex;
    align-items: center;
    justify-content: center;
    font-weight: 700;
    font-size: 0.85rem;
    flex-shrink: 0;
}

.quiz-option.selected .quiz-option__letter {
    background: #e94560;
}

.quiz-option.correct .quiz-option__letter {
    background: #2ed573;
}

.quiz-option.wrong .quiz-option__letter {
    background: #ff4444;
}

/* ── Sonuç Ekranı ───────────────────────────────────────────────────── */
.quiz-result {
    display: none;
    text-align: center;
    padding: 2rem 0;
}

.quiz-result.active { display: block; }

.quiz-result__score {
    font-size: 4rem;
    font-weight: 900;
    margin: 1rem 0;
}

.quiz-result__score--high {
    background: linear-gradient(135deg, #2ed573, #7bed9f);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.quiz-result__score--mid {
    background: linear-gradient(135deg, #f5a623, #ffc048);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.quiz-result__score--low {
    background: linear-gradient(135deg, #e94560, #ff6b81);
    -webkit-background-clip: text;
    -webkit-text-fill-color: transparent;
    background-clip: text;
}

.quiz-result__details {
    display: flex;
    gap: 2rem;
    justify-content: center;
    margin: 1.5rem 0;
    flex-wrap: wrap;
}

.quiz-result__detail {
    text-align: center;
}

.quiz-result__detail-value {
    font-size: 2rem;
    font-weight: 700;
}

.quiz-result__detail-label {
    font-size: 0.8rem;
    color: rgba(255,255,255,0.6);
}

.btn-quiz-retry {
    background: rgba(255,255,255,0.15);
    border: 2px solid rgba(255,255,255,0.3);
    color: #fff;
    font-size: 1rem;
    font-weight: 600;
    padding: 0.8rem 2rem;
    border-radius: 50px;
    cursor: pointer;
    transition: all 0.3s;
    margin-top: 1rem;
}

.btn-quiz-retry:hover {
    background: rgba(255,255,255,0.25);
    transform: translateY(-2px);
}

/* ── Responsive ─────────────────────────────────────────────────────── */
@media (max-width: 768px) {
    .quiz-card { padding: 1.5rem; }
    .quiz-welcome__title { font-size: 1.5rem; }
    .quiz-soru-text { font-size: 1.1rem; }
    .quiz-result__score { font-size: 3rem; }
}
</style>

<div class="quiz-wrapper">
    <div class="quiz-card">

        <!-- ── HOŞGELDIN EKRANI ───────────────────────────────── -->
        <div class="quiz-welcome" id="quizWelcome">
            <div class="quiz-welcome__icon">⚽</div>
            <h2 class="quiz-welcome__title">TS Bilgi Yarışması</h2>
            <p style="color:rgba(255,255,255,0.7); margin-bottom:0.5rem;">Trabzonspor hakkında ne kadar bilgilisin?</p>

            <div class="quiz-welcome__stats">
                <div class="quiz-stat">
                    <div class="quiz-stat__value"><?= $toplam ?></div>
                    <div class="quiz-stat__label">Soru Havuzu</div>
                </div>
                <div class="quiz-stat">
                    <div class="quiz-stat__value">8</div>
                    <div class="quiz-stat__label">Soru / Tur</div>
                </div>
                <div class="quiz-stat">
                    <div class="quiz-stat__value">10<small>sn</small></div>
                    <div class="quiz-stat__label">Süre / Soru</div>
                </div>
                <div class="quiz-stat">
                    <div class="quiz-stat__value"><?= $gunluk_oynama ?>/10</div>
                    <div class="quiz-stat__label">Bugün Oynadın</div>
                </div>
            </div>

            <ul class="quiz-welcome__rules">
                <li><i class="fa-solid fa-circle-check"></i> 8 rastgele soru sorulacak</li>
                <li><i class="fa-solid fa-clock"></i> Her soru için 10 saniye süreniz var</li>
                <li><i class="fa-solid fa-star"></i> Her doğru cevap 100 puan değerinde</li>
                <li><i class="fa-solid fa-triangle-exclamation"></i> Yarışmayı yarıda bırakırsanız puan yansımaz</li>
                <li><i class="fa-solid fa-trophy"></i> Haftalık en iyi puanınız: <strong><?= $kisisel_en_iyi ?></strong></li>
            </ul>

            <?php if ($gunluk_oynama >= 10): ?>
                <button class="btn-quiz-start" disabled>
                    <i class="fa-solid fa-lock me-2"></i> Günlük Limit Doldu
                </button>
                <p style="color:rgba(255,255,255,0.5); margin-top:0.5rem; font-size:0.85rem;">Yarın tekrar oynayabilirsiniz.</p>
            <?php else: ?>
                <button class="btn-quiz-start" id="btnQuizStart" onclick="quizBasla()">
                    <i class="fa-solid fa-play me-2"></i> Başla
                </button>
            <?php endif; ?>
        </div>

        <!-- ── SORU EKRANI ─────────────────────────────────────── -->
        <div class="quiz-question" id="quizQuestion">
            <div class="quiz-header">
                <div class="quiz-progress">
                    Soru <span class="quiz-progress__current" id="soruNo">1</span> / 8
                    <span class="quiz-zorluk" id="soruZorluk"></span>
                </div>
                <div class="quiz-timer">
                    <i class="fa-solid fa-stopwatch quiz-timer__icon"></i>
                    <span id="timerText">10</span>
                </div>
            </div>

            <div class="quiz-timer__bar">
                <div class="quiz-timer__fill" id="timerBar"></div>
            </div>

            <div class="quiz-soru-text" id="soruText"></div>

            <div class="quiz-options" id="seceneklerDiv"></div>
        </div>

        <!-- ── SONUÇ EKRANI ────────────────────────────────────── -->
        <div class="quiz-result" id="quizResult">
            <div style="font-size:3rem;">🏆</div>
            <h2 style="font-weight:700; margin:0.5rem 0;">Yarışma Bitti!</h2>
            <div class="quiz-result__score" id="sonucPuan"></div>

            <div class="quiz-result__details">
                <div class="quiz-result__detail">
                    <div class="quiz-result__detail-value" id="sonucDogru" style="color:#2ed573;">0</div>
                    <div class="quiz-result__detail-label">Doğru</div>
                </div>
                <div class="quiz-result__detail">
                    <div class="quiz-result__detail-value" id="sonucYanlis" style="color:#ff4444;">0</div>
                    <div class="quiz-result__detail-label">Yanlış</div>
                </div>
                <div class="quiz-result__detail">
                    <div class="quiz-result__detail-value" id="sonucBos" style="color:#f5a623;">0</div>
                    <div class="quiz-result__detail-label">Boş</div>
                </div>
            </div>

            <p id="sonucMesaj" style="color:rgba(255,255,255,0.7);"></p>

            <button class="btn-quiz-retry" onclick="location.reload()">
                <i class="fa-solid fa-rotate-right me-2"></i> Tekrar Oyna
            </button>
        </div>

    </div>
</div>

<script>
(function() {
    'use strict';

    const SORULAR   = <?= json_encode($client_sorular, JSON_UNESCAPED_UNICODE) ?>;
    const CSRF      = <?= json_encode($csrf_token) ?>;
    const SURE_MS   = 10000; // 10 saniye
    const TOPLAM    = 8;

    let mevcutSoru  = 0;
    let cevaplar    = [];
    let timerID     = null;
    let baslangic   = 0;
    let bosKalan    = 0;

    // ── Quiz Başla ─────────────────────────────────────────
    window.quizBasla = function() {
        document.getElementById('quizWelcome').style.display = 'none';
        document.getElementById('quizQuestion').classList.add('active');
        mevcutSoru = 0;
        cevaplar   = [];
        bosKalan   = 0;
        soruGoster();
    };

    // ── Soru Göster ────────────────────────────────────────
    function soruGoster() {
        if (mevcutSoru >= TOPLAM) {
            yarismaiBitir();
            return;
        }

        const s = SORULAR[mevcutSoru];
        document.getElementById('soruNo').textContent = mevcutSoru + 1;
        document.getElementById('soruText').textContent = s.soru;

        // Zorluk badge
        const zorlukEl = document.getElementById('soruZorluk');
        zorlukEl.textContent = s.zorluk.charAt(0).toUpperCase() + s.zorluk.slice(1);
        zorlukEl.className = 'quiz-zorluk quiz-zorluk--' + s.zorluk;

        // Seçenekler
        const harfler = ['A', 'B', 'C', 'D', 'E'];
        const div = document.getElementById('seceneklerDiv');
        div.innerHTML = '';

        s.secenekler.forEach(function(sec, i) {
            const opt = document.createElement('div');
            opt.className = 'quiz-option';
            opt.innerHTML = '<span class="quiz-option__letter">' + harfler[i] + '</span><span>' + escapeHtml(sec) + '</span>';
            opt.addEventListener('click', function() { cevapSec(i); });
            div.appendChild(opt);
        });

        // Timer başlat
        zamanlayiciBaSlat();
    }

    // ── Zamanlayıcı ────────────────────────────────────────
    function zamanlayiciBaSlat() {
        baslangic = Date.now();
        const bar = document.getElementById('timerBar');
        const txt = document.getElementById('timerText');
        bar.style.width = '100%';
        bar.classList.remove('warning');

        if (timerID) clearInterval(timerID);

        timerID = setInterval(function() {
            const gecen = Date.now() - baslangic;
            const kalan = Math.max(0, SURE_MS - gecen);
            const yuzde = (kalan / SURE_MS) * 100;

            bar.style.width = yuzde + '%';
            txt.textContent = Math.ceil(kalan / 1000);

            if (yuzde < 30) bar.classList.add('warning');

            if (kalan <= 0) {
                clearInterval(timerID);
                sureBitti();
            }
        }, 100);
    }

    // ── Süre Bitti ─────────────────────────────────────────
    function sureBitti() {
        cevaplar.push(-1); // Boş
        bosKalan++;

        // Tüm şıkları devre dışı bırak
        document.querySelectorAll('.quiz-option').forEach(function(el) {
            el.classList.add('disabled');
        });

        setTimeout(function() {
            mevcutSoru++;
            soruGoster();
        }, 800);
    }

    // ── Cevap Seç ──────────────────────────────────────────
    function cevapSec(secim) {
        clearInterval(timerID);
        cevaplar.push(secim);

        // Seçili şıkkı işaretle
        const opts = document.querySelectorAll('.quiz-option');
        opts.forEach(function(el, i) {
            el.classList.add('disabled');
            if (i === secim) el.classList.add('selected');
        });

        // Kısa bekleme sonrası sonraki soru
        setTimeout(function() {
            mevcutSoru++;
            soruGoster();
        }, 600);
    }

    // ── Yarışmayı Bitir ────────────────────────────────────
    function yarismaiBitir() {
        clearInterval(timerID);
        document.getElementById('quizQuestion').classList.remove('active');
        document.getElementById('quizResult').classList.add('active');

        // Sonucu sunucuya gönder
        const soruIdleri = SORULAR.map(function(s) { return s.index; });

        const formData = new FormData();
        formData.append('soru_idleri', JSON.stringify(soruIdleri));
        formData.append('cevaplar', JSON.stringify(cevaplar));
        formData.append('csrf_token', CSRF);

        fetch('index.php?sayfa=quiz-kaydet', {
            method: 'POST',
            body: formData,
        })
        .then(function(r) {
            return r.text();
        })
        .then(function(text) {
            var data;
            try {
                data = JSON.parse(text);
            } catch (e) {
                document.getElementById('sonucMesaj').textContent = 'Sunucu yanıtı okunamadı.';
                console.error('Quiz JSON parse hatası:', text);
                return;
            }

            if (data.ok) {
                var dogru  = data.dogru_sayisi;
                var yanlis = TOPLAM - dogru - bosKalan;
                var puan   = data.toplam_puan;

                document.getElementById('sonucDogru').textContent  = dogru;
                document.getElementById('sonucYanlis').textContent  = yanlis;
                document.getElementById('sonucBos').textContent     = bosKalan;

                var puanEl = document.getElementById('sonucPuan');
                puanEl.textContent = puan + ' Puan';

                if (dogru >= 6) {
                    puanEl.className = 'quiz-result__score quiz-result__score--high';
                    document.getElementById('sonucMesaj').textContent = 'Mükemmel! Gerçek bir Trabzonspor bilginisin! 🔥';
                } else if (dogru >= 4) {
                    puanEl.className = 'quiz-result__score quiz-result__score--mid';
                    document.getElementById('sonucMesaj').textContent = 'İyi! Biraz daha çalışarak şampiyon olabilirsin! 💪';
                } else {
                    puanEl.className = 'quiz-result__score quiz-result__score--low';
                    document.getElementById('sonucMesaj').textContent = 'Biraz daha pratik yapmak gerek! 📚';
                }
            } else {
                document.getElementById('sonucMesaj').textContent = data.mesaj || 'Sonuç kaydedilemedi.';
            }
        })
        .catch(function(err) {
            document.getElementById('sonucMesaj').textContent = 'Bağlantı hatası. Sonuç kaydedilemedi.';
            console.error('Quiz fetch hatası:', err);
        });
    }

    // ── Yardımcı: HTML Escape ──────────────────────────────
    function escapeHtml(text) {
        const d = document.createElement('div');
        d.textContent = text;
        return d.innerHTML;
    }
})();
</script>
