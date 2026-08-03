<?php

declare(strict_types=1);

use App\Application\ViewModel\HomePageViewModel;
use App\Core\View\PhpViewRenderer;

/**
 * Anasayfa.
 *
 * @var PhpViewRenderer $view
 * @var HomePageViewModel $model
 * @var array<string, mixed> $site
 * @var list<array<string, string>> $socials
 */

/** @var array<string, string> $sosyalHaritasi */
$sosyalHaritasi = [];

foreach ($socials as $social) {
    $sosyalHaritasi[(string) ($social['key'] ?? '')] = (string) ($social['url'] ?? '');
}

$youtubeUrl = $sosyalHaritasi['youtube'] ?? '#';
$uyelikUrl = (string) ($site['membership_form_url'] ?? '/uye-ol');
$tanitimVideoId = (string) ($site['promo_video_id'] ?? '');
?>
<div class="kahraman-alani">
    <?= $view->partial('pages/home/kahraman', ['model' => $model, 'uyelikUrl' => $uyelikUrl]) ?>
    <?= $view->partial('pages/home/duyuru-serit', ['announcements' => $model->announcements]) ?>
</div>
<?= $view->partial('pages/home/medya', ['youtubeUrl' => $youtubeUrl, 'videoId' => $tanitimVideoId]) ?>
<?= $view->partial('pages/home/hakkinda') ?>
<?= $view->partial('pages/home/faaliyet', ['activityAreas' => $model->activityAreas]) ?>
<?= $view->partial('pages/home/etkinlikler', ['events' => $model->events]) ?>
<?= $view->partial('pages/home/ilceler', ['districts' => $model->districts]) ?>
<?= $view->partial('pages/home/tarihce', ['milestones' => $model->milestones]) ?>
<?= $view->partial('pages/home/uyelik', ['uyelikUrl' => $uyelikUrl]) ?>
<?= $view->partial('pages/home/sosyal') ?>
