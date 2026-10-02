<?php
/**
 * Tek seferlik DB migration: dernek_uyeler tablosuna ek_roller sütunu ekler.
 * Kullanım: index.php?sayfa=migration-ek-roller
 * Çalıştırdıktan sonra bu dosyayı ve router kaydını siliniz.
 */
require_once __DIR__ . '/../inc/baglan.php';

if (!isset($_SESSION['oturum']) || $_SESSION['oturum'] !== true) {
    http_response_code(403); die('Yetkisiz erişim!');
}
$rol = $_SESSION['rol'] ?? '';
if (!in_array($rol, ['admin', 'gelistirici'], true)) {
    http_response_code(403); die('Yalnızca admin/geliştirici çalıştırabilir.');
}

$mesajlar = [];

try {
    // Sütun zaten var mı kontrol et
    $kontrol = $db_baglanti->query("SHOW COLUMNS FROM dernek_uyeler LIKE 'ek_roller'");
    if ($kontrol->rowCount() > 0) {
        $mesajlar[] = '✅ ek_roller sütunu zaten mevcut — işlem atlandı.';
    } else {
        $db_baglanti->exec(
            "ALTER TABLE dernek_uyeler
             ADD COLUMN ek_roller TEXT NULL DEFAULT NULL
             COMMENT 'JSON dizisi: [\"Kurum Temsilcisi\",\"İlçe Başkanı\"] gibi çoklu roller'"
        );
        $mesajlar[] = '✅ ek_roller sütunu başarıyla eklendi.';
    }
} catch (\PDOException $e) {
    $mesajlar[] = '❌ Hata: ' . htmlspecialchars($e->getMessage());
}

header('Content-Type: text/html; charset=UTF-8');
echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8"><title>Migration</title>
<style>body{font-family:monospace;padding:2rem;background:#0f172a;color:#e2e8f0;}
.ok{color:#4ade80;}.err{color:#f87171;}h2{color:#7dd3fc;}</style></head><body>';
echo '<h2>Migration: ek_roller Sütunu</h2>';
foreach ($mesajlar as $m) {
    $cls = str_starts_with($m, '✅') ? 'ok' : 'err';
    echo '<p class="'.$cls.'">'.htmlspecialchars($m).'</p>';
}
echo '<hr><p style="color:#94a3b8;">Bu sayfayı çalıştırdıktan sonra router kaydını ve dosyayı silebilirsiniz.</p>';
echo '</body></html>';
exit;
