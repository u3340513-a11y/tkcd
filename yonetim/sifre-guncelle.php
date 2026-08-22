<?php
/**
 * Tek kullanımlık şifre güncelleme scripti.
 * İşlem sonrası bu dosyayı MUTLAKA siliniz.
 */

require_once __DIR__ . '/inc/baglan.php';

$kullanicilar = [
    'admin61' => 'TKC.61.tkc!',
];

echo "<pre style='font-family:monospace; font-size:14px; padding:20px;'>";

$guncelleSorgu = $db_baglanti->prepare(
    "UPDATE dernek_yoneticiler SET sifre = ? WHERE kullanici_adi = ?"
);

$dogrulaSorgu = $db_baglanti->prepare(
    "SELECT sifre FROM dernek_yoneticiler WHERE kullanici_adi = ?"
);

foreach ($kullanicilar as $kullaniciAdi => $yeniSifre) {
    echo "── {$kullaniciAdi} ──────────────────────────\n";

    $yeniHash = password_hash($yeniSifre, PASSWORD_DEFAULT);
    $guncelleSorgu->execute([$yeniHash, $kullaniciAdi]);
    $etkilenen = $guncelleSorgu->rowCount();
    echo "Güncellenen satır: {$etkilenen}\n";

    $dogrulaSorgu->execute([$kullaniciAdi]);
    $kaydedilen = $dogrulaSorgu->fetchColumn();

    $sonuc = password_verify($yeniSifre, $kaydedilen);
    echo "Doğrulama: " . ($sonuc ? '✅ BAŞARILI' : '❌ BAŞARISIZ') . "\n\n";
}

echo "⚠️  BU DOSYAYI SUNUCUDAN SİLMEYİ UNUTMAYIN!\n";
echo "</pre>";
