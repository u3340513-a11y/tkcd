<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * <head> bölümü: karakter kümesi, SEO üst verisi, sosyal paylaşım kartları,
 * yapısal veri (JSON-LD) ve varlık bağlantıları.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta $seo
 * @var array<string, mixed> $site
 * @var list<string> $styles
 */

$canonical = $view->absolute($seo->canonicalPath);
$ogImage = $view->absolute($seo->image ?? '/assets/img/logo.png');
$siteName = (string) ($site['name'] ?? '');
?>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
    <title><?= $view->e($seo->title) ?></title>
    <meta name="description" content="<?= $view->e($seo->description) ?>">
    <link rel="canonical" href="<?= $view->e($canonical) ?>">
    <meta name="robots" content="<?= $seo->indexable ? 'index, follow, max-image-preview:large' : 'noindex, follow' ?>">
    <meta name="author" content="<?= $view->e($siteName) ?>">
    <meta name="theme-color" content="#5f2132">
    <meta name="format-detection" content="telephone=no">

    <!-- Open Graph -->
    <meta property="og:type" content="<?= $view->e($seo->type) ?>">
    <meta property="og:locale" content="tr_TR">
    <meta property="og:site_name" content="<?= $view->e($siteName) ?>">
    <meta property="og:title" content="<?= $view->e($seo->title) ?>">
    <meta property="og:description" content="<?= $view->e($seo->description) ?>">
    <meta property="og:url" content="<?= $view->e($canonical) ?>">
    <meta property="og:image" content="<?= $view->e($ogImage) ?>">
    <meta property="og:image:alt" content="<?= $view->e($siteName) ?> logosu">

    <!-- Twitter / X -->
    <meta name="twitter:card" content="summary_large_image">
    <meta name="twitter:title" content="<?= $view->e($seo->title) ?>">
    <meta name="twitter:description" content="<?= $view->e($seo->description) ?>">
    <meta name="twitter:image" content="<?= $view->e($ogImage) ?>">

    <link rel="icon" href="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" type="image/webp">
    <link rel="apple-touch-icon" href="<?= $view->e($view->asset('assets/img/logo.png')) ?>">

    <link rel="preload" as="image" href="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" type="image/webp">
    <link rel="stylesheet" href="<?= $view->e($view->asset('assets/css/app.css')) ?>">
<?php foreach ($styles as $style): ?>
    <link rel="stylesheet" href="<?= $view->e($view->asset('assets/css/' . $style)) ?>">
<?php endforeach; ?>

    <!-- JavaScript kapalıysa görünürlük animasyonları devre dışı bırakılır,
         böylece tüm içerik her koşulda okunabilir kalır. -->
    <noscript><style>.belirme{opacity:1;translate:none}</style></noscript>

<?php foreach ($seo->structuredData as $block): ?>
    <script type="application/ld+json"><?= $view->json($block) ?></script>
<?php endforeach; ?>
