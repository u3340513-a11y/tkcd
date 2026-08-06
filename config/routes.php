<?php

declare(strict_types=1);

use App\Http\Controller\AboutController;
use App\Http\Controller\AnnouncementController;
use App\Http\Controller\BoardController;
use App\Http\Controller\ContactController;
use App\Http\Controller\EventController;
use App\Http\Controller\HomeController;
use App\Http\Controller\MembershipController;
use App\Http\Controller\SitemapController;

/**
 * Rota tablosu.
 *
 * Her satır: [HTTP metodu, yol, denetleyici sınıfı, metot, rota adı].
 * Yollar Türkçe ve okunabilirdir; SEO açısından anahtar kelime içerir.
 */
return [
    ['GET', '/', HomeController::class, 'index', 'home'],

    ['GET', '/hakkimizda/dernegimiz', AboutController::class, 'association', 'about.association'],
    ['GET', '/hakkimizda/anlasmali-kurumlar', AboutController::class, 'partners', 'about.partners'],
    ['GET', '/hakkimizda/temsilci-agimiz', AboutController::class, 'representatives', 'about.representatives'],
    ['GET', '/hakkimizda/galeri', AboutController::class, 'gallery', 'about.gallery'],

    ['GET', '/duyurular', AnnouncementController::class, 'index', 'announcements.index'],
    ['GET', '/etkinlikler/{slug}', EventController::class, 'show', 'events.show'],

    ['GET', '/yonetim-kurulu', BoardController::class, 'index', 'board.index'],
    ['GET',  '/iletisim', ContactController::class, 'index', 'contact.index'],
    ['POST', '/iletisim', ContactController::class, 'store', 'contact.store'],
    ['GET',  '/uye-ol', MembershipController::class, 'index', 'membership.index'],
    ['POST', '/uye-ol', MembershipController::class, 'store', 'membership.store'],
    ['GET',  '/uye-ol/telefon-kontrol', MembershipController::class, 'checkTelefon', 'membership.checkTelefon'],

    ['GET', '/sitemap.xml', SitemapController::class, 'index', 'sitemap'],
];
