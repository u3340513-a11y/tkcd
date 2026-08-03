<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Üyelik çağrı bandı.
 *
 * @var PhpViewRenderer $view
 * @var string $uyelikUrl
 */
?>
<section class="bolum bolum--sikisik" aria-labelledby="uyelik-baslik">
    <div class="kapsayici">
        <div class="uyelik-bant belirme">
            <div>
                <h2 id="uyelik-baslik">Aramıza Katılın, Dayanışmayı Büyütelim</h2>
                <p>
                    Trabzonlu kamu çalışanları olarak aynı çatı altında buluşuyoruz. Üyelik
                    başvurunuzu oluşturarak etkinliklerimize katılabilir, temsilcilik ağımızda
                    yer alabilir ve sosyal projelerimize destek olabilirsiniz.
                </p>
            </div>

            <a class="dugme dugme--hayalet" href="<?= $view->link($uyelikUrl) ?>">
                <?= $view->icon('users') ?>
                Üyelik Başvurusu
            </a>
        </div>
    </div>
</section>
