<?php

declare(strict_types=1);

/**
 * Finans modülünü yükler ve servisleri oluşturur (bağımlılık enjeksiyonu birleşim noktası).
 *
 * Kullanım: $finans = finans_baslat($db_baglanti);  // ['servis' => ..., 'aktarim' => ..., 'istek' => ...]
 * log_kaydet() (inc/log-kayit.php) önceden yüklenmiş olmalıdır.
 */

require_once __DIR__ . '/bicim.php';
require_once __DIR__ . '/yetki.php';
require_once __DIR__ . '/sema.php';
require_once __DIR__ . '/deposu.php';
require_once __DIR__ . '/servis.php';
require_once __DIR__ . '/csv-ayristirici.php';
require_once __DIR__ . '/aktarim.php';
require_once __DIR__ . '/istek.php';

/**
 * @return array{servis: FinansServisi, aktarim: FinansAktarimServisi, istek: FinansIstekYoneticisi}
 */
function finans_baslat(PDO $db): array
{
    finans_sema_kur($db);

    $depo = new FinansDeposu($db);
    $gunluk = static function (string $islemTuru, string $aciklama, ?int $hedefId) use ($db): void {
        log_kaydet($db, $islemTuru, $aciklama, 'finans_hareketler', $hedefId);
    };
    $servis  = new FinansServisi($depo, $gunluk);
    $aktarim = new FinansAktarimServisi($depo, $gunluk);
    $istek   = new FinansIstekYoneticisi(
        $servis,
        $aktarim,
        static fn (string $yetki): bool => FinansYetki::oturumIcinVarMi($yetki),
        (string) ($_SESSION['kullanici_adi'] ?? 'bilinmeyen')
    );

    return ['servis' => $servis, 'aktarim' => $aktarim, 'istek' => $istek];
}
