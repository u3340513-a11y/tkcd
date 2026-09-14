<?php

declare(strict_types=1);

/**
 * Yönetim Kurulu üyeleri.
 *
 * Alanlar:
 *   slug        — URL-safe tanımlayıcı
 *   ad          — Ad soyad
 *   unvan       — Dernekteki unvan / görev
 *   fotograf    — /assets/img/ altındaki dosya adı (uzantısız değil, tam ad)
 *   biyografi   — Kısa biyografi paragrafı (isteğe bağlı)
 *   gorevler    — Diğer görev ve üyelikler (list<string>)
 *   sosyal      — platform → url eşlemesi; boş string varsa gösterilmez
 *
 * @return list<array{slug:string,ad:string,unvan:string,fotograf:string,biyografi:string,gorevler:list<string>,sosyal:array<string,string>}>
 */
return [
    [
        'slug'      => 'ismail-turgut-oksuz',
        'ad'        => 'İsmail Turgut Öksüz',
        'unvan'     => 'Kurucu ve Onursal Başkanımız',
        'fotograf'  => 'ismailturgutoksuz.webp',
        'biyografi' => '',
        'gorevler'  => [
            'Trabzonlular Federasyonu Başkanı',
            'Trabzonspor Kongre Üyesi',
        ],
        'sosyal' => [
            'facebook'  => 'https://www.facebook.com/ismailturgutoksuz61',
            'instagram' => 'https://www.instagram.com/ismailturgutoksuz/',
            'linkedin'  => 'https://tr.linkedin.com/in/ismailturgutoksuz',
        ],
    ],
    [
        'slug'      => 'hakan-turan',
        'ad'        => 'Hakan Turan',
        'unvan'     => 'Dernek Başkanı',
        'fotograf'  => 'hakan-turan.webp',
        'biyografi' => '',
        'gorevler'  => [
            'K.M.S Derneği Başkanı',
            'TGİYD. Derneği Y.K Üyesi',
        ],
        'sosyal' => [
            'facebook'  => 'https://www.facebook.com/hakanalituran1453/',
            'instagram' => 'https://www.instagram.com/hakan_turan61',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'omer-cakir',
        'ad'        => 'Ömer Çakır',
        'unvan'     => 'Başkan Vekili',
        'fotograf'  => 'omer-cakir.png',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'orhan-karal',
        'ad'        => 'Orhan Karal',
        'unvan'     => 'Genel Sekreter',
        'fotograf'  => 'orhan_abi.png',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'ahmet-cihangir',
        'ad'        => 'Ahmet Cihangir',
        'unvan'     => 'Başkan Yardımcısı',
        'fotograf'  => 'ahmet-cihangir.webp',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'hasan-ekinci',
        'ad'        => 'Hasan Ekinci',
        'unvan'     => 'Başkan Yardımcısı',
        'fotograf'  => 'hasan-ekinci.webp',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'sener-kurt',
        'ad'        => 'Şener Kurt',
        'unvan'     => 'Başkan Yardımcısı',
        'fotograf'  => 'sener-kurt.webp',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'musa-eski',
        'ad'        => 'Musa Eski',
        'unvan'     => 'Başkan Yardımcısı',
        'fotograf'  => 'musa-eski.webp',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    [
        'slug'      => 'mustafa-sahin',
        'ad'        => 'Mustafa Şahin',
        'unvan'     => 'Sayman',
        'fotograf'  => 'mustafa-sahin.webp',
        'biyografi' => '',
        'gorevler'  => [],
        'sosyal' => [
            'facebook'  => '',
            'instagram' => '',
            'linkedin'  => '',
        ],
    ],
    // Geçici olarak gizlendi — aktifleştirmek için yorum satırını kaldırın.
    // [
    //     'slug'      => 'ilyas-demir',
    //     'ad'        => 'İlyas Demir',
    //     'unvan'     => 'Gençlik Kolları Başkanı',
    //     'fotograf'  => 'ilyas-demir.jpeg',
    //     'biyografi' => '',
    //     'gorevler'  => [],
    //     'sosyal' => [
    //         'facebook'  => '',
    //         'instagram' => '',
    //         'linkedin'  => '',
    //     ],
    // ],
    // [
    //     'slug'      => 'fatma-demir',
    //     'ad'        => 'Fatma Demir',
    //     'unvan'     => 'Kadın Kolları Başkanı',
    //     'fotograf'  => 'fatma-demir.jpeg',
    //     'biyografi' => '',
    //     'gorevler'  => [],
    //     'sosyal' => [
    //         'facebook'  => '',
    //         'instagram' => '',
    //         'linkedin'  => '',
    //     ],
    // ],
];
