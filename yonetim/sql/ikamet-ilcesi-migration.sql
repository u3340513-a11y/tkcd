-- =====================================================================
-- Üye Bilgi Güncelleme Sistemi — Veritabanı Migrasyonu
-- Tarih: 2026-08-25
-- Açıklama: dernek_uyeler tablosuna ikamet_ilcesi kolonu eklenir.
-- =====================================================================

ALTER TABLE dernek_uyeler
ADD COLUMN ikamet_ilcesi VARCHAR(100) DEFAULT NULL
AFTER ikamet_ili;
