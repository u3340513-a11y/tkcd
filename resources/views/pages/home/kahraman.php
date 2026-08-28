<?php

declare(strict_types=1);

use App\Application\ViewModel\HomePageViewModel;
use App\Core\View\PhpViewRenderer;

/**
 * Kahraman (hero) bölümü: derneğin ana mesajı, çağrı düğmeleri, sayaçlar ve
 * üyelik tanıtım kartı.
 *
 * @var PhpViewRenderer $view
 * @var HomePageViewModel $model
 * @var string $uyelikUrl
 * @var list<array<string, string>> $socials
 * @var array<string, mixed> $site
 */
?>
<section class="kahraman" aria-labelledby="kahraman-baslik">
    <video class="kahraman__video" autoplay muted loop playsinline aria-hidden="true">
        <source src="/images/hrbg.mp4" type="video/mp4">
    </video>
    <span class="kahraman__isik kahraman__isik--bordo" aria-hidden="true"></span>
    <span class="kahraman__isik kahraman__isik--mavi" aria-hidden="true"></span>

    <svg class="kahraman__desen" viewBox="0 0 1440 180" preserveAspectRatio="none" aria-hidden="true">
        <path d="M0 96c160-48 320-48 480 0s320 48 480 0 320-48 480 0v84H0z" fill="currentColor" opacity=".55"/>
        <path d="M0 126c180-42 300-30 480 6s300 48 480 6 300-54 480-18v60H0z" fill="currentColor" opacity=".35"/>
    </svg>

    <div class="kapsayici kahraman__izgara">
        <div>
            <span class="kahraman__rozet">
                <strong><?= (int) ($site['founded_year'] ?? 0) ?></strong>
                <?= $view->e((string) ($site['legal_note'] ?? '')) ?>
            </span>

            <h1 class="kahraman__baslik" id="kahraman-baslik">
                <span>Trabzon Ruhunu</span> Yaşatan Güçlü Bir Dayanışma
            </h1>

            <p class="kahraman__ozet">
                Trabzonlu kamu çalışanlarını aynı çatı altında buluşturarak birlik ve dayanışma
                ruhunu güçlendiriyor, kültürel değerlerimizi yaşatmayı ve gelecek nesillere
                aktarmayı amaçlıyoruz. Sosyal, kültürel ve dayanışma odaklı çalışmalarımızla
                güçlü bir hemşerilik bağı oluşturuyoruz.
            </p>

            <div class="kahraman__eylemler">
                <a class="dugme" href="<?= $view->link($uyelikUrl) ?>">
                    <?= $view->icon('users') ?>
                    Üye Ol
                </a>
                <a class="dugme dugme--ikincil" href="#hakkimizda">
                    Derneğimizi Tanıyın
                    <?= $view->icon('arrow-right') ?>
                </a>

                <ul class="sosyal-liste" aria-label="Sosyal medya hesaplarımız">
<?php foreach ($socials as $social): ?>
<?php if (($social['key'] ?? '') === 'whatsapp') { continue; } ?>
                    <li>
                        <a class="sosyal-dugme"
                           href="<?= $view->link($social['url'] ?? '') ?>"
                           target="_blank" rel="noopener noreferrer"
                           aria-label="<?= $view->e($social['label'] ?? '') ?>">
                            <?= $view->icon((string) ($social['icon'] ?? '')) ?>
                        </a>
                    </li>
<?php endforeach; ?>
                </ul>
            </div>

            <ul class="sayac-izgara" aria-label="Derneğimiz rakamlarla">
<?php foreach ($model->statistics as $statistic): ?>
                <li class="sayac">
                    <span class="sayac__ikon" aria-hidden="true"><?= $view->icon($statistic->icon) ?></span>
                    <span class="sayac__deger"
                          data-sayac="<?= (int) $statistic->value ?>"
                          data-sayac-son-ek="<?= $view->e($statistic->suffix) ?>">
                        <?= (int) $statistic->value ?><?= $view->e($statistic->suffix) ?>
                    </span>
                    <span class="sayac__etiket"><?= $view->e($statistic->label) ?></span>
                </li>
<?php endforeach; ?>
            </ul>
        </div>

        <aside class="kahraman__kart belirme" aria-labelledby="uyelik-kart-baslik">
            <div class="kahraman__gorsel">
<?= $view->partial('components/gorsel', [
    'src' => '/assets/img/kahraman-uyelik.jpg',
    'alt' => 'Derneğimizin Türkiye’nin 81 ilinde ve Avrupa’nın 18 noktasında büyüyen '
        . 'temsilcilik ağını gösteren tanıtım görseli',
    'yedekIkon' => 'handshake',
    'yukleme' => 'eager',
]) ?>
            </div>

            <h2 class="baslik-3" id="uyelik-kart-baslik">Hemşerilik Bağını Güçlendirelim</h2>
            <p>
                Üyelikle etkinliklerimize katılabilir, kültürel faaliyetlerde yer alabilir ve
                dayanışma projelerimize doğrudan destek olabilirsiniz.
            </p>

            <p class="kahraman__degerler">
                <?= $view->icon('heart') ?>
                Birlik &middot; Dayanışma &middot; Kültür
            </p>
        </aside>
    </div>
</section>
