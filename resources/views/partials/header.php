<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Navigation\NavigationItem;

/**
 * Site başlığı: marka, masaüstü menüsü, üyelik çağrısı ve mobil menü düğmesi.
 *
 * @var PhpViewRenderer $view
 * @var list<NavigationItem> $navigation
 * @var array<string, mixed> $site
 */

$membershipUrl = (string) ($site['membership_form_url'] ?? '/uye-ol');
?>
<header class="site-basligi" data-site-basligi>
    <div class="kapsayici site-basligi__ic">
        <a class="marka" href="/" aria-label="<?= $view->e((string) ($site['name'] ?? '')) ?> — Ana sayfa">
            <img src="<?= $view->e($view->asset('assets/img/logo.webp')) ?>"
                 alt="<?= $view->e((string) ($site['name'] ?? '')) ?> logosu"
                 width="54" height="54" fetchpriority="high">
            <span class="marka__yazi">
                <span class="marka__ad">Trabzonlu Kamu Çalışanları</span>
                <span class="marka__alt">Derneği</span>
            </span>
        </a>

        <nav class="ana-menu" aria-label="Ana menü">
            <ul class="ana-menu__liste">
<?php foreach ($navigation as $item): ?>
<?php if ($item->hasChildren()): ?>
                <li class="ana-menu__oge">
                    <!-- Alt kırılımı olan başlıklar bağlantı değildir; yalnızca
                         alt menüyü açar. tabindex ile klavye odağına alınır ki
                         :focus-within kuralı alt menüyü görünür yapabilsin. -->
                    <span class="ana-menu__baglanti ana-menu__baglanti--grup<?= $item->isActiveBranch() ? ' aktif' : '' ?>" tabindex="0">
                        <?= $view->e($item->label) ?>
                        <?= $view->icon('chevron-down', 'icon ana-menu__ok') ?>
                    </span>
                    <div class="alt-menu">
<?php foreach ($item->children as $child): ?>
                        <a class="alt-menu__baglanti" href="<?= $view->link($child->path) ?>"
                           <?= $child->active ? 'aria-current="page"' : '' ?>>
                            <?= $view->e($child->label) ?>
                        </a>
<?php endforeach; ?>
                    </div>
                </li>
<?php else: ?>
                <li class="ana-menu__oge">
                    <a class="ana-menu__baglanti<?= $item->active ? ' aktif' : '' ?>"
                       href="<?= $view->link($item->path) ?>"
                       <?= $item->active ? 'aria-current="page"' : '' ?>>
                        <?= $view->e($item->label) ?>
                    </a>
                </li>
<?php endif; ?>
<?php endforeach; ?>
            </ul>
        </nav>

        <div class="baslik-eylem">
            <a class="dugme dugme--kucuk" href="<?= $view->link($membershipUrl) ?>">
                <?= $view->icon('users') ?>
                Üye Ol
            </a>
        </div>

        <button type="button" class="menu-dugmesi" data-menu-ac aria-expanded="false" aria-controls="mobil-menu">
            <span class="gorsel-gizli">Menüyü aç</span>
            <span class="menu-dugmesi__cizgi" aria-hidden="true"></span>
            <span class="menu-dugmesi__cizgi" aria-hidden="true"></span>
            <span class="menu-dugmesi__cizgi" aria-hidden="true"></span>
        </button>
    </div>
</header>

<div class="cekmece" id="mobil-menu" data-cekmece data-acik="false" aria-hidden="true">
    <div class="cekmece__perde" data-cekmece-kapat></div>
    <nav class="cekmece__panel" aria-label="Mobil menü">
        <div class="cekmece__ust">
            <span class="marka__ad">Menü</span>
            <button type="button" class="cekmece__kapat" data-cekmece-kapat>
                <span class="gorsel-gizli">Menüyü kapat</span>
                <?= $view->icon('close') ?>
            </button>
        </div>

        <ul>
<?php foreach ($navigation as $index => $item): ?>
            <li>
<?php if ($item->hasChildren()): ?>
                <button type="button" class="cekmece__baglanti" data-alt-menu-ac
                        aria-expanded="false" aria-controls="alt-menu-<?= (int) $index ?>">
                    <?= $view->e($item->label) ?>
                    <?= $view->icon('chevron-down') ?>
                </button>
                <div class="cekmece__alt-liste" id="alt-menu-<?= (int) $index ?>" data-acik="false">
                    <div>
<?php foreach ($item->children as $child): ?>
                        <a class="cekmece__alt-baglanti" href="<?= $view->link($child->path) ?>"
                           <?= $child->active ? 'aria-current="page"' : '' ?>>
                            <?= $view->e($child->label) ?>
                        </a>
<?php endforeach; ?>
                    </div>
                </div>
<?php else: ?>
                <a class="cekmece__baglanti" href="<?= $view->link($item->path) ?>"
                   <?= $item->active ? 'aria-current="page"' : '' ?>>
                    <?= $view->e($item->label) ?>
                </a>
<?php endif; ?>
            </li>
<?php endforeach; ?>
        </ul>

        <a class="dugme" href="<?= $view->link($membershipUrl) ?>">
            <?= $view->icon('users') ?>
            Üye Ol
        </a>
    </nav>
</div>
