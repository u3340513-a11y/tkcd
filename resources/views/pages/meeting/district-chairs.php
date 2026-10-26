<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;

/**
 * Toplantı sunumu: ilçe başkanları (bağımsız, tam ekran sayfa).
 *
 * Neden ayrı şablon: Sunum ekranında site menüsü/alt bilgisi olmamalı; sayfa
 * arama motorlarına kapalıdır ve yalnızca dış CSS/JS dosyaları kullanır (CSP uyumlu).
 * Her slayt hazır bir poster görselidir; sayfa yalnızca sırayla gösterir.
 *
 * @var PhpViewRenderer $view
 * @var list<array{ad: string, ilce: string, gorsel: string}> $baskanlar
 */

$dernekAdi = 'Trabzonlu Kamu Çalışanları Derneği';
$toplam = count($baskanlar);
?>
<!DOCTYPE html>
<html lang="tr">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>İlçe Başkanları · <?= $view->e($dernekAdi) ?></title>
    <meta name="robots" content="noindex, nofollow">
    <link rel="icon" href="<?= $view->e($view->asset('assets/img/logo.webp')) ?>" type="image/webp">
    <link rel="stylesheet" href="<?= $view->e($view->asset('assets/css/meeting.css')) ?>">
</head>
<body class="sunum" data-sunum-toplam="<?= $toplam ?>">
    <main class="sunum__alan" aria-label="İlçe başkanları sunumu">
<?php foreach ($baskanlar as $sira => $baskan):
    $etiket = $baskan['ilce'] === '' ? $baskan['ad'] : $baskan['ad'] . ', ' . $baskan['ilce'] . ' İlçe Başkanı';
    ?>
        <section class="slayt<?= $sira === 0 ? ' slayt--aktif' : '' ?>" data-slayt aria-label="<?= $view->e($etiket) ?>">
            <img class="slayt__gorsel" src="<?= $view->e($view->asset($baskan['gorsel'])) ?>"
                 alt="<?= $view->e($etiket) ?>"
                 decoding="async">
        </section>
<?php endforeach; ?>
    </main>

    <nav class="sunum__kontrol" aria-label="Sunum kontrolleri" data-sunum-kontrol>
        <button type="button" class="sunum__dugme" data-onceki aria-label="Önceki">&#8592;</button>
        <span class="sunum__sayac" aria-live="polite"><span data-sayac>1</span> / <?= $toplam ?></span>
        <button type="button" class="sunum__dugme" data-sonraki aria-label="Sonraki">&#8594;</button>
        <button type="button" class="sunum__dugme" data-tam-ekran aria-label="Tam ekran (F)">&#9974;</button>
    </nav>

    <script src="<?= $view->e($view->asset('assets/js/meeting.js')) ?>" defer></script>
</body>
</html>
