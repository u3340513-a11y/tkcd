<?php

declare(strict_types=1);

/**
 * Yönetim Kurulu asil ve yedek üye listesi.
 *
 * Sadece kamuya açık bilgiler: ad-soyad ve görev.
 * TC Kimlik No ve telefon numaraları güvenlik gereği dahil edilmez.
 *
 * @return array{asil: list<array{ad: string, gorev: string}>, yedek: list<array{ad: string, gorev: string}>}
 */
return [
    'asil' => [
        ['ad' => 'Hakan Turan',          'gorev' => 'Genel Başkan'],
        ['ad' => 'Mert Hayrioğlu',       'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Orhan Karal',          'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Şener Kurt',           'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Selim Sandıkçı',       'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Zeynep Hilal Umur',    'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Hasan Ekinci',         'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Mustafa Şahin',        'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Muhammet Ali Topçu',   'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'İlyas Demir',          'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Hüseyin Koç',          'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Fatma Demir',          'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Musa Eski',            'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Ömer Çakır',           'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
        ['ad' => 'Ahmet Cihangir',       'gorev' => 'Yönetim Kurulu Üyesi (Asil)'],
    ],
    'yedek' => [
        ['ad' => 'Mehmet Volkan Yavuztürk', 'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Enes Üstün',              'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Yunus Okutan',             'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Dursun Ali Süleymanoğlu',  'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Ümit Bölükbaş',            'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Murat Bayraktar',          'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Ahmet Yılmaz',             'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Mehmet Uzunoğlu',          'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Yücel Alp',                'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Uğur Okumuş',             'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Samet Çelik',              'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Onur Yıldız',              'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Berkay Soylu',             'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Uğur Kayazoğlu',          'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
        ['ad' => 'Salim Süleymanoğlu',       'gorev' => 'Yönetim Kurulu Üyesi (Yedek)'],
    ],
];
