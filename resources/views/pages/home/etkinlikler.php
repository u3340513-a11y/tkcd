<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\Event;

/**
 * Etkinlikler ve haberler bölümü.
 *
 * @var PhpViewRenderer $view
 * @var list<Event> $events
 */

if ($events === []) {
    return;
}
?>
<section class="bolum bolum--yuzey" aria-labelledby="etkinlik-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Güncel',
    'baslik' => 'Etkinlikler &',
    'vurgu' => 'Haberler',
    'aciklama' => 'Derneğimizin düzenlediği etkinlikler, sosyal faaliyetler ve güncel '
        . 'duyurular hakkındaki son gelişmeleri buradan takip edebilirsiniz.',
    'id' => 'etkinlik-baslik',
]) ?>

        <div class="izgara izgara--3">
<?php foreach ($events as $event): ?>
            <article class="etkinlik-kart belirme">
                <div class="etkinlik-kart__medya">
<?= $view->partial('components/gorsel', [
    'src' => $event->image,
    'alt' => $event->imageAlt,
    'yedekIkon' => $event->icon,
]) ?>
                    <span class="etkinlik-kart__rozet"><?= $view->e($event->category) ?></span>
                </div>

                <div class="etkinlik-kart__govde">
                    <span class="etkinlik-kart__tarih">
                        <?= $view->icon('calendar') ?>
                        <time datetime="<?= $view->e($event->publishedAt) ?>">
                            <?= $view->e($view->date($event->publishedAt)) ?>
                        </time>
                    </span>

                    <h3><?= $view->e($event->title) ?></h3>
                    <p><?= $view->e($event->summary) ?></p>

                    <div class="etkinlik-kart__alt">
                        <a class="ok-baglanti" href="<?= $view->link($event->url()) ?>">
                            Detaylar
                            <span class="gorsel-gizli">: <?= $view->e($event->title) ?></span>
                            <?= $view->icon('arrow-right') ?>
                        </a>
                    </div>
                </div>
            </article>
<?php endforeach; ?>
        </div>
    </div>
</section>
