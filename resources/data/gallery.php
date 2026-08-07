<?php

declare(strict_types=1);

/**
 * Galeri fotoğrafları.
 *
 * Her görsel yalnızca bir kez listelenir. "boyut" alanı CSS grid span
 * değerini belirler: 'buyuk' → geniş kart, 'normal' → standart kart.
 *
 * @return list<array{dosya:string,alt:string,boyut:'buyuk'|'normal'}>
 */
return [
    [
        'dosya' => 'galeri/IMG_1278.JPG',
        'alt'   => 'Dernek etkinliğinden bir kare',
        'boyut' => 'buyuk',
    ],
    [
        'dosya' => 'galeri/IMG_1319.JPG',
        'alt'   => 'Dernek buluşmasından bir an',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'galeri/IMG_1367.JPG',
        'alt'   => 'Kültürel programa katılım',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'galeri/IMG_1370.JPG',
        'alt'   => 'Dernek üyeleriyle birlikte',
        'boyut' => 'buyuk',
    ],
    [
        'dosya' => 'galeri/IMG_1381.JPG',
        'alt'   => 'Etkinlik anından bir fotoğraf',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'galeri/IMG_1383.JPG',
        'alt'   => 'Dernek faaliyetlerinden bir kare',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'galeri/IMG_2254.JPG',
        'alt'   => 'Hemşeri buluşmasından bir an',
        'boyut' => 'buyuk',
    ],
    [
        'dosya' => 'galeri/IMG_2765.jpg',
        'alt'   => 'Dayanışma etkinliğinden bir fotoğraf',
        'boyut' => 'normal',
    ],
];
