<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\Milestone;

/**
 * Trabzon'un tarihçesi bölümü (kronolojik zaman çizelgesi).
 *
 * @var PhpViewRenderer $view
 * @var list<Milestone> $milestones
 */

if ($milestones === []) {
    return;
}
?>
<section class="bolum bolum--yuzey" aria-labelledby="tarihce-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Köklerimiz',
    'baslik' => 'Trabzon’un',
    'vurgu' => 'Tarihçesi',
    'aciklama' => 'Karadeniz’in en köklü şehirlerinden biri olan Trabzon, binlerce yıllık '
        . 'ticaret, kültür ve denizcilik mirasını bugüne taşıyor.',
    'ortali' => true,
    'id' => 'tarihce-baslik',
]) ?>

        <ol class="zaman-cizelgesi">
<?php foreach ($milestones as $milestone): ?>
            <li class="zaman-durak belirme">
                <span class="zaman-durak__donem"><?= $view->e($milestone->period) ?></span>
                <h3><?= $view->e($milestone->title) ?></h3>
                <p><?= $view->e($milestone->description) ?></p>
            </li>
<?php endforeach; ?>
        </ol>
    </div>
</section>
