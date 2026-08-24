-- ============================================================
-- Geliştirici Rolü & Log Sistemi — Veritabanı Kurulumu
-- ============================================================
-- Bu dosyayı phpMyAdmin veya MySQL CLI'da çalıştırın.
-- ============================================================

-- 1. Log tablosu
CREATE TABLE IF NOT EXISTS yonetim_log (
    id              BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    yonetici_id     INT UNSIGNED DEFAULT NULL       COMMENT 'dernek_yoneticiler.id — login öncesi null olabilir',
    kullanici_adi   VARCHAR(100) NOT NULL            COMMENT 'İşlemi yapan kullanıcı adı',
    rol             VARCHAR(50)  DEFAULT NULL        COMMENT 'İşlem anındaki rol',
    islem_turu      VARCHAR(50)  NOT NULL            COMMENT 'giris, cikis, uye_onayla vb.',
    islem_aciklama  TEXT         NOT NULL            COMMENT 'İnsan tarafından okunabilir açıklama',
    hedef_tablo     VARCHAR(100) DEFAULT NULL        COMMENT 'Etkilenen tablo adı',
    hedef_id        INT UNSIGNED DEFAULT NULL        COMMENT 'Etkilenen kayıt ID',
    ip_adresi       VARCHAR(45)  DEFAULT NULL        COMMENT 'IPv4/IPv6',
    user_agent      TEXT         DEFAULT NULL        COMMENT 'Tarayıcı bilgisi',
    tarih           DATETIME     NOT NULL DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_tarih      (tarih),
    INDEX idx_yonetici   (yonetici_id),
    INDEX idx_islem_turu (islem_turu)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci
  COMMENT='Yönetim paneli audit trail / işlem logları';

-- 2. Geliştirici hesabı (tertekan)
INSERT INTO dernek_yoneticiler (kullanici_adi, sifre, rol)
VALUES (
    'tertekan',
    '$2y$12$.x0CHUsfOxmYN8EsHlUAQ.p81pE.9xmKr5wYZEnMJPiUO3rVjM6xO',
    'gelistirici'
);
