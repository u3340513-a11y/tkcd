<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * İletişim sayfası.
 *
 * Bölümler:
 *   1. Hero        : Markalı başlık şeridi
 *   2. Harita      : Google Maps gömme
 *   3. İçerik      : Sol iletişim bilgileri + sağ form
 *
 * @var PhpViewRenderer      $view
 * @var SeoMeta              $seo
 * @var array<string, mixed> $site
 * @var string|null          $durum   'basarili' | 'hata' | null
 */

/** @var array<string, string> $iletisim */
$iletisim = (array) ($site['contact'] ?? []);
$adres    = (string) ($iletisim['address']    ?? '');
$eposta   = (string) ($iletisim['email']      ?? '');
$telefon  = (string) ($iletisim['phone']      ?? '');
$telefonE = (string) ($iletisim['phone_e164'] ?? '');

$haritaSrc = 'https://www.google.com/maps/embed?pb=!1m18!1m12!1m3!1d3010.4123456789!2d28.9397!3d41.0182!'
    . '2m3!1f0!2f0!3f0!3m2!1i1024!2i768!4f13.1!3m3!1m2!1s0x14cab9e7a1234567%3A0xabcdef!2sFatih%2C%20'
    . 'Yavuz%20Selim%20Cd.%2C%2034134%20Fatih%2F%C4%B0stanbul!5e0!3m2!1str!2str!4v1234567890';

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="kt-hero" aria-labelledby="kt-hero-baslik">
    <div class="kapsayici kt-hero__ic">
        <?php if (!empty($seo->breadcrumbs)): ?>
        <nav class="da-hero__breadcrumb" aria-label="Konum">
            <ol class="da-hero__breadcrumb-list">
                <li><a href="/">Ana Sayfa</a></li>
                <?php foreach ($seo->breadcrumbs as $bc): ?>
                <li>
                    <span aria-hidden="true">›</span>
                    <a href="<?= $view->e($bc['path']) ?>"><?= $view->e($bc['label']) ?></a>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>

        <h1 class="kt-hero__baslik belirme" id="kt-hero-baslik">İletişim</h1>
        <p class="kt-hero__alt belirme">
            Aklınıza takılan her konuda bize yazabilir, derneğimizle ilgili
            detaylı bilgi alabilirsiniz.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. HARİTA                                           ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div class="kt-harita" aria-label="Konumumuz haritada">
    <iframe
        class="kt-harita__cerceve"
        src="<?= $view->e($haritaSrc) ?>"
        title="Trabzonlu Kamu Çalışanları Derneği konumu"
        width="100%"
        height="340"
        style="border:0;"
        allowfullscreen
        loading="lazy"
        referrerpolicy="no-referrer-when-downgrade"
        aria-hidden="true"
        tabindex="-1"
    ></iframe>
</div>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  3. İÇERİK  (iletişim bilgileri + form)              ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="kt-icerik" aria-label="İletişim bilgileri ve form">
    <div class="kapsayici kt-icerik__izgara">

        <!-- Sol: bilgiler -->
        <div class="kt-bilgi">
            <h2 class="kt-bilgi__baslik">Sizinle Konuşmaya Hazırız</h2>
            <span class="kt-bilgi__cizgi" aria-hidden="true"></span>
            <p class="kt-bilgi__aciklama">
                Aklınıza takılan her konuda bize yazabilir, derneğimizle ilgili
                detaylı bilgi alabilirsiniz. İletişiminiz bizim için değerli.
            </p>

            <ul class="kt-bilgi__liste" aria-label="İletişim kanalları">
                <li class="kt-bilgi__satir">
                    <span class="kt-bilgi__ikon" aria-hidden="true"><?= $view->icon('map-pin') ?></span>
                    <span><?= $view->e($adres) ?></span>
                </li>

                <?php if ($eposta !== ''): ?>
                <li class="kt-bilgi__satir">
                    <span class="kt-bilgi__ikon" aria-hidden="true"><?= $view->icon('mail') ?></span>
                    <a href="mailto:<?= $view->e($eposta) ?>"><?= $view->e($eposta) ?></a>
                </li>
                <?php endif; ?>

                <?php if ($telefon !== ''): ?>
                <li class="kt-bilgi__satir">
                    <span class="kt-bilgi__ikon" aria-hidden="true"><?= $view->icon('phone') ?></span>
                    <a href="tel:<?= $view->e($telefonE) ?>"><?= $view->e($telefon) ?></a>
                </li>
                <?php endif; ?>
            </ul>
        </div>

        <!-- Sağ: form -->
        <div class="kt-form-kutu">

            <?php if ($durum === 'basarili'): ?>
            <div class="kt-bildiri kt-bildiri--basarili" role="alert">
                <?= $view->icon('check-circle') ?>
                <div>
                    <strong>Mesajınız İletildi</strong>
                    <p>En kısa sürede sizinle iletişime geçeceğiz.</p>
                </div>
            </div>
            <?php elseif ($durum === 'hata'): ?>
            <div class="kt-bildiri kt-bildiri--hata" role="alert">
                <?= $view->icon('alert-circle') ?>
                <div>
                    <strong>Gönderim Başarısız</strong>
                    <p>Lütfen tüm zorunlu alanları doğru doldurup tekrar deneyin.</p>
                </div>
            </div>
            <?php endif; ?>

            <form
                class="kt-form"
                method="POST"
                action="/iletisim"
                novalidate
                aria-label="İletişim formu"
            >
                <div class="kt-form__alan">
                    <label class="kt-form__etiket" for="kt-ad">
                        Adı Soyadı <span class="kt-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="kt-form__girdi"
                        type="text"
                        id="kt-ad"
                        name="ad"
                        autocomplete="name"
                        maxlength="120"
                        required
                        aria-required="true"
                    >
                </div>

                <div class="kt-form__alan">
                    <label class="kt-form__etiket" for="kt-eposta">
                        E-posta <span class="kt-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="kt-form__girdi"
                        type="email"
                        id="kt-eposta"
                        name="eposta"
                        autocomplete="email"
                        maxlength="254"
                        required
                        aria-required="true"
                    >
                </div>

                <div class="kt-form__alan">
                    <label class="kt-form__etiket" for="kt-konu">
                        Konu <span class="kt-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="kt-form__girdi"
                        type="text"
                        id="kt-konu"
                        name="konu"
                        maxlength="160"
                        required
                        aria-required="true"
                    >
                </div>

                <div class="kt-form__alan">
                    <label class="kt-form__etiket" for="kt-mesaj">Mesajınız</label>
                    <textarea
                        class="kt-form__girdi kt-form__girdi--alan"
                        id="kt-mesaj"
                        name="mesaj"
                        rows="5"
                        maxlength="2000"
                    ></textarea>
                </div>

                <button class="kt-form__gonder" type="submit">
                    <?= $view->icon('send') ?>
                    Gönder
                </button>
            </form>
        </div>

    </div>
</section>
