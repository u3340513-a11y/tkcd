<?php

declare(strict_types=1);

/**
 * Finans modülü testleri (harici bağımlılık yok).
 *
 * Çalıştırma:
 *   php scripts/finans-test.php
 *   FINANS_TEST_DSN="mysql:host=127.0.0.1;port=3399;dbname=finans_test;charset=utf8mb4" \
 *   FINANS_TEST_USER=root FINANS_TEST_PASS= php scripts/finans-test.php
 *
 * DSN verilmezse yalnızca saf fonksiyon testleri çalışır. Veritabanı testleri
 * hedef şemadaki finans_* tablolarını SİLİP yeniden oluşturur: asla canlı veritabanına yöneltmeyin.
 */

require_once __DIR__ . '/../yonetim/inc/finans/bicim.php';
require_once __DIR__ . '/../yonetim/inc/finans/yetki.php';
require_once __DIR__ . '/../yonetim/inc/finans/sema.php';
require_once __DIR__ . '/../yonetim/inc/finans/deposu.php';
require_once __DIR__ . '/../yonetim/inc/finans/servis.php';
require_once __DIR__ . '/../yonetim/inc/finans/csv-ayristirici.php';
require_once __DIR__ . '/../yonetim/inc/finans/aktarim.php';

$basarisiz = 0;
$toplam = 0;

function esit(mixed $beklenen, mixed $gercek, string $ad): void
{
    global $basarisiz, $toplam;
    $toplam++;
    if ($beklenen !== $gercek) {
        $basarisiz++;
        echo "BAŞARISIZ: {$ad}\n  beklenen: " . var_export($beklenen, true) . "\n  gerçek  : " . var_export($gercek, true) . "\n";
    }
}

// ── Tutar ───────────────────────────────────────────────────────────────
esit(150000, FinansTutar::kurusaCevir('1.500,00'), 'tutar: binlik+kuruş');
esit(150000, FinansTutar::kurusaCevir('₺ 1.500,00 '), 'tutar: sembol ve boşluk');
esit(100000, FinansTutar::kurusaCevir('₺1.000'), 'tutar: sembol bitişik');
esit(150050, FinansTutar::kurusaCevir('1500,5'), 'tutar: tek haneli kuruş');
esit(null, FinansTutar::kurusaCevir('₺ -   '), 'tutar: tire = boş');
esit(null, FinansTutar::kurusaCevir('0'), 'tutar: sıfır reddedilir');
esit(null, FinansTutar::kurusaCevir('-5'), 'tutar: negatif reddedilir');
esit(null, FinansTutar::kurusaCevir('1.5'), 'tutar: belirsiz ondalık reddedilir');
esit(null, FinansTutar::kurusaCevir('abc'), 'tutar: metin reddedilir');
esit(null, FinansTutar::kurusaCevir('1000000000'), 'tutar: azami aşımı reddedilir');
esit(true, FinansTutar::bosMu('₺ -   '), 'bosMu: tire');
esit(false, FinansTutar::bosMu('abc'), 'bosMu: metin boş değil');
esit('₺90.526,00', FinansTutar::bicimle(9052600), 'biçim: pozitif');
esit('-₺1.500,05', FinansTutar::bicimle(-150005), 'biçim: negatif');

// ── Tarih / dönem ───────────────────────────────────────────────────────
esit('2026-01-15', FinansDonem::tarihiNormallestir('15.01.2026'), 'tarih: GG.AA.YYYY');
esit('2026-01-05', FinansDonem::tarihiNormallestir('5/1/2026'), 'tarih: tek haneli');
esit('2026-01-15', FinansDonem::tarihiNormallestir('2026-01-15'), 'tarih: ISO');
esit(null, FinansDonem::tarihiNormallestir('31.02.2026'), 'tarih: geçersiz gün');
esit(null, FinansDonem::tarihiNormallestir('abc'), 'tarih: metin');

// ── Yetki ───────────────────────────────────────────────────────────────
esit(true, FinansYetki::izinliMi(FinansYetki::KASA_GORUNTULE, 'gelistirici', 'x'), 'yetki: geliştirici kasa');
esit(false, FinansYetki::izinliMi(FinansYetki::KASA_GORUNTULE, 'admin', 'admin61'), 'yetki: admin61 şimdilik yok');
esit(false, FinansYetki::izinliMi(FinansYetki::AIDAT_GORUNTULE, 'yonetim', 'yonetim_hk'), 'yetki: yonetim_hk aidat yok');
esit(false, FinansYetki::izinliMi('bilinmeyen', 'gelistirici', 'x'), 'yetki: bilinmeyen yetki reddedilir');

// ── CSV ayrıştırma ──────────────────────────────────────────────────────
$aidatCsv = implode("\n", [
    '"Yönetim Aidat Listesi","","","","","","","","","","","","","",""',
    '"","","2026","","","","","","","","","","","","2027"',
    '"","İsim Soyisim","","","","","","","","","","EKİM","KASIM","ARALIK",""',
    '"1","ALİ VELİ","₺1.000","₺ 1.000,00","","","","","","","","","","",""',
    '"2","AYŞE FATMA","","₺ 1.500,00","","","","","","","","","","",""',
    '"3","BOŞ KİŞİ","","","","","","","","","","","","",""',
    '"","AYLIK TOPLAM","₺ 1.500,00","₺ 2.500,00","","","","","","","","","","",""',
]);
$a = FinansCsvAyristirici::aidatListesi(FinansCsvAyristirici::satirlar($aidatCsv));
esit([], $a['hatalar'], 'aidat csv: hata yok');
esit(3, count($a['uyeler']), 'aidat csv: 3 kişi');
esit(3, count($a['odemeler']), 'aidat csv: 3 ödeme');
esit(['yil' => 2026, 'ay' => 1, 'sayfa' => 150000, 'hesaplanan' => 100000, 'fark' => 50000], $a['farklar'][0], 'aidat csv: Ocak farkı');
esit(1, count($a['farklar']), 'aidat csv: yalnızca bir fark');

$kayitCsv = "\"BAĞIŞ KAYITLARI açıklama\nTarih\",\"Firma Adı\",\"Açıklama\",\"Bağış Miktarı (TL)\"\n"
    . "\"15.01.2026\",\"ABC Ltd.\",\"Not\",\"₺ 5.000,00\"\n\"31.02.2026\",\"Hatalı\",\"\",\"₺ 1,00\"\n\"\",\"\",\"\",\"\"\n";
$k = FinansCsvAyristirici::kayitListesi(FinansCsvAyristirici::satirlar($kayitCsv));
esit(1, count($k['kayitlar']), 'kayıt csv: 1 geçerli satır');
esit(1, count($k['uyarilar']), 'kayıt csv: geçersiz satır uyarı verir (sessiz kayıp yok)');
esit(500000, $k['kayitlar'][0]['kurus'], 'kayıt csv: tutar');
esit(true, count(FinansCsvAyristirici::aidatListesi([['x']])['hatalar']) === 1, 'aidat csv: yapı bozuksa hata');

// ── Veritabanı entegrasyonu ─────────────────────────────────────────────
$dsn = getenv('FINANS_TEST_DSN');
if ($dsn !== false && $dsn !== '') {
    $db = new PDO($dsn, (string) getenv('FINANS_TEST_USER'), (string) getenv('FINANS_TEST_PASS'), [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]);
    $db->exec('SET FOREIGN_KEY_CHECKS = 0');
    $db->exec('DROP TABLE IF EXISTS finans_hareketler');
    $db->exec('DROP TABLE IF EXISTS finans_aidat_uyeleri');
    $db->exec('SET FOREIGN_KEY_CHECKS = 1');
    finans_sema_kur($db);

    $gunlukler = [];
    $gunluk = static function (string $tur, string $aciklama, ?int $id) use (&$gunlukler): void {
        $gunlukler[] = $tur;
    };
    $depo = new FinansDeposu($db);
    $servis = new FinansServisi($depo, $gunluk);
    $aktarim = new FinansAktarimServisi($depo, $gunluk);

    // Kasa boşken
    esit(0, $servis->kasaOzeti()['bakiye'], 'db: boş kasa');

    // Kişi + aidat
    esit(true, $servis->aidatUyesiEkle('TEST KİŞİ')->basarili, 'db: kişi eklenir');
    esit(false, $servis->aidatUyesiEkle('TEST KİŞİ')->basarili, 'db: aynı kişi reddedilir');
    $uyeId = $depo->aidatUyeleri()[0]['id'];
    $girdi = ['aidat_uye_id' => $uyeId, 'yil' => 2026, 'ay' => 9, 'tarih' => '10.09.2026', 'tutar' => '1.500,00'];
    esit(true, $servis->aidatKaydet($girdi, 'test')->basarili, 'db: aidat kaydı');
    esit(false, $servis->aidatKaydet($girdi, 'test')->basarili, 'db: aynı ay ikinci aidat reddedilir');
    esit(false, $servis->aidatKaydet(['yil' => 2026, 'ay' => 13] + $girdi, 'test')->basarili, 'db: geçersiz ay reddedilir');
    esit(false, $servis->aidatKaydet(['tutar' => 'abc'] + $girdi, 'test')->basarili, 'db: geçersiz tutar reddedilir');

    // Bağış / gider ve otomatik kasa
    $servis->bagisGiderKaydet('bagis', ['tarih' => '15.01.2026', 'taraf' => 'ABC', 'tutar' => '5.000,00'], 'test');
    $servis->bagisGiderKaydet('gider', ['tarih' => '20.01.2026', 'taraf' => 'XYZ', 'tutar' => '1.800,00'], 'test');
    $oz = $servis->kasaOzeti();
    esit(150000 + 500000 - 180000, $oz['bakiye'], 'db: kasa = gelir - gider');
    esit(false, $servis->bagisGiderKaydet('gider', ['tarih' => '20.01.2026', 'taraf' => '', 'tutar' => '1'], 'test')->basarili, 'db: firma zorunlu');

    // İptal: silinmez, kasadan düşer, aynı ay yeniden girilebilir
    $aidatId = (int) $depo->hareketler('aidat', 2026, false, 10, 0)[0]['id'];
    esit(false, $servis->iptalEt($aidatId, 'a', 'test')->basarili, 'db: kısa iptal nedeni reddedilir');
    esit(true, $servis->iptalEt($aidatId, 'Yanlış tutar girildi', 'test')->basarili, 'db: iptal');
    esit(false, $servis->iptalEt($aidatId, 'Yanlış tutar girildi', 'test')->basarili, 'db: çift iptal reddedilir');
    esit(500000 - 180000, $servis->kasaOzeti()['bakiye'], 'db: iptal sonrası kasa düzelir');
    esit(1, $depo->hareketSayisi('aidat', 2026, true), 'db: iptal edilen kayıt saklanır (veri kaybı yok)');
    esit(true, $servis->aidatKaydet(['tutar' => '1.000,00'] + $girdi, 'test')->basarili, 'db: iptalden sonra düzeltme girilir');

    // Aylık özet
    $aylik = $depo->aylikToplamlar(2026);
    esit(100000, $aylik[9]['aidat']['kurus'], 'db: eylül aidat toplamı');
    esit(500000, $aylik[1]['bagis']['kurus'], 'db: ocak bağış toplamı');

    // Excel aktarımı: ilk seferde yazar, ikinci seferde mükerrer oluşturmaz
    $kayitlar = [['tarih' => '2026-02-03', 'taraf' => 'Firma', 'aciklama' => '', 'kurus' => 12345]];
    $oncekiBakiye = $servis->kasaOzeti()['bakiye'];
    $s1 = $aktarim->uygula($a, ['net'], $kayitlar, [], 'test');
    esit(3, $s1['aidat_eklendi'], 'aktarım: aidatlar eklenir');
    esit(1, $s1['devir_eklendi'], 'aktarım: seçilen fark devir olur');
    esit(1, $s1['bagis_eklendi'], 'aktarım: bağış eklenir');
    esit($oncekiBakiye + 100000 + 100000 + 150000 + 50000 + 12345, $servis->kasaOzeti()['bakiye'], 'aktarım: kasa doğru artar');
    $s2 = $aktarim->uygula($a, ['net'], $kayitlar, [], 'test');
    esit([0, 3, 1, 1], [$s2['aidat_eklendi'], $s2['aidat_zaten'], $s2['devir_zaten'], $s2['bagis_zaten']], 'aktarım: ikinci yükleme mükerrer üretmez');

    // Kasa toplamı kayıtlarla birebir tutarlı mı?
    $elle = (int) $db->query(
        "SELECT SUM(CASE WHEN tur='gider' THEN -tutar_kurus ELSE tutar_kurus END) FROM finans_hareketler WHERE durum='aktif'"
    )->fetchColumn();
    esit($elle, $servis->kasaOzeti()['bakiye'], 'tutarlılık: bakiye = aktif kayıtların net toplamı');

    // Hatada tam geri alma
    $once = (int) $db->query('SELECT COUNT(*) FROM finans_hareketler')->fetchColumn();
    $bozuk = $a;
    $bozuk['odemeler'][] = ['ad' => 'YOK KİŞİ', 'yil' => 2026, 'ay' => 5, 'kurus' => 100];
    try {
        $aktarim->uygula($bozuk, [], [], [], 'test');
    } catch (Throwable) {
        // beklenen: bilinmeyen kişi nedeniyle istisna
    }
    esit($once, (int) $db->query('SELECT COUNT(*) FROM finans_hareketler')->fetchColumn(), 'aktarım: hatada hiçbir kayıt yazılmaz (rollback)');
    esit(false, $db->inTransaction(), 'aktarım: açık transaction kalmaz');
} else {
    echo "(FINANS_TEST_DSN verilmedi: veritabanı testleri atlandı)\n";
}

echo ($basarisiz === 0 ? 'TÜM TESTLER GEÇTİ' : $basarisiz . ' TEST BAŞARISIZ') . " ({$toplam} doğrulama)\n";
exit($basarisiz === 0 ? 0 : 1);
