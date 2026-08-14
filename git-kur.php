<?php
/**
 * Tek kullanımlık Git kurulum scripti.
 * Çalıştıktan sonra kendini otomatik olarak siler.
 * 
 * Kullanım: tarayıcıda /git-kur.php adresine gidin.
 */

// Güvenlik: sadece sunucudan çalıştırılabilir
if (php_sapi_name() === 'cli') {
    echo "Bu script tarayıcıdan çalıştırılmalıdır.\n";
    exit(1);
}

header('Content-Type: text/plain; charset=utf-8');

$repoUrl  = 'https://github.com/u3340513-a11y/tkcd.git';
$repoPath = __DIR__;

echo "=== Git Kurulum Scripti ===\n\n";

// 1. Mevcut .git varsa temizle
$gitDir = $repoPath . '/.git';
if (is_dir($gitDir)) {
    echo "[1] Mevcut .git klasoru siliniyor...\n";
    silDizin($gitDir);
    echo "    Silindi.\n";
} else {
    echo "[1] .git klasoru bulunamadi, temiz kurulum.\n";
}

// 2. git init
echo "[2] git init calistiriliyor...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git init 2>&1");
echo "    " . trim($sonuc) . "\n";

// 3. Remote ekle
echo "[3] Remote ekleniyor...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git remote add origin " . escapeshellarg($repoUrl) . " 2>&1");
echo "    " . (trim($sonuc) ?: "origin eklendi") . "\n";

// 4. Fetch
echo "[4] Fetch yapiliyor (bu biraz surabilir)...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git fetch origin 2>&1");
echo "    " . (trim($sonuc) ?: "Fetch tamamlandi") . "\n";

// 5. Reset to main
echo "[5] main branch'e reset yapiliyor...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git checkout -B main origin/main 2>&1");
echo "    " . trim($sonuc) . "\n";

// 6. Tracking ayarla
echo "[6] Tracking ayarlaniyor...\n";
$sonuc = shell_exec("cd " . escapeshellarg($repoPath) . " && git branch --set-upstream-to=origin/main main 2>&1");
echo "    " . trim($sonuc) . "\n";

// 7. Bu scripti sil
echo "\n[7] Bu script kendini siliyor...\n";
$scriptPath = __FILE__;
if (@unlink($scriptPath)) {
    echo "    git-kur.php silindi. Guvenlik saglandi.\n";
} else {
    echo "    UYARI: Script silinemedi! Lutfen manuel silin: " . basename($scriptPath) . "\n";
}

echo "\n=== TAMAMLANDI ===\n";
echo "Artik cPanel Git Version Control'de repo gorunur olmalidir.\n";
echo "Gelecekte git pull ile guncelleme yapabilirsiniz.\n";

/**
 * Dizini ve tüm içeriğini özyinelemeli olarak siler.
 */
function silDizin(string $yol): void
{
    if (!is_dir($yol)) {
        return;
    }
    $icerik = new RecursiveIteratorIterator(
        new RecursiveDirectoryIterator($yol, RecursiveDirectoryIterator::SKIP_DOTS),
        RecursiveIteratorIterator::CHILD_FIRST
    );
    foreach ($icerik as $dosya) {
        if ($dosya->isDir()) {
            @rmdir($dosya->getRealPath());
        } else {
            @unlink($dosya->getRealPath());
        }
    }
    @rmdir($yol);
}
