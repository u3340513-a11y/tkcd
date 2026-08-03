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
        'dosya' => 'trabzon-kamu-masa.webp',
        'alt'   => 'Derneğimizin toplantı masasından bir kare',
        'boyut' => 'buyuk',
    ],
    [
        'dosya' => 'trabzon-hero.webp',
        'alt'   => 'Trabzon — Sümela Manastırı',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'uzungol-hero.webp',
        'alt'   => 'Uzungöl manzarası',
        'boyut' => 'buyuk',
    ],
    [
        'dosya' => 'kamu-etkinlik.jpeg',
        'alt'   => 'Dernek etkinliğinden bir kare',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'kahraman-uyelik.jpg',
        'alt'   => 'Dernek üyelik etkinliğinden bir görüntü',
        'boyut' => 'normal',
    ],
    [
        'dosya' => 'herog.jpeg',
        'alt'   => 'Dernek faaliyetlerinden bir fotoğraf',
        'boyut' => 'normal',
    ],
];
