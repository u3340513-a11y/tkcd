<?php

declare(strict_types=1);

/**
 * Yönetim Kurulu hiyerarşik yapısı.
 *
 * Her grup bir 'bolum' (bölüm başlığı) ve 'uyeler' (kart listesi) içerir.
 * Tek kişilik satırlar 'tek' => true ile işaretlenir (tam genişlik).
 * Görsel olmayanlara 'placeholder-kisi.svg' atanır.
 *
 * @return list<array{baslik:string|null, uyeler: list<array{
 *   slug:string, ad:string, unvan:string, fotograf:string,
 *   biyografi:string, gorevler:list<string>, sosyal:array<string,string>
 * }>}>
 */

/** @return array{slug:string,ad:string,unvan:string,fotograf:string,biyografi:string,gorevler:list<string>,sosyal:array<string,string>} */
function kisi(
    string $slug,
    string $ad,
    string $unvan,
    string $fotograf = 'placeholder-kisi.svg',
    string $biyografi = '',
    array $gorevler = [],
    array $sosyal = [],
): array {
    return compact('slug', 'ad', 'unvan', 'fotograf', 'biyografi', 'gorevler', 'sosyal');
}

return [
    // ── 1. KURUCU/ONURSAL BAŞKAN + GENEL BAŞKAN ──────────────────────────────────────
    [
        'baslik' => null,
        'uyeler' => [
            kisi(
                slug:     'ismail-turgut-oksuz',
                ad:       'İsmail Turgut Öksüz',
                unvan:    'Kurucu ve Onursal Başkanımız',
                fotograf: 'ismailturgutoksuz.webp',
                gorevler: ['Trabzonlular Federasyonu Başkanı', 'Trabzonspor Kongre Üyesi'],
                sosyal:   [
                    'facebook'  => 'https://www.facebook.com/ismailturgutoksuz61',
                    'instagram' => 'https://www.instagram.com/ismailturgutoksuz/',
                    'linkedin'  => 'https://tr.linkedin.com/in/ismailturgutoksuz',
                ],
            ),
            kisi(
                slug:     'hakan-turan',
                ad:       'Hakan Turan',
                unvan:    'Genel Başkan',
                fotograf: 'hakan-turan.webp',
                gorevler: ['K.M.S Derneği Başkanı', 'TGİYD. Derneği Y.K Üyesi'],
                sosyal:   [
                    'facebook'  => 'https://www.facebook.com/hakanalituran1453/',
                    'instagram' => 'https://www.instagram.com/hakan_turan61',
                ],
            ),
        ],
    ],

    // ── 3. BAŞKAN VEKİLİ + GENEL SEKRETER ───────────────────────────────────
    [
        'baslik' => null,
        'uyeler' => [
            kisi(slug: 'omer-cakir',    ad: 'Ömer Çakır',   unvan: 'Başkan Vekili',  fotograf: 'omer-cakir.png'),
            kisi(slug: 'orhan-karal',   ad: 'Orhan Karal',   unvan: 'Genel Sekreter', fotograf: 'orhan_abi.png'),
        ],
    ],

    // ── 4. BAŞKAN YARDIMCILARI ───────────────────────────────────────────────
    [
        'baslik' => 'Başkan Yardımcıları',
        'uyeler' => [
            kisi(slug: 'ahmet-cihangir', ad: 'Ahmet Cihangir', unvan: 'Başkan Yardımcısı', fotograf: 'ahmet-cihangir.png'),
            kisi(slug: 'hasan-ekinci',   ad: 'Hasan Ekinci',   unvan: 'Başkan Yardımcısı', fotograf: 'hasan-ekinci.png'),
            kisi(slug: 'sener-kurt',     ad: 'Şener Kurt',     unvan: 'Başkan Yardımcısı'),
            kisi(slug: 'musa-eski',      ad: 'Musa Eski',      unvan: 'Başkan Yardımcısı', fotograf: 'musa-eski.png'),
        ],
    ],

    // ── 5. SAYMAN + HUKUK İŞLERİ ─────────────────────────────────────────────
    [
        'baslik' => null,
        'uyeler' => [
            kisi(slug: 'mustafa-sahin',      ad: 'Mustafa Şahin',     unvan: 'Sayman', fotograf: 'mustafa-sahin.png'),
            kisi(slug: 'zeynep-hilal-umur',  ad: 'Zeynep Hilal Umur', unvan: 'Hukuk İşleri Başkanı', biyografi: 'Dernek Avukatı', fotograf: 'zhu.png'),
        ],
    ],

    // ── 6a. GENÇLİK KOLLARI ──────────────────────────────────────────────────
    [
        'baslik' => 'Gençlik Kolları',
        'uyeler' => [
            kisi(slug: 'ilyas-demir',   ad: 'İlyas Demir',    unvan: 'Gençlik Kolları Başkanı', fotograf: 'ilyas-demir.png'),
            kisi(slug: 'umit-bolukbas', ad: 'Ümit Bölükbaş',  unvan: 'Başkan Yardımcısı'),
            kisi(slug: 'samet-celik',   ad: 'Samet Çelik',    unvan: 'Başkan Yardımcısı', fotograf: 'samet-celik.png'),
            kisi(slug: 'berkay-soylu',  ad: 'Berkay Soylu',   unvan: 'Başkan Yardımcısı', fotograf: 'berkay-s.png'),
        ],
    ],

    // ── 6b. TEŞKİLATLANMADAN SORUMLU ─────────────────────────────────────────
    [
        'baslik' => 'Teşkilatlanma',
        'uyeler' => [
            kisi(slug: 'huseyin-koc',      ad: 'Hüseyin Koç',      unvan: 'Teşkilatlanmadan Sorumlu Başkan', fotograf: 'huseyin_koc.png'),
            kisi(slug: 'mert-hayrioglu',   ad: 'Mert Hayrioğlu',   unvan: 'Başkan Yardımcısı', fotograf: 'mert-hayrioglu.jpeg'),
            kisi(slug: 'murat-bayraktar',  ad: 'Murat Bayraktar',  unvan: 'Başkan Yardımcısı', fotograf: 'murat-bayraktar.png'),
            kisi(slug: 'ugur-kayazoglu',   ad: 'Uğur Kayazoğlu',   unvan: 'Başkan Yardımcısı', fotograf: 'ugur-kyz.png'),
        ],
    ],

    // ── 6c. KADIN KOLLARI ────────────────────────────────────────────────────
    [
        'baslik' => 'Kadın Kolları',
        'uyeler' => [
            kisi(slug: 'busra-yilmaz',       ad: 'Büşra Yılmaz',       unvan: 'Kadın Kolları Başkanı', fotograf: 'büşra-bk.png'),
            kisi(slug: 'guluzar-aydogdu',    ad: 'Gülüzar Aydoğdu',    unvan: 'Başkan Yardımcısı', fotograf: 'guluzar-aydogdu.png'),
            kisi(slug: 'nurcan-degermenci',  ad: 'Nurcan Değermenci',   unvan: 'Başkan Yardımcısı', fotograf: 'nurcan-degermenci.jpeg'),
            kisi(slug: 'emine-aydin',        ad: 'Emine Aydın',        unvan: 'Başkan Yardımcısı', fotograf: 'emine-aydin.png'),
        ],
    ],

    // ── 7. YÖNETİM KURULU ÜYELERİ ────────────────────────────────────────────
    [
        'baslik' => 'Yönetim Kurulu Üyeleri',
        'uyeler' => [
            kisi(slug: 'mehmet-volkan-yavuzturk', ad: 'Mehmet Volkan Yavuztürk', unvan: 'Yönetim Kurulu Üyesi', fotograf: 'mvy.png'),
            kisi(slug: 'enes-ustun',              ad: 'Enes Üstün',              unvan: 'Yönetim Kurulu Üyesi', fotograf: 'enes-ustn.png'),
            kisi(slug: 'selim-sandikci',          ad: 'Selim Sandıkçı',          unvan: 'Yönetim Kurulu Üyesi', fotograf: 'selim-sandikci.png'),
            kisi(slug: 'muhammet-ali-topcu',      ad: 'Muhammet Ali Topçu',      unvan: 'Yönetim Kurulu Üyesi'),
            kisi(slug: 'yunus-okutan',            ad: 'Yunus Okutan',            unvan: 'Yönetim Kurulu Üyesi'),
            kisi(slug: 'dursun-ali-suleymanogl',  ad: 'Dursun Ali Süleymanoğlu', unvan: 'Yönetim Kurulu Üyesi'),
            kisi(slug: 'ahmet-yilmaz',            ad: 'Ahmet Yılmaz',            unvan: 'Yönetim Kurulu Üyesi', fotograf: 'ahmet-yilmaz.png'),
            kisi(slug: 'mehmet-uzunoglu',         ad: 'Mehmet Uzunoğlu',         unvan: 'Yönetim Kurulu Üyesi', fotograf: 'mehmet-uzunoglu.jpeg'),
            kisi(slug: 'onur-yildiz',             ad: 'Onur Yıldız',             unvan: 'Yönetim Kurulu Üyesi', fotograf: 'onur-yildiz.png'),
            kisi(slug: 'yucel-alp',               ad: 'Yücel Alp',               unvan: 'Yönetim Kurulu Üyesi', fotograf: 'yucel-alp.jpeg'),
            kisi(slug: 'ugur-okumus',             ad: 'Uğur Okumuş',             unvan: 'Yönetim Kurulu Üyesi', fotograf: 'ugur-okumus.png'),
            kisi(slug: 'salim-suleymanogl',       ad: 'Salim Süleymanoğlu',      unvan: 'Yönetim Kurulu Üyesi'),
            kisi(slug: 'fatma-demir',             ad: 'Fatma Demir',             unvan: 'Yönetim Kurulu Üyesi', fotograf: 'fatma-demir.png'),
        ],
    ],

    // ── 8. DİJİTAL PROJELER KOORDİNATÖRÜ ────────────────────────────────────
    [
        'baslik' => 'Dijital Projeler ve Teknoloji',
        'uyeler' => [
            kisi(slug: 'ugur-kotbas', ad: 'Uğur Kotbaş', unvan: 'Koordinatör', fotograf: 'ugur.kotbas.png'),
        ],
    ],
];
