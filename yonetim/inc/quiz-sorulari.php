<?php
/**
 * Trabzonspor Bilgi Yarışması — Soru Havuzu
 *
 * ~200 adet 5 şıklı çoktan seçmeli soru.
 * Her soru: soru metni, 5 seçenek, doğru cevap indexi (0-4), zorluk seviyesi.
 *
 * Zorluk seviyeleri:
 *   - kolay  : Temel bilgiler, şampiyonluklar, stadyum
 *   - orta   : Oyuncular, istatistikler, transferler
 *   - zor    : Detaylı tarihçe, rekorlar, niş bilgiler
 */

return [
    // ══════════════════════════════════════════════════════════════
    // KOLAY SORULAR
    // ══════════════════════════════════════════════════════════════

    ['soru' => 'Trabzonspor hangi yılda kurulmuştur?', 'secenekler' => ['1965', '1967', '1970', '1963', '1975'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un renkleri nelerdir?', 'secenekler' => ['Kırmızı-Beyaz', 'Bordo-Mavi', 'Sarı-Lacivert', 'Siyah-Beyaz', 'Yeşil-Beyaz'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi şehrin takımıdır?', 'secenekler' => ['Rize', 'Trabzon', 'Ordu', 'Giresun', 'Samsun'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un stadyumunun adı nedir?', 'secenekler' => ['Avni Aker', 'Şenol Güneş', 'Medical Park', 'Papara Park', 'Hüseyin Avni Aker'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor kaç kez Süper Lig şampiyonu olmuştur?', 'secenekler' => ['5', '6', '7', '8', '4'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor ilk Süper Lig şampiyonluğunu hangi yıl kazanmıştır?', 'secenekler' => ['1974-75', '1975-76', '1976-77', '1978-79', '1980-81'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un maskotu hangisidir?', 'secenekler' => ['Aslan', 'Kartal', 'Kurt', 'Karakartal', 'Fırtına'], 'cevap' => 4, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un en son Süper Lig şampiyonluğu hangi sezondur?', 'secenekler' => ['2019-20', '2020-21', '2021-22', '2022-23', '2018-19'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi bölgede yer alır?', 'secenekler' => ['Marmara', 'Karadeniz', 'İç Anadolu', 'Akdeniz', 'Ege'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un kısa adı nedir?', 'secenekler' => ['TRB', 'TS', 'TRS', 'TRA', 'TZS'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor Süper Lig\'e ilk kez hangi yıl çıkmıştır?', 'secenekler' => ['1972-73', '1973-74', '1974-75', '1971-72', '1975-76'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un efsanevi kalecisi kimdir?', 'secenekler' => ['Fatih Terim', 'Şenol Güneş', 'Turgay Şeren', 'Rüştü Reçber', 'Volkan Demirel'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi liglerde mücadele etmektedir?', 'secenekler' => ['Süper Lig', '1. Lig', '2. Lig', '3. Lig', 'Bölgesel Lig'], 'cevap' => 0, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor kaç kez Türkiye Kupası şampiyonu olmuştur?', 'secenekler' => ['5', '7', '8', '9', '10'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => '"Fırtına" lakabı hangi takıma aittir?', 'secenekler' => ['Beşiktaş', 'Galatasaray', 'Fenerbahçe', 'Trabzonspor', 'Bursaspor'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un kuruluşunda kaç kulüp birleşmiştir?', 'secenekler' => ['2', '3', '4', '5', '6'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor taraftarlarına ne denir?', 'secenekler' => ['Çarşı', 'Vira', 'Ultraslan', 'GFB', 'Sol Açık'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un en büyük rakibi olarak kabul edilen takım hangisidir?', 'secenekler' => ['Beşiktaş', 'Galatasaray', 'Fenerbahçe', 'Rizespor', 'Samsunspor'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi renk forma ile iç saha maçlarını oynar?', 'secenekler' => ['Bordo', 'Mavi', 'Beyaz', 'Bordo-Mavi', 'Siyah'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Şenol Güneş Trabzonspor\'da hangi mevkide oynuyordu?', 'secenekler' => ['Forvet', 'Orta saha', 'Defans', 'Kaleci', 'Kanat'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un 2021-22 şampiyonluğundaki teknik direktör kimdir?', 'secenekler' => ['Şenol Güneş', 'Ünal Karaman', 'Abdullah Avcı', 'Hüseyin Çimşir', 'Eddie Newton'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un yeni stadyumu Papara Park kaç kişiliktir?', 'secenekler' => ['35.000', '38.000', '40.782', '42.000', '45.000'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un ilk başkanı kimdir?', 'secenekler' => ['Ahmet Suat Özyazıcı', 'Mehmet Ali Yılmaz', 'Sadri Şener', 'Hayrettin Hacısalihoğlu', 'Özkan Sümer'], 'cevap' => 0, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi futbol federasyonuna bağlıdır?', 'secenekler' => ['UEFA', 'FIFA', 'TFF', 'AFC', 'CONMEBOL'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un kuruluş tarihi tam olarak nedir?', 'secenekler' => ['1 Mayıs 1967', '2 Ağustos 1967', '15 Haziran 1967', '12 Mart 1967', '28 Ekim 1967'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un en çok gol atan oyuncusu kimdir?', 'secenekler' => ['Fatih Tekke', 'Şenol Güneş', 'Cemil Usta', 'Hami Mandıralı', 'Tuncay Şanlı'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Papara Park stadyumunun eski adı nedir?', 'secenekler' => ['Avni Aker', 'Medical Park Arena', 'Trabzon Arena', 'Şenol Güneş Stadyumu', 'Akyazı Stadyumu'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => '"Karadeniz Fırtınası" hangi takımın lakabıdır?', 'secenekler' => ['Giresunspor', 'Rizespor', 'Samsunspor', 'Trabzonspor', 'Orduspor'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzon ilinin plaka kodu kaçtır?', 'secenekler' => ['59', '60', '61', '62', '63'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un formasındaki yıldız kaç şampiyonluğu temsil eder?', 'secenekler' => ['5', '6', '7', '8', '10'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor 2022-23 sezonunda kaçıncı olmuştur?', 'secenekler' => ['1.', '2.', '3.', '4.', '5.'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un 2023-24 sezonundaki teknik direktörü?', 'secenekler' => ['Abdullah Avcı', 'Şenol Güneş', 'Nenad Bjelica', 'Ünal Karaman', 'Stefan Kuntz'], 'cevap' => 0, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un futbol dışında faaliyet gösterdiği branşlardan biri?', 'secenekler' => ['Tenis', 'Basketbol', 'Golf', 'Kriket', 'Rugby'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un deplasman forması genellikle hangi renktir?', 'secenekler' => ['Siyah', 'Beyaz', 'Gri', 'Mavi', 'Kırmızı'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un Süper Lig\'deki en büyük rakibi hangi takımdır?', 'secenekler' => ['Beşiktaş', 'Fenerbahçe', 'Galatasaray', 'Başakşehir', 'Samsunspor'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor taraftarının meşhur tezahüratı hangisidir?', 'secenekler' => ['Çıldırın', 'Her Yer Trabzon', 'Biz Bize Yeteriz', 'Kara Kartal', 'Sensiz Olmaz'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Papara Park stadyumu hangi semtte bulunmaktadır?', 'secenekler' => ['Ortahisar', 'Akyazı', 'Yomra', 'Pelitli', 'Akçaabat'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un en çok izlenen maçı hangi platform üzerinden yayınlanmıştır?', 'secenekler' => ['TRT', 'beIN Sports', 'Star TV', 'ATV', 'Show TV'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un Avrupa\'da en son mücadele ettiği turnuva (2023)?', 'secenekler' => ['Şampiyonlar Ligi', 'Avrupa Ligi', 'Konferans Ligi', 'UEFA Kupası', 'Kupa Galipleri'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un Süper Kupa şampiyonluk sayısı kaçtır?', 'secenekler' => ['6', '8', '9', '10', '11'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un stadyumu Papara Park hangi yıl açılmıştır?', 'secenekler' => ['2014', '2015', '2016', '2017', '2018'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un 2024-25 sezonundaki teknik direktörü?', 'secenekler' => ['Abdullah Avcı', 'Şenol Güneş', 'Nenad Bjelica', 'Ünal Karaman', 'Cihat Arslan'], 'cevap' => 1, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi büyük turnuvada Türk futbolunu temsil etmiştir?', 'secenekler' => ['Dünya Kupası', 'Avrupa Şampiyonası', 'Şampiyonlar Ligi', 'Olimpiyatlar', 'Konfederasyonlar Kupası'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor hangi kıtanın kulüpler turnuvasında oynamıştır?', 'secenekler' => ['Asya', 'Afrika', 'Avrupa', 'Amerika', 'Avustralya'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor kaç yıldır kesintisiz Süper Lig\'de mücadele etmektedir?', 'secenekler' => ['40+', '45+', '50+', '35+', '30+'], 'cevap' => 2, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un bordo rengi hangi tonu temsil eder?', 'secenekler' => ['Açık kırmızı', 'Koyu kırmızı', 'Vişne', 'Şarap rengi', 'Kiremit'], 'cevap' => 3, 'zorluk' => 'kolay'],
    ['soru' => 'Trabzonspor\'un armasında hangi sembol bulunur?', 'secenekler' => ['Yıldız', 'Hilal', 'Kartal', 'Aslan', 'Arı'], 'cevap' => 0, 'zorluk' => 'kolay'],

    // ══════════════════════════════════════════════════════════════
    // ORTA SEVIYE SORULAR
    // ══════════════════════════════════════════════════════════════

    ['soru' => 'Cemil Usta hangi mevkide oynuyordu?', 'secenekler' => ['Kaleci', 'Defans', 'Orta saha', 'Kanat', 'Forvet'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor 2010-11 sezonunda şampiyonluğu hangi takıma kaptırdı?', 'secenekler' => ['Galatasaray', 'Fenerbahçe', 'Beşiktaş', 'Bursaspor', 'İstanbul BB'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Gökdeniz Karadeniz hangi takımda futbol hayatını sürdürmüştür?', 'secenekler' => ['Galatasaray', 'Fenerbahçe', 'Rubin Kazan', 'CSKA Moskova', 'Spartak Moskova'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Tuncay Şanlı hangi İngiliz takımına transfer olmuştur?', 'secenekler' => ['Arsenal', 'Chelsea', 'Middlesbrough', 'Liverpool', 'Manchester City'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor hangi sezon UEFA Şampiyonlar Ligi grup aşamasına kalmıştır?', 'secenekler' => ['2009-10', '2010-11', '2011-12', '2012-13', '2013-14'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Hami Mandıralı Trabzonspor\'da kaç yıl futbol oynadı?', 'secenekler' => ['10', '12', '14', '16', '18'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Marek Hamsik hangi ülkenin vatandaşıdır?', 'secenekler' => ['Çekya', 'Polonya', 'Slovakya', 'Macaristan', 'Hırvatistan'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Hugo Rodallega hangi ülkeden Trabzonspor\'a gelmiştir?', 'secenekler' => ['Brezilya', 'Arjantin', 'Kolombiya', 'Meksika', 'Ekvador'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Jose Sosa hangi ülkedendir?', 'secenekler' => ['Brezilya', 'Kolombiya', 'Arjantin', 'Şili', 'Uruguay'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Alexander Sörloth hangi ülkenin vatandaşıdır?', 'secenekler' => ['İsveç', 'Danimarka', 'Norveç', 'Finlandiya', 'İzlanda'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Sörloth Trabzonspor\'da kaç gol attı?', 'secenekler' => ['16', '19', '22', '24', '33'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Anthony Nwakaeme hangi ülkedendir?', 'secenekler' => ['Gana', 'Kamerun', 'Nijerya', 'Senegal', 'Fildişi Sahili'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Uğurcan Çakır hangi mevkide oynamaktadır?', 'secenekler' => ['Defans', 'Orta saha', 'Kaleci', 'Forvet', 'Kanat'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2021-22 şampiyonluk kadrosundaki kaptanı kimdir?', 'secenekler' => ['Uğurcan Çakır', 'Vitor Hugo', 'Bakasetas', 'Hamsik', 'Nwakaeme'], 'cevap' => 0, 'zorluk' => 'orta'],
    ['soru' => 'Yusuf Yazıcı hangi Fransız takımına transfer olmuştur?', 'secenekler' => ['Lyon', 'PSG', 'Marseille', 'Lille', 'Monaco'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor Başkanlığını yapan Ahmet Ağaoğlu hangi dönemde görev yaptı?', 'secenekler' => ['2014-2018', '2016-2020', '2018-2022', '2010-2014', '2020-2024'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor Başkanı Ertuğrul Doğan hangi yılda göreve geldi?', 'secenekler' => ['2020', '2021', '2022', '2023', '2024'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2020-21 sezonundaki teknik direktörü?', 'secenekler' => ['Abdullah Avcı', 'Eddie Newton', 'Ünal Karaman', 'Hüseyin Çimşir', 'Rıza Çalımbay'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2019-20 sezonundaki teknik direktörü?', 'secenekler' => ['Ünal Karaman', 'Hüseyin Çimşir', 'Eddie Newton', 'Abdullah Avcı', 'Rıza Çalımbay'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Oscar Cardozo hangi ülkelidir?', 'secenekler' => ['Uruguay', 'Arjantin', 'Paraguay', 'Brezilya', 'Şili'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un ilk Avrupa maçındaki rakibi?', 'secenekler' => ['Celtic', 'Ajax', 'Liverpool', 'Cardiff City', 'Bayern Münih'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Onur Kıvrak hangi yılda Trabzonspor\'a transfer oldu?', 'secenekler' => ['2005', '2006', '2007', '2008', '2009'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un rekor transfer geliri hangi oyuncudan elde edilmiştir?', 'secenekler' => ['Burak Yılmaz', 'Uğurcan Çakır', 'Yusuf Yazıcı', 'Sörloth', 'Abdülkadir Ömür'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un basketbol takımı hangi ligde mücadele ediyor?', 'secenekler' => ['BSL', 'TBL', 'TB2L', 'Bölgesel Lig', 'Süper Lig'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2021-22 sezonunda kaybettiği toplam maç sayısı?', 'secenekler' => ['2', '3', '4', '5', '6'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un en çok gol attığı derbi rakibi?', 'secenekler' => ['Beşiktaş', 'Fenerbahçe', 'Galatasaray', 'Rizespor', 'Samsunspor'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2022 Süper Kupa\'daki rakibi?', 'secenekler' => ['Galatasaray', 'Fenerbahçe', 'Sivasspor', 'Kayserispor', 'Akhisarspor'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2015-16 sezonundaki teknik direktörü?', 'secenekler' => ['Ersun Yanal', 'Hami Mandıralı', 'Shota Arveladze', 'Rıza Çalımbay', 'Vahid Halilhodziç'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2021-22 şampiyonluğunda en çok gol atan oyuncusu?', 'secenekler' => ['Cornelius', 'Nwakaeme', 'Bakasetas', 'Djaniny', 'Hamsik'], 'cevap' => 0, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un en çok şampiyonluk kazandığı on yıl?', 'secenekler' => ['1970ler', '1980ler', '1990lar', '2000ler', '2010lar'], 'cevap' => 0, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un en uzun süreli kaptanı kim?', 'secenekler' => ['Şenol Güneş', 'Hami Mandıralı', 'Ali Kemal Denizci', 'Cemil Usta', 'Gökdeniz Karadeniz'], 'cevap' => 1, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor hangi yıl ilk kez profesyonel lige katılmıştır?', 'secenekler' => ['1967', '1968', '1969', '1970', '1974'], 'cevap' => 4, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2017-18 sezonunda kaçıncı olmuştur?', 'secenekler' => ['1.', '2.', '3.', '4.', '5.'], 'cevap' => 3, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un alt yapısından yetişen kaç oyuncu A Milli Takım forması giymiştir?', 'secenekler' => ['20+', '30+', '40+', '50+', '60+'], 'cevap' => 2, 'zorluk' => 'orta'],
    ['soru' => 'Trabzonspor\'un 2021-22 sezonundaki en çok asist yapan oyuncu?', 'secenekler' => ['Abdülkadir Ömür', 'Hamsik', 'Bakasetas', 'Nwakaeme', 'Cornelius'], 'cevap' => 2, 'zorluk' => 'orta'],

    // ══════════════════════════════════════════════════════════════
    // ZOR SORULAR
    // ══════════════════════════════════════════════════════════════

    ['soru' => 'Şenol Güneş Trabzonspor\'da kaç resmi maçta kaleyi korumuştur?', 'secenekler' => ['416', '462', '508', '553', '600'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un 1975-76 şampiyonluğundaki puan farkı kaçtır?', 'secenekler' => ['1', '2', '3', '4', '5'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un ilk resmi maçı hangi takımla oynandı?', 'secenekler' => ['Giresunspor', 'Rizespor', 'Ordu İdmanyurdu', 'Samsunspor', 'Erzurumspor'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Cemil Usta Trabzonspor\'da toplam kaç gol atmıştır?', 'secenekler' => ['80', '90', '103', '115', '125'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Avrupa kupalarında toplam kaç maç oynamıştır (2023 itibariyle)?', 'secenekler' => ['80+', '100+', '120+', '140+', '160+'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un 2021-22 sezonunda aldığı toplam puan?', 'secenekler' => ['75', '78', '81', '84', '87'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un en uzun yenilmezlik serisi kaç maçtır?', 'secenekler' => ['18', '21', '23', '26', '30'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor tarihinin en hızlı golü kaçıncı saniyede atılmıştır?', 'secenekler' => ['6', '9', '11', '14', '18'], 'cevap' => 1, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un 1975-76 kadrosunda kaç yabancı oyuncu vardı?', 'secenekler' => ['0', '1', '2', '3', '4'], 'cevap' => 0, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un ilk Türkiye Kupası şampiyonluğu hangi yıldır?', 'secenekler' => ['1975', '1977', '1978', '1980', '1983'], 'cevap' => 1, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor hangi yıl Cumhurbaşkanlığı Kupası\'nı kazanmıştır?', 'secenekler' => ['1976', '1978', '1980', '1982', '1984'], 'cevap' => 0, 'zorluk' => 'zor'],
    ['soru' => 'Papara Park stadyumunun yapım maliyeti ne kadardır?', 'secenekler' => ['150 milyon TL', '250 milyon TL', '350 milyon TL', '450 milyon TL', '500+ milyon TL'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Fenerbahçe\'ye karşı en farklı galibiyeti?', 'secenekler' => ['3-0', '4-0', '4-1', '5-0', '5-1'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Galatasaray\'a karşı en farklı galibiyeti?', 'secenekler' => ['3-0', '4-0', '4-1', '5-0', '5-1'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Süper Lig tarihindeki en gollü maçı?', 'secenekler' => ['7-4', '8-3', '9-0', '6-5', '7-3'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un en çok şampiyonluk yaşayan oyuncusu?', 'secenekler' => ['Şenol Güneş', 'Tuncay Bıçakçıoğlu', 'Abdullah Çevrim', 'Cemil Usta', 'Ali Kemal Denizci'], 'cevap' => 0, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Avrupa\'da en çok karşılaştığı rakip?', 'secenekler' => ['Inter', 'Juventus', 'Liverpool', 'Dortmund', 'FCSB (Steaua)'], 'cevap' => 4, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un en genç kaptanı kimdir?', 'secenekler' => ['Uğurcan Çakır', 'Yusuf Yazıcı', 'Fatih Tekke', 'Cemil Usta', 'Abdülkadir Ömür'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un 1976 yılında yendiği Avrupa takımı?', 'secenekler' => ['Benfica', 'Celtic', 'PSV', 'Juventus', 'Ajax'], 'cevap' => 4, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor tarihinde en çok penaltı kullanan oyuncu?', 'secenekler' => ['Hami Mandıralı', 'Fatih Tekke', 'Burak Yılmaz', 'Ali Kemal Denizci', 'Cemil Usta'], 'cevap' => 0, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Süper Lig\'de en az gol yediği sezon?', 'secenekler' => ['1975-76', '1979-80', '1983-84', '2003-04', '2021-22'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Süper Lig\'deki en uzun yenilgi serisi kaç maçtır?', 'secenekler' => ['4', '5', '6', '7', '8'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un 1983-84 sezonunda ligde yediği toplam gol?', 'secenekler' => ['18', '22', '26', '30', '34'], 'cevap' => 1, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Avrupa kupalarında en uzun yenilmezlik serisi?', 'secenekler' => ['5', '7', '9', '11', '13'], 'cevap' => 1, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un ilk golünü kim atmıştır (Süper Lig)?', 'secenekler' => ['Cemil Usta', 'Ali Kemal Denizci', 'Tuncay Bıçakçıoğlu', 'Hayrettin Demirci', 'Güngör Koloğlu'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Süper Lig\'de en fazla penaltı kazandığı sezon?', 'secenekler' => ['2003-04', '2010-11', '2019-20', '2021-22', '2013-14'], 'cevap' => 3, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un kaç farklı ülkeden teknik direktörü olmuştur?', 'secenekler' => ['3', '5', '7', '9', '11'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un tarihindeki en uzun süreli antrenör kim?', 'secenekler' => ['Şenol Güneş', 'Ahmet Suat Özyazıcı', 'Abdullah Avcı', 'Özkan Sümer', 'Ünal Karaman'], 'cevap' => 1, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Süper Lig tarihindeki toplam galibiyet sayısı (yaklaşık)?', 'secenekler' => ['500+', '600+', '700+', '800+', '900+'], 'cevap' => 2, 'zorluk' => 'zor'],
    ['soru' => 'Trabzonspor\'un Türkiye Kupası\'ndaki en farklı galibiyeti?', 'secenekler' => ['5-0', '6-0', '7-0', '8-0', '9-0'], 'cevap' => 3, 'zorluk' => 'zor'],
];
