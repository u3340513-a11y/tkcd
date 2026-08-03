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
];
