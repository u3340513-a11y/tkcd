<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Galeri sayfası.
 *
 * Bölümler:
 *   1. Hero   : Markalı başlık şeridi
 *   2. Izgara : CSS grid masonry — büyük görseller geniş span alır
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta         $seo
 * @var list<array{dosya:string,alt:string,boyut:'buyuk'|'normal'}> $gorseller
 */

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="gl-hero" aria-labelledby="gl-hero-baslik">
    <div class="kapsayici gl-hero__ic">
        <?php if (!empty($seo->breadcrumbs)): ?>
        <nav class="da-hero__breadcrumb" aria-label="Konum">
            <ol class="da-hero__breadcrumb-list">
                <li><a href="/">Ana Sayfa</a></li>
                <?php foreach ($seo->breadcrumbs as $bc): ?>
                <li>
                    <span aria-hidden="true">›</span>
                    <a href="<?= $view->e($bc['path']) ?>"><?= $view->e($bc['label']) ?></a>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <h1 class="gl-hero__baslik belirme" id="gl-hero-baslik">Galeri</h1>
        <p class="gl-hero__alt belirme">
            Etkinliklerimizden, buluşmalarımızdan ve kültürel programlarımızdan kareler.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. FOTOĞRAF IZGARASI                                ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="gl-bolum" aria-label="Galeri fotoğrafları">
    <div class="kapsayici">
        <ul class="gl-izgara">
            <?php foreach ($gorseller as $idx => $gorsel):
                $src = '/assets/img/' . $gorsel['dosya'];
            ?>
            <li class="gl-kart<?= $gorsel['boyut'] === 'buyuk' ? ' gl-kart--genis' : '' ?>">
                <figure class="gl-kart__cerceve">
                    <?php if ($view->assetExists($src)): ?>
                    <img
                        class="gl-kart__gorsel"
                        src="<?= $view->e($view->asset($src)) ?>"
                        alt="<?= $view->e($gorsel['alt']) ?>"
                        loading="<?= $idx < 2 ? 'eager' : 'lazy' ?>"
                        decoding="async"
                    >
                    <?php else: ?>
                    <span class="gl-kart__yedek" role="img" aria-label="<?= $view->e($gorsel['alt']) ?>">
                        <?= $view->icon('camera') ?>
                    </span>
                    <?php endif; ?>
                    <figcaption class="gl-kart__altyazi"><?= $view->e($gorsel['alt']) ?></figcaption>
                </figure>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
