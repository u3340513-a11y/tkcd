<?php
/**
 * Tek kullanımlık .git klasörü silme scripti.
 * Çalıştıktan sonra kendini otomatik olarak siler.
 *
 * Kullanım: tarayıcıda /git-sil.php adresine gidin.
 */

header('Content-Type: text/plain; charset=utf-8');

$gitDir = __DIR__ . '/.git';

echo "=== .git Silme Scripti ===\n\n";

if (!is_dir($gitDir)) {
    echo "[!] .git klasoru bulunamadi. Zaten silinmis olabilir.\n";
} else {
    echo "[1] .git klasoru siliniyor...\n";
    silDizin($gitDir);

    if (is_dir($gitDir)) {
        echo "    HATA: .git klasoru silinemedi!\n";
    } else {
        echo "    .git klasoru basariyla silindi.\n";
    }
}

echo "\n[2] Bu script kendini siliyor...\n";
if (@unlink(__FILE__)) {
    echo "    git-sil.php silindi. Guvenlik saglandi.\n";
} else {
    echo "    UYARI: Script silinemedi! Lutfen manuel silin: git-sil.php\n";
}

echo "\n=== TAMAMLANDI ===\n";
echo "Artik cPanel Git Version Control'den yeniden kurulum yapabilirsiniz.\n";

/**
 * Dizini ve tüm içeriğini özyinelemeli olarak siler.
 *
 * @param string $dizin Silinecek dizin yolu
 */
function silDizin(string $dizin): void
{
    if (!is_dir($dizin)) {
        return;
    }

    $icerik = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($dizin, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );

    foreach ($icerik as $nesne) {
        if ($nesne->isDir()) {
            rmdir($nesne->getRealPath());
        } else {
            unlink($nesne->getRealPath());
        }
    }

    rmdir($dizin);
}
