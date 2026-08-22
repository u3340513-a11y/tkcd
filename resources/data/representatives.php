<?php

declare(strict_types=1);

/**
 * Temsilci ağı verisi.
 *
 * Neden bu dosya: Admin panel ve veritabanı entegrasyonu tamamlanana kadar
 * il ve ilçe temsilcisi bilgileri burada yönetilir. Panel hazır olduğunda
 * bu dosya kaldırılır; controller doğrudan veritabanı servisine geçer.
 *
 * Yapı:
 *   - Anahtar: plaka kodu (iki haneli string, örn. "06" = Ankara)
 *   - Değer:
 *       il_adi   : İlin görünen adı
 *       temsilci : İl temsilcisinin adı soyadı (boş ise henüz atanmamış)
 *       telefon  : Temsilcinin iletişim numarası (boş ise gizli)
 *       eposta   : Temsilcinin e-posta adresi (opsiyonel)
 *       ilceler  : İlçe temsilcileri — admin panel ikinci fazda doldurulacak
 *
 * @return array<string, array{il_adi: string, temsilci: string, telefon: string, eposta: string, ilceler: list<array{ilce: string, temsilci: string, telefon: string}>}>
 */
return [
    '06' => [
        'il_adi'   => 'Ankara',
        'temsilci' => 'Ahmet Yılmaz',
        'telefon'  => '0312 XXX XX XX',
        'eposta'   => '',
        'ilceler'  => [
            [
                'ilce'     => 'Çankaya',
                'temsilci' => 'Uğur KOTBAŞ',
                'telefon'  => '0530 XXX XX XX',
            ],
        ],
    ],
    '34' => [
        'il_adi'   => 'İstanbul',
        'temsilci' => 'Mehmet Demir',
        'telefon'  => '0212 XXX XX XX',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '61' => [
        'il_adi'   => 'Trabzon',
        'temsilci' => 'Temel Yılmaz',
        'telefon'  => '0462 XXX XX XX',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '35' => [
        'il_adi'   => 'İzmir',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '16' => [
        'il_adi'   => 'Bursa',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '42' => [
        'il_adi'   => 'Konya',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '01' => [
        'il_adi'   => 'Adana',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '27' => [
        'il_adi'   => 'Gaziantep',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '55' => [
        'il_adi'   => 'Samsun',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '38' => [
        'il_adi'   => 'Kayseri',
        'temsilci' => '',
        'telefon'  => '',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '41' => [
        'il_adi'   => 'Kocaeli',
        'temsilci' => 'Tuncay Dağ',
        'telefon'  => '0545 641 11 61',
        'eposta'   => '',
        'ilceler'  => [],
    ],
    '67' => [
        'il_adi'   => 'Zonguldak',
        'temsilci' => 'Sinan Taşkın',
        'telefon'  => '0532 403 02 47',
        'eposta'   => '',
        'ilceler'  => [],
    ],
];
