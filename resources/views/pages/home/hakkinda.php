<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Hakkımızda özeti: derneğin amacı ve temel değerleri.
 *
 * @var PhpViewRenderer $view
 */
?>
<section class="bolum bolum--yuzey" id="hakkimizda" aria-labelledby="hakkinda-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Hakkımızda',
    'baslik' => 'Ortak Kültür ve',
    'vurgu' => 'Dayanışma Çatısı',
    'aciklama' => 'Trabzonlu kamu çalışanlarını ortak kültür ve dayanışma çatısı altında '
        . 'buluşturuyor, hemşerilik bağını her geçen gün güçlendiriyoruz.',
    'id' => 'hakkinda-baslik',
]) ?>

        <div class="hakkinda__izgara">
            <div class="hakkinda__gorsel belirme">
<?= $view->partial('components/gorsel', [
    'src' => '/assets/img/trabzon-kamu-masa.webp',
    'alt' => 'Trabzon kamu çalışanları dayanışma masası etkinliğinden bir kare',
    'yukleme' => 'lazy',
    'yedekIkon' => 'landmark',
]) ?>
            </div>

            <div class="hakkinda__panel belirme">
                <span class="etiket">Amacımız</span>
                <h3 class="baslik-3">Birlikte Üretmek, Birlikte Yaşatmak</h3>
                <p class="aciklama">
                    Derneğimiz; aile huzurunu gözeten Trabzonlu kamu çalışanlarının kaynaşmasını
                    sağlamak, şehrimizin gelenek ve göreneklerini yaşatmak ve üyelerimizin sosyal
                    yaşamını güçlendirmek için faaliyet yürütür.
                </p>

                <div class="hakkinda__ikili">
                    <article class="mini-kart">
                        <span class="mini-kart__ikon" aria-hidden="true"><?= $view->icon('handshake') ?></span>
                        <h4>Birlik &amp; Beraberlik</h4>
                        <p>Hemşerilerimiz arasında dayanışma, destek ve paylaşım.</p>
                    </article>

                    <article class="mini-kart">
                        <span class="mini-kart__ikon" aria-hidden="true"><?= $view->icon('landmark') ?></span>
                        <h4>Kültürü Yaşatmak</h4>
                        <p>Gelenekten geleceğe uzanan Trabzon değerleri.</p>
                    </article>
                </div>

                <p class="hakkinda__baglanti">
                    <a class="ok-baglanti" href="/hakkimizda/dernegimiz">
                        Derneğimiz hakkında daha fazlası
                        <?= $view->icon('arrow-right') ?>
                    </a>
                </p>
            </div>
        </div>
    </div>
</section>
