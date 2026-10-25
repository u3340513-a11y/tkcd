<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Tasarım 2 — Modern Gece: koyu gradyan zemin, ortada halkalı portre.
 *
 * @var PhpViewRenderer $view
 * @var array{ad: string, ilce: string, fotograf: string} $baskan
 * @var string $adlar
 * @var string $soyad
 * @var string $dernekAdi
 */
?>
            <div class="t2">
                <header class="t2__ust">
                    <img class="t2__logo" src="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" alt="<?= $view->e($dernekAdi) ?> logosu">
                    <p class="t2__dernek"><?= $view->e($dernekAdi) ?></p>
                </header>
                <div class="t2__portre">
                    <img src="<?= $view->e($view->asset($baskan['fotograf'])) ?>" alt="<?= $view->e($baskan['ad']) ?>, <?= $view->e($baskan['ilce']) ?> İlçe Başkanı">
                </div>
                <div class="t2__metin">
                    <h1 class="t2__isim"><?= $view->e($adlar) ?> <strong><?= $view->e($soyad) ?></strong></h1>
                    <p class="t2__unvan"><span><?= $view->e($baskan['ilce']) ?></span> İlçe Başkanı</p>
                </div>
            </div>
