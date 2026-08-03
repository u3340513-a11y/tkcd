<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Hata sayfası (404 / 500).
 *
 * Kullanıcıya teknik ayrıntı gösterilmez; yalnızca yönlendirici bir mesaj
 * sunulur. Teknik ayrıntılar sunucu tarafında günlüğe yazılır.
 *
 * @var PhpViewRenderer $view
 * @var int $status
 * @var string $title
 */

$aciklama = $status === 404
    ? 'Aradığınız sayfa taşınmış veya kaldırılmış olabilir. Aşağıdaki bağlantılardan '
        . 'gezinmeye devam edebilirsiniz.'
    : 'Beklenmeyen bir sorun oluştu. Ekibimiz durumdan haberdar edildi; lütfen kısa bir '
        . 'süre sonra tekrar deneyin.';
?>
<section class="sayfa-basi">
    <div class="kapsayici">
        <span class="rozet"><?= (int) $status ?></span>
        <h1><?= $view->e($title) ?></h1>
        <p><?= $view->e($aciklama) ?></p>
    </div>
</section>

<section class="bolum">
    <div class="kapsayici">
        <div class="bos-durum">
            <span class="bos-durum__ikon" aria-hidden="true"><?= $view->icon('map-pin') ?></span>
            <h2 class="baslik-3">Yolunuzu mu kaybettiniz?</h2>
            <p>Ana sayfamızdan derneğimizin çalışmalarına ve güncel duyurularımıza ulaşabilirsiniz.</p>
            <div class="bos-durum__eylemler">
                <a class="dugme" href="/">
                    Ana Sayfaya Dön
                    <?= $view->icon('arrow-right') ?>
                </a>
                <a class="dugme dugme--ikincil" href="/iletisim">
                    <?= $view->icon('mail') ?>
                    İletişime Geçin
                </a>
            </div>
        </div>
    </div>
</section>
