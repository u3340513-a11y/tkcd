<?php

declare(strict_types=1);

/**
 * Ana menü tanımı.
 *
 * Menü yapısı tek kaynaktan beslenir; header, mobil çekmece, footer ve
 * sitemap aynı diziyi kullanır. Böylece yeni sayfa eklenirken tek dosya
 * güncellenir (DRY).
 *
 * children: alt kırılım varsa ikinci seviye bağlantılar.
 * in_sitemap: sitemap.xml çıktısına dahil edilsin mi.
 */
return [
    [
        'label' => 'Ana Sayfa',
        'path' => '/',
        'priority' => '1.0',
        'children' => [],
    ],
    [
        'label' => 'Hakkımızda',
        'path' => '/hakkimizda/dernegimiz',
        'priority' => '0.8',
        'children' => [
            ['label' => 'Derneğimiz', 'path' => '/hakkimizda/dernegimiz', 'priority' => '0.8'],
            ['label' => 'Anlaşmalı Kurumlar', 'path' => '/hakkimizda/anlasmali-kurumlar', 'priority' => '0.6'],
            ['label' => 'Temsilci Ağımız', 'path' => '/hakkimizda/temsilci-agimiz', 'priority' => '0.6'],
            ['label' => 'Galeri', 'path' => '/hakkimizda/galeri', 'priority' => '0.6'],
        ],
    ],
    [
        'label' => 'Duyurular',
        'path' => '/duyurular',
        'priority' => '0.8',
        'children' => [],
    ],
    [
        'label' => 'Yönetim Kurulu',
        'path' => '/yonetim-kurulu',
        'priority' => '0.7',
        'children' => [],
    ],
    [
        'label' => 'İletişim',
        'path' => '/iletisim',
        'priority' => '0.7',
        'children' => [],
    ],
];
