<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Domain\Content\Entity\District;

/**
 * Trabzon ilçeleri bölümü — etkileşimli il haritası.
 *
 * Etkileşim: Haritadaki bir ilçeye veya listedeki hızlı seçim etiketine
 * tıklandığında (ya da Enter/Boşluk ile etkinleştirildiğinde) o ilçe hakkında
 * kısa bilgi bir modal pencerede gösterilir. Üzerine gelindiğinde/odaklanıldığında
 * ilçe adı harita üzerinde küçük bir etiket olarak belirir.
 *
 * Erişilebilirlik: Harita <path> öğeleri her zaman <title> ile ilçe adını
 * taşır (tarayıcı ipucu + ekran okuyucu) ve klavyeyle odaklanılabilir; hızlı
 * seçim listesindeki düğmeler de aynı bilgiyi sunar. Bilgi modalı yalnızca
 * JavaScript ile açılır.
 *
 * @var PhpViewRenderer $view
 * @var list<District> $districts
 */

if ($districts === []) {
    return;
}
?>
<section class="bolum bolum--desenli" aria-labelledby="ilce-baslik">
    <div class="kapsayici">
<?= $view->partial('components/bolum-basligi', [
    'etiket' => 'Memleketimiz',
    'baslik' => 'Trabzon',
    'vurgu' => 'İlçeleri',
    'aciklama' => 'Trabzon’un 18 ilçesi; her biri kendine özgü kültürü, doğası ve '
        . 'gelenekleriyle hemşerilik bağımızın kaynağıdır. Haritadan bir ilçeye '
        . 'tıklayarak kısa bilgi alabilirsiniz.',
    'id' => 'ilce-baslik',
]) ?>

        <div class="ilce__izgara">
            <div class="trabzon-harita" data-ilce-harita>
                <svg class="trabzon-harita__svg" viewBox="10 15 411 233" role="img"
                     aria-labelledby="ilce-harita-baslik">
                    <title id="ilce-harita-baslik">Trabzon ilçeleri haritası</title>
                    <g>
<?php foreach ($districts as $district): ?>
                        <path class="trabzon-harita__ilce<?= $district->isCenter ? ' trabzon-harita__ilce--merkez' : '' ?>"
                              d="<?= $view->e($district->mapPath) ?>"
                              tabindex="0"
                              role="button"
                              aria-haspopup="dialog"
                              aria-label="<?= $view->e($district->name) ?> ilçesi hakkında bilgi al"
                              data-ilce-slug="<?= $view->e($district->slug) ?>"
                              data-ilce-adi="<?= $view->e($district->name) ?>"
                              data-ilce-bilgi="<?= $view->e($district->highlight) ?>"
                              data-ilce-merkez="<?= $district->isCenter ? '1' : '0' ?>"
                        ><title><?= $view->e($district->name) ?></title></path>
<?php endforeach; ?>
                    </g>
                </svg>
                <span class="trabzon-harita__etiket" data-ilce-etiket hidden aria-hidden="true"></span>
            </div>

            <div class="ilce-yardimci">
                <p class="ilce-yardimci__metin">
                    Haritada bulmakta zorlandığınız ilçeyi aşağıdaki hızlı seçim
                    listesinden de açabilirsiniz.
                </p>
                <ul class="ilce-liste" data-ilce-liste>
<?php foreach ($districts as $district): ?>
                    <li>
                        <button type="button"
                                class="ilce-dugme<?= $district->isCenter ? ' ilce-dugme--merkez' : '' ?>"
                                data-ilce-slug="<?= $view->e($district->slug) ?>"
                                data-ilce-adi="<?= $view->e($district->name) ?>"
                                data-ilce-bilgi="<?= $view->e($district->highlight) ?>"
                                data-ilce-merkez="<?= $district->isCenter ? '1' : '0' ?>"
                                aria-haspopup="dialog">
                            <?= $view->e($district->name) ?>
                        </button>
                    </li>
<?php endforeach; ?>
                </ul>
            </div>
        </div>
    </div>
</section>

<dialog class="ilce-modal" data-ilce-modal aria-labelledby="ilce-modal-baslik">
    <div class="ilce-modal__panel">
        <button type="button" class="ilce-modal__kapat" data-ilce-modal-kapat aria-label="Kapat">
            <?= $view->icon('close') ?>
        </button>
        <span class="ilce-modal__ikon" aria-hidden="true"><?= $view->icon('map-pin') ?></span>
        <p class="ilce-modal__etiket" data-ilce-modal-etiket>Trabzon İlçesi</p>
        <h3 id="ilce-modal-baslik" data-ilce-modal-baslik></h3>
        <p class="ilce-modal__aciklama" data-ilce-modal-aciklama></p>
    </div>
</dialog>
