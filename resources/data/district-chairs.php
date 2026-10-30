<?php

declare(strict_types=1);

/**
 * Toplantı sunumundaki ilçe başkanı slaytları (sunum sırası = dizi sırası).
 *
 * Her slayt hazır bir 16:9 poster görseli ya da bir videodur; fotoğraf, isim ve logo görselin içindedir.
 * Alanlar:
 *   - ad    : Ad Soyad / slayt adı (erişilebilirlik metni için)
 *   - ilce  : Başkanı olduğu ilçe; başkan olmayan slaytlarda boş (erişilebilirlik metni için)
 *   - gorsel: public/ klasörüne göre poster yolu (görsel slaytları)
 *   - video : public/ klasörüne göre video yolu (video slaytları; 'gorsel' yerine verilir)
 *
 * @return list<array{ad: string, ilce: string, gorsel?: string, video?: string}>
 */
return [
    ['ad' => 'Lansman Kapağı',     'ilce' => '',          'gorsel' => 'assets/img/lansman-kapak.png'],
    ['ad' => 'Lansman Videosu',    'ilce' => '',          'video'  => 'assets/video/TRABZONN.mp4'],
    ['ad' => 'Enes Adıgüzel',      'ilce' => 'Geyve',     'gorsel' => 'assets/img/slider-sakarya/enes-adiguzel-geyve.png'],
    ['ad' => 'İsmail Kolaşalı',    'ilce' => 'Arifiye',   'gorsel' => 'assets/img/slider-sakarya/ismail-kolasali-arifiye.png'],
    ['ad' => 'Gökhan Uzun',        'ilce' => 'Hendek',    'gorsel' => 'assets/img/slider-sakarya/gokhan-uzun-hendek.png'],
    ['ad' => 'Adem Özpınar',       'ilce' => 'Kocaali',   'gorsel' => 'assets/img/slider-sakarya/adem-ozpinar-kocaali.png'],
    ['ad' => 'Enes Akgün',         'ilce' => 'Akyazı',    'gorsel' => 'assets/img/slider-sakarya/enes-akgun-b.png'],
    ['ad' => 'Murat Saraç',        'ilce' => 'Hendek',    'gorsel' => 'assets/img/slider-sakarya/murat-sarac-hendek.png'],
    ['ad' => 'İlhan Balcı',        'ilce' => 'Adapazarı', 'gorsel' => 'assets/img/slider-sakarya/ilhan-balci-b.png'],
    ['ad' => 'Selim Koç',          'ilce' => 'Serdivan',  'gorsel' => 'assets/img/slider-sakarya/selim-koc-serdivan.png'],
    ['ad' => 'Erkan Kurt',         'ilce' => 'Kaynarca',  'gorsel' => 'assets/img/slider-sakarya/erkan-kurt-kaynarca.png'],
    ['ad' => 'Volkan Çalık',       'ilce' => 'Pamukova',  'gorsel' => 'assets/img/slider-sakarya/volkan-calik-pamukova.png'],
    ['ad' => 'Hasan Kolaşalı',     'ilce' => 'Sapanca',   'gorsel' => 'assets/img/slider-sakarya/hasan-kolasali-sapanca.png'],
    ['ad' => 'Yusuf Aksoy',        'ilce' => 'Söğütlü',   'gorsel' => 'assets/img/slider-sakarya/yusuf-aksoy-sogutlu.png'],
    ['ad' => 'Ali Osman Bayraktar', 'ilce' => 'Taraklı',  'gorsel' => 'assets/img/slider-sakarya/ali-osman-bayraktar-tarakli.png'],
    ['ad' => 'Ali Kamus',          'ilce' => 'Ferizli',   'gorsel' => 'assets/img/slider-sakarya/ali-kamus-ferizli.png'],
];
