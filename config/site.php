<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Kurumsal kimlik, iletişim ve varsayılan SEO bilgileri.
 *
 * Admin panel fazında bu değerler veritabanındaki "settings" tablosundan
 * okunacak; o zaman yalnızca SiteSettingsProvider implementasyonu değişir,
 * arayüz katmanı aynı kalır.
 */
return [
    'name' => Env::string('SITE_NAME', 'Trabzonlu Kamu Çalışanları Derneği'),
    'short_name' => Env::string('SITE_SHORT_NAME', 'TKÇD'),
    'legal_note' => 'Trabzonlular Federasyonu Kuruluşudur',
    'founded_year' => 2025,

    'tagline' => 'Trabzon Ruhunu Yaşatan Güçlü Bir Dayanışma',
    'description' => 'Trabzonlu kamu çalışanlarını aynı çatı altında buluşturan; '
        . 'birlik, dayanışma ve kültürel değerleri geleceğe taşıyan dernek.',

    'contact' => [
        'email' => Env::string('SITE_EMAIL', 'info@trabzonlukamucalisanlaridernegi.com'),
        'phone' => Env::string('SITE_PHONE', '+90 535 418 61 61'),
        'phone_e164' => '+905354186161',
        'address' => Env::string('SITE_ADDRESS', 'Fatih Mah. Yavuz Selim Cad. 34134 İstanbul / TÜRKİYE'),
        'address_locality' => 'İstanbul',
        'address_region' => 'Fatih',
        'postal_code' => '34134',
        'country' => 'TR',
    ],

    'membership_form_url' => Env::string('MEMBERSHIP_FORM_URL', '/uye-ol'),

    // Anasayfadaki tanıtım bölümünün arka planında oynatılan YouTube videosu.
    // Yalnızca video kimliği (ID) tutulur; gömme URL'si SeoMeta/partial
    // katmanında oluşturulur.
    'promo_video_id' => Env::string('SITE_PROMO_VIDEO_ID', 'ELbwpC0AYhA'),

    'seo' => [
        'default_title' => 'Trabzonlu Kamu Çalışanları Derneği',
        'title_suffix' => ' | Trabzonlu Kamu Çalışanları Derneği',
        'default_description' => 'Trabzonlu kamu çalışanlarını aynı çatı altında buluşturarak birlik ve '
            . 'dayanışma ruhunu güçlendiriyor, kültürel değerlerimizi yaşatarak gelecek nesillere aktarıyoruz.',
        'default_image' => '/assets/img/logo.png',
        'keywords' => [
            'Trabzonlu kamu çalışanları derneği',
            'Trabzon dernek',
            'hemşehri derneği',
            'Trabzon dayanışma',
            'Trabzonlular Federasyonu',
        ],
    ],
];
