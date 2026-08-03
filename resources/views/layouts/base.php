<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Ana yerleşim şablonu.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var string $content Sayfa şablonundan gelen işlenmiş içerik
 * @var array<string, mixed> $site
 * @var string $language
 * @var list<string> $styles  Sayfaya özel ek stil dosyaları
 * @var list<string> $scripts Sayfaya özel ek JavaScript dosyaları
 */

$styles  = $styles  ?? [];
$scripts = $scripts ?? [];
?>
<!DOCTYPE html>
<html lang="<?= $view->e($language) ?>" prefix="og: https://ogp.me/ns#">
<head>
<?= $view->partial('partials/head', ['seo' => $seo, 'styles' => $styles]) ?>
</head>
<body>
    <a class="atlama-baglantisi" href="#ana-icerik">İçeriğe geç</a>

<?= $view->partial('partials/header') ?>

    <main id="ana-icerik">
<?= $content ?>
    </main>

<?= $view->partial('partials/footer') ?>

    <button type="button" class="yukari-cik" data-yukari-cik>
        <span class="gorsel-gizli">Sayfanın başına dön</span>
        <?= $view->icon('arrow-up') ?>
    </button>

    <script src="<?= $view->e($view->asset('assets/js/app.js')) ?>" defer></script>
<?php foreach ($scripts as $script): ?>
    <script src="<?= $view->e($view->asset('assets/js/' . $script)) ?>" defer></script>
<?php endforeach; ?>
</body>
</html>
