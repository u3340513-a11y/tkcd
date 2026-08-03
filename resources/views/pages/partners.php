<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * "Anlaşmalı Kurumlar" sayfası.
 *
 * İçerik ikinci fazda yönetim panelinden beslenecektir.
 * Şu an: üyeye sağlanacak avantaj kategorileri + kurumlar için CTA.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var array<string, mixed> $site
 */

/** @var array<string, string> $contact */
$contact = (array) ($site['contact'] ?? []);

$avantajlar = [
    [
        'ikon'     => 'target',
        'baslik'   => 'Özel İndirimler',
        'aciklama' => 'Anlaşmalı kurumlardan üyelere sunulan yüzde on ile yüzde elli arasında özel indirim fırsatları.',
    ],
    [
        'ikon'     => 'sparkles',
        'baslik'   => 'Öncelikli Hizmet',
        'aciklama' => 'Randevu ve hizmet süreçlerinde üyelerimize tanınan öncelikli sıra ve hız avantajı.',
    ],
    [
        'ikon'     => 'handshake',
        'baslik'   => 'Ek Ayrıcalıklar',
        'aciklama' => 'Kampanya dönemlerinde üyelerimize özel ekstra hediye, puan ve bonuslar.',
    ],
];

$kategoriler = [
    ['ikon' => 'heart',    'baslik' => 'Sağlık & Güzellik',       'renk' => 'bordo'],
    ['ikon' => 'book',     'baslik' => 'Eğitim & Kurs',            'renk' => 'mavi'],
    ['ikon' => 'map',      'baslik' => 'Turizm & Konaklama',        'renk' => 'bordo'],
    ['ikon' => 'landmark', 'baslik' => 'Hukuk & Mali Danışmanlık', 'renk' => 'mavi'],
    ['ikon' => 'sparkles', 'baslik' => 'Alışveriş & Market',        'renk' => 'bordo'],
    ['ikon' => 'leaf',     'baslik' => 'Yeme, İçme & Kafe',         'renk' => 'mavi'],
];
?>

<!-- ===== HERO ===== -->
<section class="da-hero da-hero--uzungol" aria-labelledby="pk-hero-baslik">
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
                    <?= $view->icon('handshake') ?>
                    Üye Avantajları
                </span>

                <h1 class="da-hero__baslik" id="pk-hero-baslik">
                    Üyelerimize özel<br>
                    <span>ayrıcalıklı</span> kurumsal anlaşmalar.
                </h1>

                <p class="da-hero__ozet">
                    Trabzonlu kamu çalışanları olarak üyelerimize sağlık, eğitim,
                    turizm ve daha birçok alanda indirim ve öncelikli hizmet sunacak
                    kurumlarla güçlü anlaşmalar kuruyoruz.
                </p>

                <div class="da-hero__eylemler">
                    <a class="dugme" href="mailto:<?= $view->e($contact['email'] ?? '') ?>">
                        <?= $view->icon('mail') ?>
                        Kurumunuzu Ekleyin
                    </a>
                    <a class="dugme dugme--hayalet" href="<?= $view->link('/hakkimizda/dernegimiz') ?>">
                        Derneğimiz Hakkında
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

<!-- ===== ÜYE AVANTAJLARI ===== -->
<section class="bolum bolum--yuzey" aria-labelledby="avantaj-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket'   => 'Üyelerimize',
    'baslik'   => 'Ne Kazanırsınız?',
    'aciklama' => 'Anlaşmalı kurumlar üzerinden üyelerimize sunulacak ayrıcalıkların özeti.',
    'ortali'   => false,
    'id'       => 'avantaj-baslik',
]) ?>

        <ul class="pk-avantaj-izgara">
<?php foreach ($avantajlar as $a): ?>
            <li class="pk-avantaj-kart belirme">
                <span class="pk-avantaj-kart__ikon" aria-hidden="true"><?= $view->icon($a['ikon']) ?></span>
                <h3><?= $view->e($a['baslik']) ?></h3>
                <p><?= $view->e($a['aciklama']) ?></p>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- ===== ANLAŞMA KATEGORİLERİ ===== -->
<section class="bolum bolum--desenli pk-kategoriler" aria-labelledby="kategori-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket'   => 'Kapsam',
    'baslik'   => 'Anlaşma',
    'vurgu'    => 'Kategorileri',
    'aciklama' => 'Aşağıdaki alanlarda kurumlarla anlaşmalar yürütülmektedir. Detaylı liste yakında yayımlanacaktır.',
    'id'       => 'kategori-baslik',
]) ?>

        <ul class="pk-kategori-izgara">
<?php foreach ($kategoriler as $k): ?>
            <li class="pk-kategori-kart belirme pk-kategori-kart--<?= $view->e($k['renk']) ?>">
                <span class="pk-kategori-kart__ikon" aria-hidden="true"><?= $view->icon($k['ikon']) ?></span>
                <h3><?= $view->e($k['baslik']) ?></h3>
                <span class="pk-kategori-kart__rozet">Yakında</span>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
</section>

<!-- ===== PARTNER CTA ===== -->
<section class="pk-cta" aria-labelledby="pk-cta-baslik">
    <span class="pk-cta__isik" aria-hidden="true"></span>

    <div class="kapsayici pk-cta__ic belirme">
        <span class="pk-cta__ikon" aria-hidden="true"><?= $view->icon('handshake') ?></span>
        <h2 class="pk-cta__baslik" id="pk-cta-baslik">
            Kurumunuzu anlaşmalı kurum listesine eklemek ister misiniz?
        </h2>
        <p class="pk-cta__ozet">
            Derneğimizle anlaşmalı kurum olmak için bize ulaşın. Üyelerimize sunacağınız
            avantajları birlikte belirleyelim.
        </p>
        <div class="pk-cta__eylemler">
            <a class="dugme" href="mailto:<?= $view->e($contact['email'] ?? '') ?>">
                <?= $view->icon('mail') ?>
                Bize Yazın
            </a>
            <a class="dugme dugme--hayalet" href="<?= $view->link('/iletisim') ?>">
                <?= $view->icon('phone') ?>
                İletişim Sayfası
            </a>
        </div>
    </div>
</section>
