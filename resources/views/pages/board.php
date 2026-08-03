<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Yönetim Kurulu sayfası.
 *
 * Bölümler:
 *   1. Hero      : Markalı başlık şeridi
 *   2. Üye Kartları : Fotoğraf, unvan, görevler, sosyal bağlantılar
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta         $seo
 * @var array<string, mixed> $site
 * @var list<array{slug:string,ad:string,unvan:string,fotograf:string,biyografi:string,gorevler:list<string>,sosyal:array<string,string>}> $uyeler
 */

/** @var array<string, string> */
$sosyalEtiketler = [
    'facebook'  => 'Facebook',
    'instagram' => 'Instagram',
    'linkedin'  => 'LinkedIn',
    'x'         => 'X',
    'youtube'   => 'YouTube',
    'whatsapp'  => 'WhatsApp',
];

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="yk-hero" aria-labelledby="yk-hero-baslik">
    <div class="kapsayici yk-hero__ic">
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

        <h1 class="yk-hero__baslik belirme" id="yk-hero-baslik">Yönetim Kurulu</h1>
        <p class="yk-hero__alt belirme">
            Derneğimizi kuran ve yöneten değerli başkanlarımız.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. ÜYE KARTLARI                                     ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="yk-bolum" aria-label="Yönetim kurulu üyeleri">
    <div class="kapsayici">
        <ul class="yk-izgara">
            <?php foreach ($uyeler as $uye): ?>
            <li class="yk-karti belirme" id="uye-<?= $view->e($uye['slug']) ?>">

                <!-- Fotoğraf -->
                <div class="yk-karti__fotograf-cerceve" aria-hidden="true">
                    <img
                        class="yk-karti__fotograf"
                        src="<?= $view->e($view->asset('assets/img/' . $uye['fotograf'])) ?>"
                        alt="<?= $view->e($uye['ad']) ?> fotoğrafı"
                        width="200" height="200"
                        loading="lazy"
                        decoding="async"
                    >
                </div>

                <!-- Bilgiler -->
                <div class="yk-karti__govde">
                    <h2 class="yk-karti__ad"><?= $view->e($uye['ad']) ?></h2>
                    <p class="yk-karti__unvan"><?= $view->e($uye['unvan']) ?></p>

                    <?php if (!empty($uye['biyografi'])): ?>
                    <p class="yk-karti__biyografi"><?= $view->e($uye['biyografi']) ?></p>
                    <?php endif; ?>

                    <?php if (!empty($uye['gorevler'])): ?>
                    <ul class="yk-karti__gorevler" aria-label="Diğer görevler">
                        <?php foreach ($uye['gorevler'] as $gorev): ?>
                        <li><?= $view->e($gorev) ?></li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>

                    <!-- Sosyal bağlantılar -->
                    <?php
                    $aktifSosyal = array_filter(
                        $uye['sosyal'],
                        static fn(string $url): bool => $url !== ''
                    );
                    ?>
                    <?php if (!empty($aktifSosyal)): ?>
                    <ul class="yk-karti__sosyal" aria-label="<?= $view->e($uye['ad']) ?> sosyal medya">
                        <?php foreach ($aktifSosyal as $platform => $url): ?>
                        <li>
                            <a class="yk-karti__sosyal-link yk-karti__sosyal-link--<?= $view->e($platform) ?>"
                               href="<?= $view->e($url) ?>"
                               target="_blank"
                               rel="noopener noreferrer"
                               aria-label="<?= $view->e($sosyalEtiketler[$platform] ?? $platform) ?>">
                                <?= $view->icon($platform) ?>
                            </a>
                        </li>
                        <?php endforeach; ?>
                    </ul>
                    <?php endif; ?>
                </div>

            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>
