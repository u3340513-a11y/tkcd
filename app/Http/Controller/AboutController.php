<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Http\Response;

/**
 * "Hakkımızda" alt kırılım sayfaları.
 *
 * İçerikler ikinci fazda yönetim panelinden beslenecektir; sayfalar şu an
 * kurumsal çerçeve ve bilgilendirici boş durum (empty state) ile yayındadır.
 */
final class AboutController
{
    public function __construct(private readonly PageResponder $responder)
    {
    }

    public function association(): Response
    {
        $seo = $this->responder->seo(
            title: 'Derneğimiz',
            description: 'Trabzonlu Kamu Çalışanları Derneği’nin kuruluş amacı, vizyonu ve '
                . 'çalışma ilkeleri hakkında bilgi edinin.',
            canonicalPath: '/hakkimizda/dernegimiz',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Derneğimiz', 'path' => '/hakkimizda/dernegimiz'],
            ],
        );

        return $this->responder->page('pages/association', $seo, [
            'styles' => ['about.css'],
        ]);
    }

    public function partners(): Response
    {
        $seo = $this->responder->seo(
            title: 'Anlaşmalı Kurumlar',
            description: 'Üyelerimize özel indirim ve avantaj sağlayan anlaşmalı kurum ve '
                . 'kuruluşlarımızın listesi.',
            canonicalPath: '/hakkimizda/anlasmali-kurumlar',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Anlaşmalı Kurumlar', 'path' => '/hakkimizda/anlasmali-kurumlar'],
            ],
        );

        return $this->responder->page('pages/partners', $seo, [
            'styles' => ['about.css'],
        ]);
    }

    public function representatives(): Response
    {
        $seo = $this->responder->seo(
            title: 'Temsilci Ağımız',
            description: 'Türkiye genelinde 81 ilde görev yapan il ve ilçe temsilcilerimize '
                . 'buradan ulaşabilirsiniz.',
            canonicalPath: '/hakkimizda/temsilci-agimiz',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Temsilci Ağımız', 'path' => '/hakkimizda/temsilci-agimiz'],
            ],
        );

        /** @var array<string, mixed> $temsilciler */
        $temsilciler = require dirname(__DIR__, 3) . '/resources/data/representatives.php';

        /** @var list<array{plate: string, il: string, yol: string}> $haritaYollari */
        $haritaYollari = require dirname(__DIR__, 3) . '/resources/data/turkey-map.php';

        return $this->responder->page('pages/representatives', $seo, [
            'styles'        => ['about.css', 'representatives.css'],
            'scripts'       => ['representatives.js'],
            'temsilciler'   => $temsilciler,
            'haritaYollari' => $haritaYollari,
        ]);
    }

    public function gallery(): Response
    {
        $seo = $this->responder->seo(
            title: 'Galeri',
            description: 'Etkinliklerimizden, buluşmalarımızdan ve kültürel programlarımızdan '
                . 'fotoğraf ve videolar.',
            canonicalPath: '/hakkimizda/galeri',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Galeri', 'path' => '/hakkimizda/galeri'],
            ],
        );

        /** @var list<array{dosya:string,alt:string,boyut:'buyuk'|'normal'}> $gorseller */
        $gorseller = require dirname(__DIR__, 3) . '/resources/data/gallery.php';

        return $this->responder->page('pages/gallery', $seo, [
            'styles'    => ['about.css', 'gallery.css'],
            'scripts'   => ['gallery.js'],
            'gorseller' => $gorseller,
        ]);
    }

    private function section(string $title, string $description, string $path): Response
    {
        $seo = $this->responder->seo(
            title: $title,
            description: $description,
            canonicalPath: $path,
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => $title, 'path' => $path],
            ],
        );

        return $this->responder->page('pages/section', $seo, [
            'pageTitle' => $title,
            'pageDescription' => $description,
            'parentLabel' => 'Hakkımızda',
        ]);
    }
}
