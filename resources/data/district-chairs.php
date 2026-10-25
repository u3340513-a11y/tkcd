<?php

declare(strict_types=1);

/**
 * Toplantı sunumunda gösterilen ilçe başkanları (sunum sırası = dizi sırası).
 *
 * Alanlar:
 *   - ad       : Ad Soyad (son sözcük soyadı kabul edilir)
 *   - ilce     : Başkanı olduğu ilçe
 *   - fotograf : public/ klasörüne göre fotoğraf yolu
 *
 * @return list<array{ad: string, ilce: string, fotograf: string}>
 */
return [
    [
        'ad'       => 'İlhan Balcı',
        'ilce'     => 'Adapazarı',
        'fotograf' => 'assets/img/sakarya-ilce/ilhan-balci.png',
    ],
];
