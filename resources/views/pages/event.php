<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\Event;

/**
 * Etkinlik / haber detay sayfası.
 *
 * @var PhpViewRenderer $view
 * @var Event $event
 */
?>
<section class="sayfa-basi">
    <div class="kapsayici">
        <nav class="kirinti" aria-label="Site haritası">
            <a href="/">Ana Sayfa</a>
            <span class="kirinti__ayrac" aria-hidden="true">/</span>
            <a href="/duyurular">Duyurular</a>
            <span class="kirinti__ayrac" aria-hidden="true">/</span>
            <span aria-current="page"><?= $view->e($event->title) ?></span>
        </nav>

        <h1><?= $view->e($event->title) ?></h1>
        <p><?= $view->e($event->summary) ?></p>
    </div>
</section>

<article class="bolum">
    <div class="kapsayici kapsayici--dar">
        <p class="meta-satiri">
            <?= $view->icon('calendar') ?>
            <time datetime="<?= $view->e($event->publishedAt) ?>">
                <?= $view->e($view->date($event->publishedAt)) ?>
            </time>
            <span class="rozet rozet--mavi"><?= $view->e($event->category) ?></span>
        </p>

        <div class="makale-gorsel">
<?= $view->partial('components/gorsel', [
    'src' => $event->image,
    'alt' => $event->imageAlt,
    'yedekIkon' => $event->icon,
    'yukleme' => 'eager',
]) ?>
        </div>

        <div class="makale-govde">
<?php foreach ($event->body as $paragraph): ?>
            <p><?= $view->e($paragraph) ?></p>
<?php endforeach; ?>
        </div>

        <p class="makale-alt">
            <a class="ok-baglanti" href="/duyurular">
                Tüm duyurulara dön
                <?= $view->icon('arrow-right') ?>
            </a>
        </p>
    </div>
</article>
