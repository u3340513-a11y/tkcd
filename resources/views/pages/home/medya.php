<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Tanıtım videosu bölümü.
 *
 * Video, ilk sayfa yükleme performansını korumak için bölüm görünüm alanına
 * yaklaştığında (IntersectionObserver ile) sessiz, döngüsel ve denetimsiz bir
 * arka plan olarak gömülür (gizlilik dostu youtube-nocookie.com alan adı
 * üzerinden). JavaScript kapalıyken veya kullanıcı "hareketi azalt" tercihini
 * seçmişken video hiç yüklenmez; bölüm markanın degrade zemini üzerinde
 * durmaya devam eder ve oynat düğmesi doğrudan videonun kendisini yeni
 * sekmede açar.
 *
 * @var PhpViewRenderer $view
 * @var string $youtubeUrl  Kanal bağlantısı ("Tüm Videolarımızı İzleyin")
 * @var string $videoId     Arka planda oynatılacak videonun YouTube kimliği
 */

$videoId = $videoId ?? '';
$videoUrl = $videoId !== '' ? 'https://www.youtube.com/watch?v=' . $videoId : $youtubeUrl;
?>
<section class="medya" aria-labelledby="medya-baslik"<?= $videoId !== '' ? ' data-medya-video-id="' . $view->e($videoId) . '"' : '' ?>>
<?php if ($videoId !== ''): ?>
    <div class="medya__arkaplan" aria-hidden="true">
        <iframe
            src="https://www.youtube-nocookie.com/embed/<?= $view->e($videoId) ?>?autoplay=1&mute=1&loop=1&playlist=<?= $view->e($videoId) ?>&controls=0&rel=0&modestbranding=1&playsinline=1&disablekb=1&iv_load_policy=3"
            title="Tanıtım videosu (arka plan)"
            tabindex="-1"
            frameborder="0"
            allow="accelerometer; autoplay; clipboard-write; encrypted-media; gyroscope; picture-in-picture; web-share"
            referrerpolicy="strict-origin-when-cross-origin"
            allowfullscreen
        ></iframe>
    </div>
<?php endif; ?>

    <div class="medya__katman" aria-hidden="true"></div>

    <svg class="medya__desen" viewBox="0 0 1440 600" preserveAspectRatio="xMidYMid slice" aria-hidden="true">
        <path d="M0 420c180-70 360-70 540 0s360 70 540 0 360-70 540 0" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M0 470c180-70 360-70 540 0s360 70 540 0 360-70 540 0" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M0 520c180-70 360-70 540 0s360 70 540 0 360-70 540 0" fill="none" stroke="currentColor" stroke-width="2"/>
        <path d="M480 300 720 120l240 180z" fill="none" stroke="currentColor" stroke-width="2"/>
    </svg>

    <div class="kapsayici medya__icerik">
        <a class="medya__oynat" href="<?= $view->link($videoUrl) ?>" target="_blank" rel="noopener noreferrer">
            <span class="gorsel-gizli">Tanıtım videomuzu YouTube’da sesli izleyin</span>
            <?= $view->icon('play') ?>
        </a>

        <h2 id="medya-baslik">Birlikte Daha Güçlü Bir Dayanışma</h2>
        <p>
            Trabzonlu kamu çalışanlarını aynı çatı altında buluşturarak birlik, dayanışma ve
            kültürel değerlerimizi geleceğe taşıyoruz. Türkiye’nin her tarafında iş birliğiyle
            büyüyen bir hemşerilik ağıyız.
        </p>

        <p class="medya__eylem">
            <a class="dugme dugme--hayalet" href="<?= $view->link($youtubeUrl) ?>"
               target="_blank" rel="noopener noreferrer">
                Tüm Videolarımızı İzleyin
                <?= $view->icon('external') ?>
            </a>
        </p>
    </div>
</section>
