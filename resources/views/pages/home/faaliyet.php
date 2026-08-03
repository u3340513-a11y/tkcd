<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\ActivityArea;

/**
 * Faaliyet alanları bölümü.
 *
 * @var PhpViewRenderer $view
 * @var list<ActivityArea> $activityAreas
 */

if ($activityAreas === []) {
    return;
}
?>
<section class="bolum bolum--desenli" aria-labelledby="faaliyet-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Ne Yapıyoruz',
    'baslik' => 'Faaliyet',
    'vurgu' => 'Alanlarımız',
    'aciklama' => 'Kültür, dayanışma, sosyal sorumluluk ve hemşerilik bağını güçlendiren '
        . 'çalışmalar yürütüyoruz.',
    'id' => 'faaliyet-baslik',
]) ?>

        <div class="izgara izgara--4">
<?php foreach ($activityAreas as $area): ?>
            <article class="kart belirme">
                <span class="ikon-kutu" aria-hidden="true"><?= $view->icon($area->icon) ?></span>
                <h3 class="kart__baslik"><?= $view->e($area->title) ?></h3>
                <p class="kart__metin"><?= $view->e($area->description) ?></p>
            </article>
<?php endforeach; ?>
        </div>
    </div>
</section>
