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
        ['ad' => 'Mert Hayrioğlu',       'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Orhan Karal',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Şener Kurt',           'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Selim Sandıkçı',       'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Zeynep Hilal Umur',    'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Hasan Ekinci',         'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Mustafa Şahin',        'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Muhammet Ali Topçu',   'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'İlyas Demir',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Hüseyin Koç',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Fatma Demir',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Musa Eski',            'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Ömer Çakır',           'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Ahmet Cihangir',       'gorev' => 'Yönetim Kurulu Üyesi'],
    ],
    'yedek' => [
        ['ad' => 'Mehmet Volkan Yavuztürk', 'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Enes Üstün',              'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Yunus Okutan',             'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Dursun Ali Süleymanoğlu',  'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Ümit Bölükbaş',            'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Murat Bayraktar',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Ahmet Yılmaz',             'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Mehmet Uzunoğlu',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Yücel Alp',                'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Uğur Okumuş',             'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Samet Çelik',              'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Onur Yıldız',              'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Berkay Soylu',             'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Uğur Kayazoğlu',          'gorev' => 'Yönetim Kurulu Üyesi'],
        ['ad' => 'Salim Süleymanoğlu',       'gorev' => 'Yönetim Kurulu Üyesi'],
    ],
];
