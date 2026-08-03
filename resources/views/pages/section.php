<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * İçeriği ikinci fazda yönetim panelinden beslenecek bölüm sayfalarının
 * ortak şablonu.
 *
 * Sayfa; başlık, kırıntı navigasyonu, bilgilendirici boş durum ve iletişim
 * yönlendirmesiyle eksiksiz bir deneyim sunar.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var string $pageTitle
 * @var string $pageDescription
 * @var string $parentLabel
 * @var array<string, mixed> $site
 */

/** @var array<string, string> $contact */
$contact = (array) ($site['contact'] ?? []);
?>
<section class="sayfa-basi">
    <div class="kapsayici">
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

        <h1><?= $view->e($pageTitle) ?></h1>
        <p><?= $view->e($pageDescription) ?></p>
    </div>
</section>

<section class="bolum" aria-labelledby="bos-durum-baslik">
    <div class="kapsayici">
        <div class="bos-durum">
            <span class="bos-durum__ikon" aria-hidden="true"><?= $view->icon('clock') ?></span>
            <h2 class="baslik-3" id="bos-durum-baslik">İçerik Hazırlanıyor</h2>
            <p>
                <?= $view->e($pageTitle) ?> sayfasının içeriği kısa süre içinde yayımlanacaktır.
                Bu süre zarfında derneğimizle ilgili tüm gelişmeleri ana sayfamızdan ve sosyal
                medya hesaplarımızdan takip edebilirsiniz.
            </p>
            <div class="bos-durum__eylemler">
                <a class="dugme" href="/">
                    Ana Sayfaya Dön
                    <?= $view->icon('arrow-right') ?>
                </a>
                <a class="dugme dugme--ikincil" href="mailto:<?= $view->e($contact['email'] ?? '') ?>">
                    <?= $view->icon('mail') ?>
                    Bize Yazın
                </a>
            </div>
        </div>
    </div>
</section>
