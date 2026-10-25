<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Tasarım 3 — Aydınlık Editoryal: açık zemin, dev ilçe adı filigranı, çerçeveli portre.
 *
 * @var PhpViewRenderer $view
 * @var array{ad: string, ilce: string, fotograf: string} $baskan
 * @var string $adlar
 * @var string $soyad
 * @var string $dernekAdi
 */
?>
            <div class="t3">
                <p class="t3__filigran" aria-hidden="true"><?= $view->e(mb_strtoupper($baskan['ilce'], 'UTF-8')) ?></p>
                <div class="t3__portre">
                    <img src="<?= $view->e($view->asset($baskan['fotograf'])) ?>" alt="<?= $view->e($baskan['ad']) ?>, <?= $view->e($baskan['ilce']) ?> İlçe Başkanı">
                </div>
                <div class="t3__icerik">
                    <img class="t3__logo" src="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" alt="<?= $view->e($dernekAdi) ?> logosu">
                    <p class="t3__etiket"><?= $view->e($baskan['ilce']) ?> İlçe Başkanı</p>
                    <h1 class="t3__isim">
                        <span><?= $view->e($adlar) ?></span>
                        <strong><?= $view->e($soyad) ?></strong>
                    </h1>
                    <p class="t3__dernek"><?= $view->e($dernekAdi) ?></p>
                </div>
            </div>
