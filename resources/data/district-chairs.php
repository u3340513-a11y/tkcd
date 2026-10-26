<?php

declare(strict_types=1);

/**
 * Toplantı sunumundaki ilçe başkanı slaytları (sunum sırası = dizi sırası).
 *
 * Her slayt hazır bir 16:9 posterdir; fotoğraf, isim ve logo görselin içindedir.
 * Alanlar:
 *   - ad    : Ad Soyad (erişilebilirlik metni için)
 *   - ilce  : Başkanı olduğu ilçe (erişilebilirlik metni için)
 *   - gorsel: public/ klasörüne göre poster yolu
 *
 * @return list<array{ad: string, ilce: string, gorsel: string}>
 */
return [
    ['ad' => 'İlhan Balcı',        'ilce' => 'Adapazarı', 'gorsel' => 'assets/img/slider-sakarya/ilhan-balci-b.png'],
    ['ad' => 'Enes Akgün',         'ilce' => 'Akyazı',    'gorsel' => 'assets/img/slider-sakarya/enes-akgun-b.png'],
    ['ad' => 'İsmail Kolaşalı',    'ilce' => 'Arifiye',   'gorsel' => 'assets/img/slider-sakarya/ismail-kolasali-arifiye.png'],
    ['ad' => 'Ali Kamus',          'ilce' => 'Ferizli',   'gorsel' => 'assets/img/slider-sakarya/ali-kamus-ferizli.png'],
    ['ad' => 'Enes Adıgüzel',      'ilce' => 'Geyve',     'gorsel' => 'assets/img/slider-sakarya/enes-adiguzel-geyve.png'],
    ['ad' => 'Gökhan Uzun',        'ilce' => 'Hendek',    'gorsel' => 'assets/img/slider-sakarya/gokhan-uzun-hendek.png'],
    ['ad' => 'Murat Saraç',        'ilce' => 'Hendek',    'gorsel' => 'assets/img/slider-sakarya/murat-sarac-hendek.png'],
    ['ad' => 'Erkan Kurt',         'ilce' => 'Kaynarca',  'gorsel' => 'assets/img/slider-sakarya/erkan-kurt-kaynarca.png'],
    ['ad' => 'Adem Özpınar',       'ilce' => 'Kocaali',   'gorsel' => 'assets/img/slider-sakarya/adem-ozpinar-kocaali.png'],
    ['ad' => 'Volkan Çalık',       'ilce' => 'Pamukova',  'gorsel' => 'assets/img/slider-sakarya/volkan-calik-pamukova.png'],
    ['ad' => 'Hasan Kolaşalı',     'ilce' => 'Sapanca',   'gorsel' => 'assets/img/slider-sakarya/hasan-kolasali-sapanca.png'],
    ['ad' => 'Selim Koç',          'ilce' => 'Serdivan',  'gorsel' => 'assets/img/slider-sakarya/selim-koc-serdivan.png'],
    ['ad' => 'Yusuf Aksoy',        'ilce' => 'Söğütlü',   'gorsel' => 'assets/img/slider-sakarya/yusuf-aksoy-sogutlu.png'],
    ['ad' => 'Ali Osman Bayraktar', 'ilce' => 'Taraklı',  'gorsel' => 'assets/img/slider-sakarya/ali-osman-bayraktar-tarakli.png'],
];
