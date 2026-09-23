<?php
/**
 * Tek kullanımlık Git pull scripti.
 * Çalıştıktan sonra kendini otomatik olarak siler.
 *
 * Kullanım: tarayıcıda /git-pull.php adresine gidin.
 */

if (php_sapi_name() === 'cli') {
    echo "Bu script tarayıcıdan çalıştırılmalıdır.\n";
    exit(1);
}

header('Content-Type: text/plain; charset=utf-8');

$repoPath = __DIR__;

echo "=== Git Pull Scripti ===\n\n";

// git pull origin main
echo "[1] git pull origin main calistiriliyor...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git pull origin main 2>&1");
echo $sonuc . "\n";

// Bu scripti sil
echo "[2] Script kendini siliyor...\n";
if (@unlink(__FILE__)) {
    echo "    git-pull.php silindi. Guvenlik saglandi.\n";
} else {
    echo "    UYARI: Script silinemedi! Lutfen manuel silin.\n";
}

echo "\n=== TAMAMLANDI ===\n";
