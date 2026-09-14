<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Yönetim Kurulu Üye Listesi sayfası.
 *
 * Asil ve yedek üye tablolarını gösterir.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta         $seo
 * @var array<string, mixed> $site
 * @var list<array{ad: string, gorev: string}> $asilUyeler
 * @var list<array{ad: string, gorev: string}> $yedekUyeler
 */
?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="yk-hero" aria-labelledby="yk-hero-baslik">
    <div class="kapsayici yk-hero__ic">
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

        <h1 class="yk-hero__baslik belirme" id="yk-hero-baslik">Üye Listesi</h1>
        <p class="yk-hero__alt belirme">
            Yönetim kurulu asil ve yedek üyelerimiz.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. ASİL ÜYELER                                      ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="yk-bolum" aria-label="Asil yönetim kurulu üyeleri">
    <div class="kapsayici">
        <div class="yk-liste-kartlari">
            <div class="yk-liste-baslik">
                <h2 class="yk-liste-baslik__metin belirme">
                    <span class="yk-liste-baslik__ikon">🏛️</span>
                    Asil Yönetim Kurulu
                </h2>
                <span class="yk-liste-baslik__sayi"><?= count($asilUyeler) ?> üye</span>
            </div>

            <div class="yk-tablo-sarmal">
                <table class="yk-tablo" aria-label="Asil yönetim kurulu üye listesi">
                    <thead>
                        <tr>
                            <th scope="col" class="yk-tablo__th yk-tablo__th--no">Sıra</th>
                            <th scope="col" class="yk-tablo__th">Adı Soyadı</th>
                            <th scope="col" class="yk-tablo__th">Görevi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($asilUyeler as $i => $uye): ?>
                        <tr class="yk-tablo__satir belirme">
                            <td class="yk-tablo__td yk-tablo__td--no"><?= $i + 1 ?></td>
                            <td class="yk-tablo__td yk-tablo__td--ad"><?= $view->e($uye['ad']) ?></td>
                            <td class="yk-tablo__td yk-tablo__td--gorev">
                                <span class="yk-gorev-badge yk-gorev-badge--asil"><?= $view->e($uye['gorev']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- ╔══════════════════════════════════════════════════════╗ -->
        <!-- ║  3. YEDEK ÜYELER                                     ║ -->
        <!-- ╚══════════════════════════════════════════════════════╝ -->
        <div class="yk-liste-kartlari yk-liste-kartlari--yedek">
            <div class="yk-liste-baslik">
                <h2 class="yk-liste-baslik__metin belirme">
                    <span class="yk-liste-baslik__ikon">📋</span>
                    Yedek Yönetim Kurulu
                </h2>
                <span class="yk-liste-baslik__sayi"><?= count($yedekUyeler) ?> üye</span>
            </div>

            <div class="yk-tablo-sarmal">
                <table class="yk-tablo" aria-label="Yedek yönetim kurulu üye listesi">
                    <thead>
                        <tr>
                            <th scope="col" class="yk-tablo__th yk-tablo__th--no">Sıra</th>
                            <th scope="col" class="yk-tablo__th">Adı Soyadı</th>
                            <th scope="col" class="yk-tablo__th">Görevi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <?php foreach ($yedekUyeler as $i => $uye): ?>
                        <tr class="yk-tablo__satir belirme">
                            <td class="yk-tablo__td yk-tablo__td--no"><?= $i + 1 ?></td>
                            <td class="yk-tablo__td yk-tablo__td--ad"><?= $view->e($uye['ad']) ?></td>
                            <td class="yk-tablo__td yk-tablo__td--gorev">
                                <span class="yk-gorev-badge yk-gorev-badge--yedek"><?= $view->e($uye['gorev']) ?></span>
                            </td>
                        </tr>
                        <?php endforeach; ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</section>
