<?php

declare(strict_types=1);

/**
 * Duyurular & Haberler.
 *
 * Alanlar:
 *   slug         — URL parçası (benzersiz, slug formatı)
 *   title        — Başlık
 *   summary      — Kısa özet (liste görünümünde gösterilir)
 *   category     — 'duyuru' | 'etkinlik' | 'rapor'
 *   published_at — ISO-8601 (Y-m-d)  → görüntüleme sırası bu alana göredir
 *   highlighted  — Anasayfa şeridinde vurgulansın mı
 *
 * @return list<array{slug:string,title:string,summary:string,category:string,published_at:string,highlighted:bool}>
 */
return [
    [
        'slug'         => 'sakarya-teskilatlanma-toplantisi-eylul-2026',
        'title'        => 'Sakarya Teşkilatlanma Toplantımız — 24 Eylül 2026',
        'summary'      => 'Bu ayki Sakarya teşkilatlanma toplantımız Adapazarı İlçe Başkanlığı ev sahipliğinde '
            . 'gerçekleştirilecektir. 📅 Tarih: 24 Eylül 2026 Perşembe | ⏰ Saat: 19.30 | '
            . '📍 Yer: Elegante Cafe Restorant — Semerciler Mah. Atatürk Bulvarı Pasaj 2000 Çatı Katı 2/202, '
            . '54100 Adapazarı, Sakarya. Tüm üyelerimiz davetlidir! '
            . '📞 İletişim: 0 505 357 88 82 — İlhan Balcı (Adapazarı İlçe Başkanı).',
        'category'     => 'etkinlik',
        'published_at' => '2026-09-21',
        'highlighted'  => true,
    ],
    [
        'slug'         => 'eskisehir-il-baskanligi-toplantisi-agustos-2026',
        'title'        => 'Eskişehir İl Başkanlığı Toplantısı',
        'summary'      => '27 Ağustos Perşembe günü saat 18.30\'da Choco Gusto Eczacılık Şubesi\'nde '
            . '(İsmet İnönü 2 Bulvarı 51/A Uluönder) Eskişehir İl Başkanlığı toplantısı düzenlenecektir. '
            . 'Tüm üyelerimiz davetlidir.',
        'category'     => 'duyuru',
        'published_at' => '2026-08-22',
        'highlighted'  => true,
    ],
    [
        'slug'         => '2025-yili-faaliyet-raporu',
        'title'        => '2025 Yılı Faaliyet Raporu Yayınlandı',
        'summary'      => 'Yılın ilk yarısında gerçekleştirdiğimiz projeler ve mali tablolarımızı '
            . 'içeren kapsamlı faaliyet raporuna buradan ulaşabilirsiniz.',
        'category'     => 'rapor',
        'published_at' => '2026-02-01',
        'highlighted'  => false,
    ],
    [
        'slug'         => 'olagan-genel-kurul-toplantisi',
        'title'        => 'Olağan Genel Kurul Toplantısı Hakkında',
        'summary'      => 'Derneğimizin 2026 yılı Olağan Genel Kurul toplantısı, belirtilen '
            . 'gündem maddelerini görüşmek üzere merkez binamızda toplanacaktır.',
        'category'     => 'duyuru',
        'published_at' => '2026-02-14',
        'highlighted'  => false,
    ],
    [
        'slug'         => 'piknik-organizasyonu',
        'title'        => 'Piknik Organizasyonu',
        'summary'      => 'Piknik organizasyonumuz planlama aşamasındadır. Program tarihi ve '
            . 'detaylar netleştiğinde üyelerimize en kısa sürede bilgilendirilecektir.',
        'category'     => 'etkinlik',
        'published_at' => '2025-10-24',
        'highlighted'  => false,
    ],
    [
        'slug'         => 'horon-egitimi',
        'title'        => 'Horon Eğitimi',
        'summary'      => 'Horon eğitimi programımız için hazırlıklar devam etmektedir. Eğitim '
            . 'takvimi ve katılım detayları önümüzdeki günlerde paylaşılacaktır.',
        'category'     => 'etkinlik',
        'published_at' => '2025-09-17',
        'highlighted'  => false,
    ],
    [
        'slug'         => 'kahvalti-programi',
        'title'        => 'Kahvaltı Programı',
        'summary'      => 'Üyelerimize yönelik düzenlenmesi planlanan kahvaltı programımızla '
            . 'ilgili tarih ve içerik bilgileri kısa süre içerisinde duyurulacaktır.',
        'category'     => 'etkinlik',
        'published_at' => '2025-09-08',
        'highlighted'  => false,
    ],
];
