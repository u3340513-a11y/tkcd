<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * "Temsilci Ağımız" sayfası.
 *
 * İnteraktif Türkiye haritası: bir ilin üzerine gelindiğinde varsa
 * il temsilcisinin adı ve iletişim bilgisi tooltip olarak gösterilir.
 * Temsilci atanmamış iller haritada soluk görünür; atanmış iller
 * bordo renkle vurgulanır.
 *
 * Veri katmanı: resources/data/representatives.php (ikinci fazda DB'ye geçecek).
 * Harita yolları: resources/data/turkey-map.php (MIT — react-turkey-map)
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var array<string, mixed> $site
 * @var array<string, array{il_adi: string, temsilci: string, telefon: string, eposta: string}> $temsilciler
 * @var list<array{plate: string, il: string, yol: string}> $haritaYollari
 */

$atanmisIller = array_filter($temsilciler, fn($t) => $t['temsilci'] !== '');
$atanmisCount = count($atanmisIller);
$toplamCount  = 81;
?>

<!-- ===== HERO ===== -->
<section class="da-hero" aria-labelledby="tm-hero-baslik">
    <span class="da-hero__isik da-hero__isik--bordo" aria-hidden="true"></span>
    <span class="da-hero__isik da-hero__isik--mavi"  aria-hidden="true"></span>

    <div class="kapsayici da-hero__ic">
        <nav class="kirinti" aria-label="Site haritası">
            <a href="/">Ana Sayfa</a>
<?php foreach ($seo->breadcrumbs as $index => $crumb): ?>
            <span class="kirinti__ayrac" aria-hidden="true">/</span>
<?php if ($index === array_key_last($seo->breadcrumbs)): ?>
            <span aria-current="page"><?= $view->e($crumb['label']) ?></span>
<?php else: ?>
            <a href="<?= $view->link($crumb['path']) ?>"><?= $view->e($crumb['label']) ?></a>
<?php endif; ?>
<?php endforeach; ?>
        </nav>

        <div class="da-hero__govde pk-hero__govde">
            <div class="da-hero__metin belirme">
                <span class="da-hero__rozet">
                    <?= $view->icon('map') ?>
                    Türkiye Geneli
                </span>

                <h1 class="da-hero__baslik" id="tm-hero-baslik">
                    Türkiye'nin dört bir yanındaki<br>
                    Trabzonlu kamu çalışanları ile <span>omuz omuzayız.</span>
                </h1>

                <p class="da-hero__ozet">
                    81 ilde büyüyen temsilcilik ağımızla üyelerimize destek olmaya,
                    dayanışmayı güçlendirmeye ve Trabzon ruhunu yaşatmaya devam ediyoruz.
                </p>

                <div class="da-hero__eylemler">
                    <a class="dugme" href="<?= $view->link('/uye-ol') ?>">
                        <?= $view->icon('users') ?>
                        Üye Ol
                    </a>
                    <a class="dugme dugme--hayalet" href="#harita">
                        Haritayı Keşfet
                        <?= $view->icon('arrow-right') ?>
                    </a>
                </div>
            </div>

        </div>
    </div>

    <svg class="da-hero__dalga" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 54c200-36 400-36 600 0s400 36 600 0 200-36 400 0v36H0z" fill="currentColor" opacity=".5"/>
        <path d="M0 72c240-30 480-12 720 6s480 30 720-6v18H0z" fill="currentColor" opacity=".3"/>
    </svg>
</section>

<!-- ===== İNTERAKTİF HARİTA ===== -->
<section class="bolum tm-harita-bolum" id="harita" aria-labelledby="harita-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket'   => 'Temsilcilik Ağı',
    'baslik'   => 'Temsilcilik',
    'vurgu'    => 'Ağımız',
    'aciklama' => 'Haritada bir ilin üzerine gelerek il temsilcimizin iletişim bilgilerine ulaşabilirsiniz. Bordo renkle gösterilen iller aktif temsilciye sahiptir.',
    'id'       => 'harita-baslik',
]) ?>

        <div class="tm-harita-kapsayici" role="region" aria-label="Türkiye temsilci ağı haritası">
            <!-- İl tooltip'i — JS ile konumlandırılır -->
            <div class="tm-tooltip" id="tm-tooltip" role="tooltip" aria-hidden="true" hidden>
                <div class="tm-tooltip__baslik" id="tm-tooltip-il"></div>

                <!-- İl Başkanı -->
                <div class="tm-tooltip__bolum" id="tm-tt-il-bolum" hidden>
                    <p class="tm-tooltip__alt-baslik">İl Başkanı</p>
                    <p class="tm-tooltip__satir tm-tooltip__satir--temsilci">
                        <span aria-hidden="true"><?= $view->icon('users') ?></span>
                        <span id="tm-tooltip-temsilci"></span>
                    </p>
                    <p class="tm-tooltip__satir tm-tooltip__satir--telefon" id="tm-tt-il-telefon-satir" hidden>
                        <span aria-hidden="true"><?= $view->icon('phone') ?></span>
                        <span id="tm-tooltip-telefon"></span>
                    </p>
                </div>

                <!-- İlçe Başkanları -->
                <div class="tm-tooltip__bolum tm-tooltip__bolum--ilce" id="tm-tt-ilce-bolum" hidden>
                    <p class="tm-tooltip__alt-baslik">İlçe Başkanları</p>
                    <ul id="tm-tt-ilceler"></ul>
                </div>

                <!-- Hiç atama yoksa -->
                <p class="tm-tooltip__bos" id="tm-tooltip-bos" hidden>Atama Bekleniyor</p>
            </div>

            <!-- Türkiye SVG haritası — 1000×600 viewBox -->
            <svg
                class="tm-harita-svg"
                viewBox="0 0 1007 443"
                role="img"
                aria-labelledby="tm-harita-svg-baslik"
                id="tm-harita"
                data-temsilciler='<?= htmlspecialchars(json_encode($temsilciler, JSON_UNESCAPED_UNICODE | JSON_HEX_APOS), ENT_QUOTES, 'UTF-8') ?>'
            >
                <title id="tm-harita-svg-baslik">Türkiye temsilcilik haritası</title>
                <g class="tm-harita-iller">
<?php foreach ($haritaYollari as $il): ?>
<?php $plate = $il['plate']; ?>
<?php $temsilci = $temsilciler[$plate] ?? null; ?>
<?php $atanmis = $temsilci !== null && ($temsilci['temsilci'] ?? '') !== ''; ?>
                    <path
                        class="tm-il<?= $atanmis ? ' tm-il--atanmis' : '' ?>"
                        d="<?= $view->e($il['yol']) ?>"
                        data-plate="<?= $view->e($plate) ?>"
                        data-il="<?= $view->e($il['il']) ?>"
                        tabindex="0"
                        role="button"
                        aria-label="<?= $view->e($il['il']) ?> — <?= $atanmis ? 'temsilci: ' . $view->e($temsilci['temsilci']) : 'temsilci atanmamış' ?>"
                    ><title><?= $view->e($il['il']) ?></title></path>
<?php endforeach; ?>
                </g>
            </svg>
        </div>

        <!-- Açıklama -->
        <div class="tm-harita-aciklama" aria-label="Harita renk açıklaması">
            <span class="tm-harita-aciklama__oge tm-harita-aciklama__oge--atanmis">
                <span class="tm-harita-aciklama__renk" aria-hidden="true"></span>
                Temsilci atanmış iller
            </span>
            <span class="tm-harita-aciklama__oge">
                <span class="tm-harita-aciklama__renk tm-harita-aciklama__renk--bos" aria-hidden="true"></span>
                Henüz temsilci atanmamış iller
            </span>
        </div>
    </div>
</section>

