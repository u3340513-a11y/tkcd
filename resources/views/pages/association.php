<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * "Derneğimiz" sayfası — Bölümler:
 *   1. Hero          : Tam ekran degradeli giriş, breadcrumb, aksiyon düğmeleri
 *   2. Kimliğimiz    : Sol büyük tipografi + sağ numaralı değer kartları
 *   3. Misyon/Vizyon : Full-bleed markalı bölüm, asimetrik düzen
 *   4. Amaç          : Manifesto stili alıntı bloğu
 *   5. Faaliyetler   : Sol ikon listesi + sağ mozaik galeri
 *   6. Rakamlar      : Karanlık markalı sayaç şeridi
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var array<string, mixed> $site
 */

$degerler = [
    [
        'no'       => '01',
        'ikon'     => 'megaphone',
        'baslik'   => 'Şeffaflık',
        'aciklama' => 'Bilgilendirme ve duyurular düzenli, açık biçimde üyelerle paylaşılır.',
    ],
    [
        'no'       => '02',
        'ikon'     => 'shield',
        'baslik'   => 'Güven & KVKK',
        'aciklama' => 'Üye verileri yalnızca dernek amaçları doğrultusunda işlenir.',
    ],
    [
        'no'       => '03',
        'ikon'     => 'hand-heart',
        'baslik'   => 'Dayanışma',
        'aciklama' => 'Üyeler arası güçlü bağlar kurulur, sosyal destek ağı büyütülür.',
    ],
    [
        'no'       => '04',
        'ikon'     => 'phone',
        'baslik'   => 'Kolay İletişim',
        'aciklama' => 'Form, WhatsApp ve sosyal medya üzerinden anında ulaşılabilirlik.',
    ],
];

$faaliyetler = [
    [
        'ikon'     => 'horon',
        'baslik'   => 'Horon Eğitimi',
        'aciklama' => 'Horon geleneğini yaşatarak Karadeniz kültürünü geleceğe taşıyoruz.',
    ],
    [
        'ikon'     => 'sparkles',
        'baslik'   => 'Kültür Geceleri',
        'aciklama' => 'Trabzon müziği, yemekleri ve geleneklerini canlı tutuyoruz.',
    ],
    [
        'ikon'     => 'leaf',
        'baslik'   => 'Piknik & Doğa',
        'aciklama' => 'Üyelerimizi ve ailelerini bir araya getiren sosyal etkinlikler.',
    ],
    [
        'ikon'     => 'map-pin',
        'baslik'   => 'Gezi & Buluşma',
        'aciklama' => 'Yurt içi ve Avrupa hemşeri buluşmaları, kültür gezileri.',
    ],
    [
        'ikon'     => 'heart',
        'baslik'   => 'Sosyal Dayanışma',
        'aciklama' => 'İhtiyaç sahibi üyelere ve ailelerine destek projeleri.',
    ],
];

$sayilar = [
    ['ikon' => 'users',    'deger' => '650+', 'etiket' => 'Mutlu Üye'],
    ['ikon' => 'calendar', 'deger' => '40+',  'etiket' => 'Etkinlik'],
    ['ikon' => 'heart',    'deger' => '15+',  'etiket' => 'Yardım Projesi'],
];
?>

<!-- ===== 1. HERO ===== -->
<section class="da-hero" aria-labelledby="da-hero-baslik">
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

        <div class="da-hero__govde">
            <div class="da-hero__metin belirme">
                <span class="da-hero__rozet">
                    <?= $view->icon('users') ?>
                    <?= $view->e((string) ($site['founded_year'] ?? '2025')) ?> Kuruluşu
                </span>

                <h1 class="da-hero__baslik" id="da-hero-baslik">
                    Trabzonlu kamu çalışanlarının<br>
                    <span>dayanışma</span> ve kültür yuvası.
                </h1>

                <p class="da-hero__ozet">
                    Kültürel değerlerimizi yaşatan, güçlü hemşerilik bağları kuran ve
                    sosyal dayanışmayı büyüten bir dernek olarak Trabzon'un sesini
                    Türkiye ve Avrupa'da yükseltiyoruz.
                </p>

                <div class="da-hero__eylemler">
                    <a class="dugme" href="/uye-ol">
                        <?= $view->icon('users') ?>
                        Üye Ol
                    </a>
                    <a class="dugme dugme--hayalet" href="#kimligimiz">
                        Daha Fazlası
                        <?= $view->icon('arrow-right') ?>
                    </a>
                </div>
            </div>

            <aside class="da-hero__kart belirme" aria-hidden="true">
                <dl class="da-hero__ozet-kutu">
                    <div>
                        <dt><?= $view->icon('map-pin') ?></dt>
                        <dd>81 İl Temsilciliği</dd>
                    </div>
                    <div>
                        <dt><?= $view->icon('globe') ?></dt>
                        <dd>Avrupa'da 18 Nokta</dd>
                    </div>
                    <div>
                        <dt><?= $view->icon('users') ?></dt>
                        <dd>650+ Üye</dd>
                    </div>
                    <div>
                        <dt><?= $view->icon('heart') ?></dt>
                        <dd>15+ Yardım Projesi</dd>
                    </div>
                </dl>
            </aside>
        </div>
    </div>

    <svg class="da-hero__dalga" viewBox="0 0 1440 90" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 54c200-36 400-36 600 0s400 36 600 0 200-36 400 0v36H0z" fill="currentColor" opacity=".5"/>
        <path d="M0 72c240-30 480-12 720 6s480 30 720-6v18H0z" fill="currentColor" opacity=".3"/>
    </svg>
</section>

<!-- ===== 2. KİMLİĞİMİZ ===== -->
<section class="bolum bolum--yuzey da-kimlik" id="kimligimiz" aria-labelledby="kimlik-baslik">
    <div class="kapsayici da-kimlik__izgara">

        <div class="da-kimlik__sol belirme">
            <span class="etiket">Biz Kimiz?</span>
            <h2 class="da-kimlik__baslik" id="kimlik-baslik">
                Trabzon ruhuyla<br>
                <span class="baslik-vurgu">birleşen</span><br>kamu çalışanları.
            </h2>
            <div class="nokta-ayrac" aria-hidden="true"><span></span><span></span><span></span></div>
            <p class="aciklama">
                Trabzonlu kamu çalışanlarını aynı çatı altında buluşturuyor; kültürel
                değerlerimizi yaşatan, üyelerimiz arasında güçlü bağlar kuran ve
                dayanışmayı büyüten çalışmalar yürütüyoruz.
            </p>
            <p class="aciklama">
                Sosyal, kültürel ve mesleki faaliyetlerde yan yana gelerek birlikte
                daha güçlü bir topluluk oluşturuyoruz.
            </p>
            <a class="ok-baglanti" href="#misyon" style="margin-top:var(--bosluk-5);display:inline-flex;">
                Misyonumuzu keşfedin
                <?= $view->icon('arrow-right') ?>
            </a>
        </div>

        <ul class="da-deger-izgara">
<?php foreach ($degerler as $deger): ?>
            <li class="da-deger-kart belirme" data-no="<?= $view->e($deger['no']) ?>">
                <span class="da-deger-kart__ikon" aria-hidden="true"><?= $view->icon($deger['ikon']) ?></span>
                <h3><?= $view->e($deger['baslik']) ?></h3>
                <p><?= $view->e($deger['aciklama']) ?></p>
            </li>
<?php endforeach; ?>
        </ul>

    </div>
</section>

<!-- ===== 3. MİSYON / VİZYON ===== -->
<section class="da-mv" id="misyon" aria-labelledby="misyon-baslik">
    <span class="da-mv__isik" aria-hidden="true"></span>

    <div class="kapsayici da-mv__izgara">

        <article class="da-mv-kutu da-mv-kutu--ana belirme">
            <span class="da-mv-kutu__no" aria-hidden="true">01</span>
            <span class="da-mv-kutu__etiket"><?= $view->icon('target') ?> Misyonumuz</span>
            <h2 class="da-mv-kutu__baslik" id="misyon-baslik">
                Güçlü bağlar kurmak, kültürü yaşatmak.
            </h2>
            <p>
                Türkiye'de ve Avrupa'da yaşayan Trabzonlu kamu çalışanlarını aynı çatı altında
                buluşturmak; sosyal, kültürel ve mesleki köprüler kurarak dayanışmayı
                güçlendiren çalışmalar yürütmek.
            </p>
        </article>

        <article class="da-mv-kutu da-mv-kutu--ikincil belirme">
            <span class="da-mv-kutu__no" aria-hidden="true">02</span>
            <span class="da-mv-kutu__etiket"><?= $view->icon('rocket') ?> Vizyonumuz</span>
            <h2 class="da-mv-kutu__baslik">
                Türkiye ve Avrupa'nın güçlü hemşeri sesi.
            </h2>
            <p>
                Türkiye genelinde ve Avrupa'da kamu çalışanlarının güçlü, görünür ve saygın
                sesi olmak; birlik, sürdürülebilirlik ve sivil toplum katılımını büyütmek.
            </p>
        </article>

    </div>
</section>

<!-- ===== 4. TEMEL AMAÇ — MANİFESTO ===== -->
<section class="da-amac bolum--yuzey" aria-labelledby="amac-baslik">
    <div class="kapsayici kapsayici--dar da-amac__ic belirme">
        <span class="da-amac__tirnak" aria-hidden="true">"</span>
        <span class="etiket" style="justify-content:center;">Temel Amaç ve Gayemiz</span>
        <h2 class="da-amac__metin" id="amac-baslik">
            Trabzonlu kamu çalışanlarını birleştirmek, <strong>kültürel değerleri yaşatmak</strong>,
            güçlü bir dayanışma ağı kurmak ve ortak değerler etrafında iş birliği yaparak
            <strong>toplumsal gelişime katkı sunmak</strong>.
        </h2>
        <a class="dugme" href="/uye-ol" style="margin-top:var(--bosluk-7);">
            <?= $view->icon('users') ?>
            Aramıza Katılın
        </a>
    </div>
</section>

<!-- ===== 5. FAALİYETLER + GALERİ ===== -->
<section class="bolum bolum--desenli da-faaliyet" aria-labelledby="faaliyet-baslik">
    <div class="kapsayici da-faaliyet__izgara">

        <div class="da-faaliyet__sol">
<?= $view->partial('components/bolum-basligi', [
    'etiket'   => 'Ne Yapıyoruz?',
    'baslik'   => 'Faaliyet',
    'vurgu'    => 'Alanlarımız',
    'aciklama' => 'Kültür, sosyal buluşmalar ve dayanışma projeleriyle her zaman yanınızdayız.',
    'id'       => 'faaliyet-baslik',
]) ?>

            <ul class="da-faaliyet-liste">
<?php foreach ($faaliyetler as $f): ?>
                <li class="da-faaliyet-item belirme">
                    <span class="da-faaliyet-item__ikon" aria-hidden="true"><?= $view->icon($f['ikon']) ?></span>
                    <div>
                        <h3><?= $view->e($f['baslik']) ?></h3>
                        <p><?= $view->e($f['aciklama']) ?></p>
                    </div>
                </li>
<?php endforeach; ?>
            </ul>
        </div>

        <div class="da-galeri belirme" aria-label="Faaliyetlerimizden kareler">
            <p class="da-galeri__baslik">Faaliyetlerimizden Kareler</p>
            <ul class="da-galeri-mozaik">
<?php
/** @var list<array{dosya:string,alt:string,boyut:'buyuk'|'normal'}> $galeriVerisi */
$galeriVerisi    = require dirname(__DIR__, 3) . '/resources/data/gallery.php';
$galeriGorseller = array_slice($galeriVerisi, 0, 6);
foreach ($galeriGorseller as $gorsel):
    $src   = '/assets/img/' . $gorsel['dosya'];
    $buyuk = ($gorsel['boyut'] === 'buyuk');
?>
                <li class="da-galeri-mozaik__oge<?= $buyuk ? ' da-galeri-mozaik__oge--buyuk' : '' ?>">
<?= $view->partial('components/gorsel', [
    'src'       => $src,
    'alt'       => $gorsel['alt'],
    'yedekIkon' => 'camera',
]) ?>
                </li>
<?php endforeach; ?>
            </ul>
            <a class="ok-baglanti da-galeri__link" href="<?= $view->link('/hakkimizda/galeri') ?>">
                Tüm Galeriyi Gör
                <?= $view->icon('arrow-right') ?>
            </a>
        </div>

    </div>
</section>

<!-- ===== 6. RAKAMLAR ===== -->
<section class="da-rakamlar" aria-label="Derneğimiz rakamlarla">
    <span class="da-rakamlar__isik" aria-hidden="true"></span>
    <div class="kapsayici da-rakamlar__izgara">
<?php foreach ($sayilar as $sayi): ?>
        <div class="da-rakamlar__oge belirme">
            <span class="da-rakamlar__ikon" aria-hidden="true"><?= $view->icon($sayi['ikon']) ?></span>
            <strong class="da-rakamlar__deger"><?= $view->e($sayi['deger']) ?></strong>
            <span class="da-rakamlar__etiket"><?= $view->e($sayi['etiket']) ?></span>
        </div>
<?php endforeach; ?>
    </div>
</section>
