<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\Announcement;

/**
 * Son duyuruların yatay olarak aktığı bilgi şeridi.
 *
 * Kesintisiz akış için liste iki kez basılır; ikinci kopya yalnızca görsel
 * süreklilik içindir ve ekran okuyuculardan gizlenir. Şerit üzerine
 * gelindiğinde veya klavye odağı alındığında animasyon durur.
 *
 * @var PhpViewRenderer $view
 * @var list<Announcement> $announcements
 */

if ($announcements === []) {
    return;
}
?>
<section class="duyuru-serit" aria-label="Son duyurular">
    <span class="duyuru-serit__etiket">
        <?= $view->icon('megaphone') ?>
        Son Duyurular
    </span>

    <div class="duyuru-serit__pencere">
        <div class="duyuru-serit__akis">
<?php foreach ([false, true] as $kopya): ?>
            <ul class="duyuru-serit__grup"<?= $kopya ? ' aria-hidden="true"' : '' ?>>
<?php foreach ($announcements as $announcement): ?>
                <li class="duyuru-serit__oge<?= $announcement->highlighted ? ' duyuru-serit__oge--vurgulu' : '' ?>">
                    <strong><?= $view->e($announcement->title) ?></strong>
                    <span><?= $view->e($announcement->summary) ?></span>
                </li>
<?php endforeach; ?>
            </ul>
<?php endforeach; ?>
        </div>
    </div>
</section>
