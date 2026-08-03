<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Sosyal medya takip çağrısı.
 *
 * @var PhpViewRenderer $view
 * @var list<array<string, string>> $socials
 */
?>
<section class="bolum bolum--desenli" aria-labelledby="sosyal-baslik">
    <div class="kapsayici sosyal-cagri__izgara">
        <div>
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Güncel Kal',
    'baslik' => 'Bizi Sosyal Medyadan',
    'vurgu' => 'Takip Edin',
    'aciklama' => 'Etkinliklerimizden, duyurularımızdan ve derneğimizden en güncel '
        . 'paylaşımları sosyal medya hesaplarımızdan takip edebilirsiniz.',
    'id' => 'sosyal-baslik',
]) ?>
        </div>

        <ul class="sosyal-kutu-izgara">
<?php foreach ($socials as $social): ?>
            <li>
                <a class="sosyal-kutu" href="<?= $view->link($social['url'] ?? '') ?>"
                   target="_blank" rel="noopener noreferrer">
                    <?= $view->icon((string) ($social['icon'] ?? '')) ?>
                    <span><?= $view->e($social['label'] ?? '') ?></span>
                </a>
            </li>
<?php endforeach; ?>
        </ul>
    </div>
</section>
