<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Güvenli görsel bileşeni.
 *
 * Neden: İçerik görselleri sunucuya sonradan yüklenebilir. Dosya mevcut
 * değilse boş bir alan veya kırık görsel simgesi yerine kurumsal desenli bir
 * alternatif gösterilir; böylece sayfa her koşulda eksiksiz görünür.
 *
 * @var PhpViewRenderer $view
 * @var string|null $src        /public dizinine göreli görsel yolu
 * @var string $alt             Ekran okuyucular için açıklama
 * @var string $yedekIkon       Görsel bulunamazsa gösterilecek ikon adı
 * @var string $yukleme         "lazy" veya "eager"
 */

$src = $src ?? null;
$alt = $alt ?? '';
$yedekIkon = $yedekIkon ?? 'sparkles';
$yukleme = ($yukleme ?? 'lazy') === 'eager' ? 'eager' : 'lazy';
?>
<?php if (is_string($src) && $src !== '' && $view->assetExists($src)): ?>
<img src="<?= $view->e($view->asset($src)) ?>" alt="<?= $view->e($alt) ?>"
     loading="<?= $view->e($yukleme) ?>" decoding="async">
<?php else: ?>
<span class="gorsel-yedek" role="img" aria-label="<?= $view->e($alt) ?>">
    <?= $view->icon($yedekIkon) ?>
</span>
<?php endif; ?>
