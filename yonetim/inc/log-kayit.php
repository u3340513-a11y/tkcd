<?php

declare(strict_types=1);

/**
 * Merkezi log kayıt fonksiyonu.
 *
 * Yönetim panelindeki tüm kullanıcı işlemlerini `yonetim_log` tablosuna
 * kaydeder. Session verisi otomatik olarak okunur; login öncesi işlemler
 * (başarısız giriş vb.) için parametreler doğrudan geçilebilir.
 *
 * @param PDO         $db            Veritabanı bağlantısı
 * @param string      $islem_turu    İşlem tipi (giris, cikis, uye_onayla vb.)
 * @param string      $aciklama      İnsan tarafından okunabilir açıklama
 * @param string|null $hedef_tablo   Etkilenen tablo adı (opsiyonel)
 * @param int|null    $hedef_id      Etkilenen kayıt ID (opsiyonel)
 * @param string|null $kullanici_adi Session dışı kullanıcı adı (login öncesi)
 */
function log_kaydet(
    PDO     $db,
    string  $islem_turu,
    string  $aciklama,
    ?string $hedef_tablo   = null,
    ?int    $hedef_id      = null,
    ?string $kullanici_adi = null
): void {
    try {
        $yonetici_id = $_SESSION['id']            ?? null;
        $kadi        = $kullanici_adi ?? ($_SESSION['kullanici_adi'] ?? 'bilinmeyen');
        $rol         = $_SESSION['rol']            ?? null;

        $ip = $_SERVER['HTTP_X_FORWARDED_FOR']
            ?? $_SERVER['HTTP_X_REAL_IP']
            ?? $_SERVER['REMOTE_ADDR']
            ?? null;

        // Proxy zincirinde ilk IP'yi al
        if ($ip !== null && str_contains($ip, ',')) {
            $ip = trim(explode(',', $ip)[0]);
        }

        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? null;

        $stmt = $db->prepare(
            "INSERT INTO yonetim_log
                (yonetici_id, kullanici_adi, rol, islem_turu, islem_aciklama,
                 hedef_tablo, hedef_id, ip_adresi, user_agent)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)"
        );

        $stmt->execute([
            $yonetici_id,
            $kadi,
            $rol,
            $islem_turu,
            $aciklama,
            $hedef_tablo,
            $hedef_id,
            $ip,
            $user_agent !== null ? mb_substr($user_agent, 0, 500) : null,
        ]);
    } catch (\PDOException $e) {
        // Log kaydı başarısız olsa bile uygulamayı çökertme
        error_log('[LOG_HATA] ' . $e->getMessage());
    }
}
