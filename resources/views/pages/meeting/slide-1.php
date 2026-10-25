<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Tasarım 1 — Klasik Bordo: sol bordo bilgi paneli, sağda tam boy fotoğraf.
 *
 * @var PhpViewRenderer $view
 * @var array{ad: string, ilce: string, fotograf: string} $baskan
 * @var string $adlar
 * @var string $soyad
 * @var string $dernekAdi
 */
?>
            <div class="t1">
                <div class="t1__panel">
                    <img class="t1__logo" src="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" alt="<?= $view->e($dernekAdi) ?> logosu">
                    <div class="t1__metin">
                        <p class="t1__etiket">İlçe Başkanı</p>
                        <h1 class="t1__isim">
                            <span class="t1__ad"><?= $view->e($adlar) ?></span>
                            <span class="t1__soyad"><?= $view->e($soyad) ?></span>
                        </h1>
                        <p class="t1__ilce"><?= $view->e($baskan['ilce']) ?></p>
                    </div>
                    <p class="t1__dernek"><?= $view->e($dernekAdi) ?></p>
                </div>
                <div class="t1__foto">
                    <img src="<?= $view->e($view->asset($baskan['fotograf'])) ?>" alt="<?= $view->e($baskan['ad']) ?>, <?= $view->e($baskan['ilce']) ?> İlçe Başkanı">
                </div>
            </div>
