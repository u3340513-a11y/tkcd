<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Navigation\NavigationItem;

/**
 * Site alt bilgisi: kurumsal kimlik, hızlı erişim, iletişim ve sosyal medya.
 *
 * @var PhpViewRenderer $view
 * @var list<NavigationItem> $navigation
 * @var list<array<string, string>> $socials
 * @var array<string, mixed> $site
 * @var int $currentYear
 */

/** @var array<string, string> $contact */
$contact = (array) ($site['contact'] ?? []);
$siteName = (string) ($site['name'] ?? '');

/** @var list<NavigationItem> $aboutLinks */
$aboutLinks = [];

foreach ($navigation as $item) {
    if ($item->hasChildren()) {
        $aboutLinks = $item->children;

        break;
    }
}
?>
<footer class="site-alti">
    <div class="kapsayici">
        <div class="site-alti__izgara">
            <div>
                <div class="site-alti__marka">
                    <span class="site-alti__rozet">
                        <img src="<?= $view->e($view->asset('assets/img/logo.webp')) ?>"
                             alt="<?= $view->e($siteName) ?> logosu" width="52" height="52" loading="lazy">
                    </span>
                    <span class="site-alti__marka-yazi">
                        <strong>Trabzonlu Kamu<br>Çalışanları Derneği</strong>
                        <span class="site-alti__marka-not">
                            <?= $view->e((string) ($site['legal_note'] ?? '')) ?>
                        </span>
                    </span>
                </div>
                <p><?= $view->e((string) ($site['description'] ?? '')) ?></p>

                <div class="sosyal-liste sosyal-liste--aralikli">
<?php foreach ($socials as $social): ?>
                    <a class="sosyal-dugme sosyal-dugme--ters"
                       href="<?= $view->link($social['url'] ?? '') ?>"
                       target="_blank" rel="noopener noreferrer"
                       aria-label="<?= $view->e($social['label'] ?? '') ?>">
                        <?= $view->icon((string) ($social['icon'] ?? '')) ?>
                    </a>
<?php endforeach; ?>
                </div>
            </div>

            <nav aria-labelledby="alt-bilgi-menu">
                <h2 class="site-alti__baslik" id="alt-bilgi-menu">Hızlı Erişim</h2>
                <ul class="site-alti__liste">
<?php foreach ($navigation as $item): ?>
                    <li><a href="<?= $view->link($item->path) ?>"><?= $view->e($item->label) ?></a></li>
<?php endforeach; ?>
                    <li><a href="/uye-ol">Üye Ol</a></li>
                </ul>
            </nav>

            <nav aria-labelledby="alt-bilgi-kurumsal">
                <h2 class="site-alti__baslik" id="alt-bilgi-kurumsal">Kurumsal</h2>
                <ul class="site-alti__liste">
<?php foreach ($aboutLinks as $child): ?>
                    <li><a href="<?= $view->link($child->path) ?>"><?= $view->e($child->label) ?></a></li>
<?php endforeach; ?>
                </ul>
            </nav>

            <div>
                <h2 class="site-alti__baslik">İletişim</h2>
                <p class="iletisim-satiri">
                    <?= $view->icon('navigation') ?>
                    <span><?= $view->e($contact['address'] ?? '') ?></span>
                </p>
                <p class="iletisim-satiri">
                    <?= $view->icon('mail') ?>
                    <a href="mailto:<?= $view->e($contact['email'] ?? '') ?>"><?= $view->e($contact['email'] ?? '') ?></a>
                </p>
                <p class="iletisim-satiri">
                    <?= $view->icon('phone') ?>
                    <a href="tel:<?= $view->e($contact['phone_e164'] ?? '') ?>"><?= $view->e($contact['phone'] ?? '') ?></a>
                </p>
            </div>
        </div>

        <div class="site-alti__cubuk">
            <span>&copy; <?= (int) $currentYear ?> <?= $view->e($siteName) ?>. Tüm hakları saklıdır.</span>
            <span><?= $view->e((string) ($site['legal_note'] ?? '')) ?></span>
            <span class="site-alti__tesekkur">
                Bu siteyi ilk yapana sonsuz teşekkürler:
                <a href="https://kodwall.com/" target="_blank" rel="noopener noreferrer">kodwall.com</a>
            </span>
        </div>
    </div>
</footer>
