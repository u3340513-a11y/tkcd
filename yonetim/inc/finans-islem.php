<?php

declare(strict_types=1);

/**
 * Finans POST girişi (index.php, HTML çıktısından ÖNCE çağırır).
 *
 * Akış: oturum/yetki → CSRF → FinansIstekYoneticisi → flash mesajı → PRG yönlendirmesi.
 * Yönlendirme adresi yalnızca beyaz listedeki sekme adlarından ve tam sayı yıldan üretilir
 * (açık yönlendirme / başlık enjeksiyonu mümkün değildir).
 *
 * @var PDO $db_baglanti
 */

if (($_SESSION['oturum'] ?? false) !== true) {
    http_response_code(403);
    exit('Yetkisiz erişim!');
}

require_once __DIR__ . '/finans/baslat.php';

$finansYil = (int) ($_POST['yil'] ?? date('Y'));

if (!FinansYetki::menuGorunurMu() || !csrf_token_dogrula((string) ($_POST['csrf_token'] ?? ''))) {
    $_SESSION['finans_flash'] = ['tip' => 'danger', 'mesaj' => 'İstek doğrulanamadı veya yetkiniz yok.'];
    header('Location: index.php?sayfa=finans');
    exit;
}

$finansSonuc = finans_baslat($db_baglanti)['istek']->isle($_POST, $_FILES, $_SESSION);
$_SESSION['finans_flash'] = ['tip' => $finansSonuc['tip'], 'mesaj' => $finansSonuc['mesaj']];

header('Location: index.php?sayfa=finans&sekme=' . rawurlencode($finansSonuc['sekme']) . '&yil=' . $finansSonuc['yil']);
exit;
