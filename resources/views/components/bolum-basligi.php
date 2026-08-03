<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Bölüm başlığı bileşeni.
 *
 * @var PhpViewRenderer $view
 * @var string $etiket    Üst etiket (küçük büyük harf)
 * @var string $baslik    Ana başlık (ilk kelime vurgulanır)
 * @var string $vurgu     Başlıkta degrade ile vurgulanacak bölüm
 * @var string $aciklama  Alt açıklama metni
 * @var bool $ortali      Ortalanmış hizalama
 * @var string $seviye    Başlık etiketi (h2 varsayılan)
 * @var string $id        Bölümün aria-labelledby ile işaret edeceği kimlik
 */

$etiket = $etiket ?? '';
$baslik = $baslik ?? '';
$vurgu = $vurgu ?? '';
$aciklama = $aciklama ?? '';
$ortali = (bool) ($ortali ?? false);
$seviye = (string) ($seviye ?? 'h2');
$seviye = in_array($seviye, ['h2', 'h3'], true) ? $seviye : 'h2';
$id = (string) ($id ?? '');
?>
<div class="bolum-basligi<?= $ortali ? ' bolum-basligi--ortali' : '' ?>">
<?php if ($etiket !== ''): ?>
    <span class="etiket"><?= $view->e($etiket) ?></span>
<?php endif; ?>
    <<?= $seviye ?> class="baslik-2"<?= $id === '' ? '' : ' id="' . $view->e($id) . '"' ?>>
        <?= $view->e($baslik) ?><?php if ($vurgu !== ''): ?> <span class="baslik-vurgu"><?= $view->e($vurgu) ?></span><?php endif; ?>
    </<?= $seviye ?>>
    <div class="nokta-ayrac" aria-hidden="true"><span></span><span></span><span></span></div>
<?php if ($aciklama !== ''): ?>
    <p class="aciklama"><?= $view->e($aciklama) ?></p>
<?php endif; ?>
</div>
