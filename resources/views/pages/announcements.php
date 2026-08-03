<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Duyurular & Haberler sayfası.
 *
 * Bölümler:
 *   1. Hero        : Kısa başlık şeridi
 *   2. Filtre      : Kategori sekmeleri (JS — attribute-based)
 *   3. Liste       : Tarih rozetli, kategorili duyuru kartları
 *   4. Sosyal CTA  : Sosyal medya takip çağrısı
 *
 * @var PhpViewRenderer                                        $view
 * @var SeoMeta                                                $seo
 * @var array<string, mixed>                                   $site
 * @var list<array<string, string>>                            $socials
 * @var list<array{slug:string,title:string,summary:string,category:string,published_at:string,highlighted:bool}> $duyurular
 */

/** @var array<string, array{etiket: string, renk: string}> */
$kategoriMap = [
    'duyuru'   => ['etiket' => 'Duyuru',   'renk' => 'bordo'],
    'etkinlik' => ['etiket' => 'Etkinlik', 'renk' => 'mavi'],
    'rapor'    => ['etiket' => 'Rapor',    'renk' => 'nötr'],
];

/** @var array<int, string> */
$aylar = [
    1  => 'Oca', 2  => 'Şub', 3  => 'Mar', 4  => 'Nis',
    5  => 'May', 6  => 'Haz', 7  => 'Tem', 8  => 'Ağu',
    9  => 'Eyl', 10 => 'Eki', 11 => 'Kas', 12 => 'Ara',
];

/** @var array<int, string> */
$aylarTam = [
    1  => 'Ocak',   2  => 'Şubat',   3  => 'Mart',    4  => 'Nisan',
    5  => 'Mayıs', 6  => 'Haziran',  7  => 'Temmuz',  8  => 'Ağustos',
    9  => 'Eylül', 10 => 'Ekim',    11 => 'Kasım',   12 => 'Aralık',
];

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="dy-hero" aria-labelledby="dy-hero-baslik">
    <div class="dy-hero__arka" aria-hidden="true"></div>

    <div class="kapsayici dy-hero__ic">
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

        <h1 class="dy-hero__baslik belirme" id="dy-hero-baslik">
            Duyurular
        </h1>
        <p class="dy-hero__alt belirme">
            Derneğimize ait tüm duyurular, etkinlik bildirileri ve yıllık
            raporlar bu sayfada yayımlanır.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. FİLTRE + LİSTE                                   ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="dy-liste-bolum" aria-label="Duyuru listesi">
    <div class="kapsayici dy-liste-ic">

        <!-- Filtre sekmeleri -->
        <nav class="dy-filtre" role="tablist" aria-label="Kategori filtresi">
            <button class="dy-filtre__btn dy-filtre__btn--aktif"
                    role="tab" aria-selected="true"
                    data-filtre="tumu">Tümü</button>
            <button class="dy-filtre__btn"
                    role="tab" aria-selected="false"
                    data-filtre="duyuru">Duyuru</button>
            <button class="dy-filtre__btn"
                    role="tab" aria-selected="false"
                    data-filtre="etkinlik">Etkinlik</button>
            <button class="dy-filtre__btn"
                    role="tab" aria-selected="false"
                    data-filtre="rapor">Rapor</button>
        </nav>

        <!-- Liste -->
        <ol class="dy-liste" id="dy-liste">
            <?php foreach ($duyurular as $duyuru):
                $tarih   = \DateTimeImmutable::createFromFormat('Y-m-d', $duyuru['published_at']);
                $gun     = $tarih ? (int) $tarih->format('j') : 0;
                $ayNo    = $tarih ? (int) $tarih->format('n') : 0;
                $yil     = $tarih ? (int) $tarih->format('Y') : 0;
                $ayKisa  = $aylar[$ayNo] ?? '';
                $ayTam   = $aylarTam[$ayNo] ?? '';
                $kat     = $kategoriMap[$duyuru['category']] ?? ['etiket' => $duyuru['category'], 'renk' => 'nötr'];
                $tarihLabel = $gun . ' ' . $ayTam . ' ' . $yil;
            ?>
            <li class="dy-karti" data-kategori="<?= $view->e($duyuru['category']) ?>">
                <div class="dy-karti__tarih" aria-label="<?= $view->e($tarihLabel) ?>">
                    <span class="dy-karti__gun"><?= $gun ?></span>
                    <span class="dy-karti__ay"><?= $view->e(mb_strtoupper($ayKisa)) ?></span>
                    <span class="dy-karti__yil"><?= $yil ?></span>
                </div>

                <div class="dy-karti__govde">
                    <span class="dy-karti__kategori dy-karti__kategori--<?= $view->e($kat['renk']) ?>">
                        <?= $view->e($kat['etiket']) ?>
                    </span>
                    <h2 class="dy-karti__baslik"><?= $view->e($duyuru['title']) ?></h2>
                    <p class="dy-karti__ozet"><?= $view->e($duyuru['summary']) ?></p>
                </div>

                <?php if (!empty($duyuru['highlighted'])): ?>
                <span class="dy-karti__vurgu" aria-label="Öne çıkan duyuru"></span>
                <?php endif; ?>
            </li>
            <?php endforeach; ?>
        </ol>

        <!-- Boş durum (JS tarafından yönetilir) -->
        <p class="dy-bos-durum" id="dy-bos-durum" hidden aria-live="polite">
            Bu kategoride henüz duyuru bulunmamaktadır.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  3. SOSYAL MEDYA CTA                                 ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="dy-sosyal" aria-labelledby="dy-sosyal-baslik">
    <div class="kapsayici dy-sosyal__ic">
        <div class="dy-sosyal__metin belirme">
            <span class="dy-sosyal__etiket">
                <?= $view->icon('bell') ?> Güncel Kal
            </span>
            <h2 class="dy-sosyal__baslik" id="dy-sosyal-baslik">
                Bizi Sosyal Medyadan Takip Edin
            </h2>
            <p class="dy-sosyal__aciklama">
                Etkinliklerimizden, duyurularımızdan ve derneğimizden en güncel
                paylaşımları sosyal medya hesaplarımızdan takip edebilirsiniz.
            </p>
        </div>

        <ul class="dy-sosyal__liste" aria-label="Sosyal medya hesapları">
            <?php foreach ($socials as $kanal): ?>
            <li>
                <a class="dy-sosyal__link dy-sosyal__link--<?= $view->e($kanal['key']) ?>"
                   href="<?= $view->e($kanal['url']) ?>"
                   target="_blank"
                   rel="noopener noreferrer"
                   aria-label="<?= $view->e($kanal['label']) ?> hesabımıza git">
                    <?= $view->icon($kanal['icon']) ?>
                    <span><?= $view->e($kanal['label']) ?></span>
                </a>
            </li>
            <?php endforeach; ?>
        </ul>
    </div>
</section>

<script>
(function () {
    'use strict';

    const liste    = document.getElementById('dy-liste');
    const bos      = document.getElementById('dy-bos-durum');
    const butonlar = document.querySelectorAll('.dy-filtre__btn');

    function filtrele(kategori) {
        if (!liste) return;

        let gorunenSayisi = 0;

        liste.querySelectorAll('.dy-karti').forEach(function (karti) {
            const eslesme = kategori === 'tumu' || karti.dataset.kategori === kategori;
            karti.hidden = !eslesme;
            if (eslesme) gorunenSayisi++;
        });

        if (bos) bos.hidden = gorunenSayisi > 0;
    }

    butonlar.forEach(function (btn) {
        btn.addEventListener('click', function () {
            butonlar.forEach(function (b) {
                b.classList.remove('dy-filtre__btn--aktif');
                b.setAttribute('aria-selected', 'false');
            });
            btn.classList.add('dy-filtre__btn--aktif');
            btn.setAttribute('aria-selected', 'true');
            filtrele(btn.dataset.filtre || 'tumu');
        });
    });
}());
</script>
