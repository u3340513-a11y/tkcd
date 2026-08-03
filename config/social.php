<?php

declare(strict_types=1);

use App\Core\Env;

/**
 * Sosyal medya kanalları.
 *
 * "handle" değeri arayüzde okunabilir etiket olarak, "url" bağlantı olarak,
 * "icon" ise ikon setindeki sembol adı olarak kullanılır.
 */
return [
    [
        'key' => 'facebook',
        'label' => 'Facebook',
        'handle' => 'Trabzonlu-kamu-çalışanları-derneği',
        'url' => Env::string('SOCIAL_FACEBOOK', 'https://www.facebook.com'),
        'icon' => 'facebook',
    ],
    [
        'key' => 'x',
        'label' => 'X (Twitter)',
        'handle' => 'TrabzonluKCD',
        'url' => Env::string('SOCIAL_X', 'https://x.com'),
        'icon' => 'x',
    ],
    [
        'key' => 'instagram',
        'label' => 'Instagram',
        'handle' => 'trabzonlukamucalisanlaridernegi',
        'url' => Env::string('SOCIAL_INSTAGRAM', 'https://www.instagram.com'),
        'icon' => 'instagram',
    ],
    [
        'key' => 'youtube',
        'label' => 'YouTube',
        'handle' => '@TrabzonluKamuÇalışanlarDerneği',
        'url' => Env::string('SOCIAL_YOUTUBE', 'https://www.youtube.com'),
        'icon' => 'youtube',
    ],
    [
        'key' => 'whatsapp',
        'label' => 'WhatsApp',
        'handle' => '+90 535 418 61 61',
        'url' => Env::string('SOCIAL_WHATSAPP', 'https://wa.me/905354186161'),
        'icon' => 'whatsapp',
    ],
];
