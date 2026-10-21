<?php

declare(strict_types=1);

/**
 * Finans tablolarını oluşturur (idempotent: tablo varsa dokunmaz).
 *
 * Tasarım kararları (veri kaybını önlemek için):
 *  - Kayıtlar ASLA silinmez; yanlış girilen kayıt `durum = 'iptal'` yapılır
 *    (neden, iptal eden ve zaman damgasıyla). Kasa yalnızca `aktif` kayıtları toplar.
 *  - Tutarlar tam sayı kuruş (BIGINT); float yok.
 *  - `aidat_anahtari`: aynı kişi + yıl + ay için tek AKTİF aidat garantisi (iptal
 *    edilince NULL olur; böylece düzeltme için yeniden girilebilir).
 *  - `kaynak_anahtari`: Excel'den aktarılan satırın parmak izi. İptal edilse bile
 *    silinmediği için aynı CSV tekrar yüklense de kayıt yeniden oluşmaz.
 */
function finans_sema_kur(PDO $db): void
{
    static $kuruldu = false;
    if ($kuruldu) {
        return;
    }

    $db->exec("
        CREATE TABLE IF NOT EXISTS `finans_aidat_uyeleri` (
            `id`        INT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `sira`      SMALLINT UNSIGNED NOT NULL DEFAULT 0,
            `ad_soyad`  VARCHAR(150) NOT NULL,
            `aktif`     TINYINT(1) NOT NULL DEFAULT 1,
            `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_finans_aidat_uye_ad` (`ad_soyad`),
            KEY `idx_finans_aidat_uye_sira` (`aktif`, `sira`)
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $db->exec("
        CREATE TABLE IF NOT EXISTS `finans_hareketler` (
            `id`               BIGINT UNSIGNED NOT NULL AUTO_INCREMENT PRIMARY KEY,
            `tur`              VARCHAR(10) NOT NULL COMMENT 'aidat, bagis, gider, devir',
            `tarih`            DATE NOT NULL,
            `donem_yil`        SMALLINT UNSIGNED NOT NULL,
            `donem_ay`         TINYINT UNSIGNED NOT NULL,
            `tutar_kurus`      BIGINT UNSIGNED NOT NULL,
            `aidat_uye_id`     INT UNSIGNED NULL,
            `taraf`            VARCHAR(190) NULL COMMENT 'Bağışçı firma / ödenen yer',
            `aciklama`         VARCHAR(500) NULL,
            `kaynak`           VARCHAR(10) NOT NULL DEFAULT 'manuel' COMMENT 'manuel veya excel',
            `kaynak_anahtari`  CHAR(64) NULL,
            `aidat_anahtari`   VARCHAR(40) NULL,
            `durum`            VARCHAR(10) NOT NULL DEFAULT 'aktif' COMMENT 'aktif veya iptal',
            `iptal_nedeni`     VARCHAR(300) NULL,
            `iptal_eden`       VARCHAR(100) NULL,
            `iptal_tarihi`     DATETIME NULL,
            `olusturan`        VARCHAR(100) NOT NULL,
            `olusturma_tarihi` DATETIME NOT NULL DEFAULT CURRENT_TIMESTAMP,
            UNIQUE KEY `uq_finans_kaynak_anahtari` (`kaynak_anahtari`),
            UNIQUE KEY `uq_finans_aidat_anahtari` (`aidat_anahtari`),
            KEY `idx_finans_donem` (`donem_yil`, `donem_ay`, `tur`, `durum`),
            KEY `idx_finans_tur_tarih` (`tur`, `durum`, `tarih`),
            KEY `idx_finans_aidat_uye` (`aidat_uye_id`),
            CONSTRAINT `fk_finans_aidat_uye` FOREIGN KEY (`aidat_uye_id`)
                REFERENCES `finans_aidat_uyeleri` (`id`) ON DELETE RESTRICT
        ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
    ");

    $kuruldu = true;
}
