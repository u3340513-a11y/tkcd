-- Mevcut duyurular: aktif=0 olan tüm kayıtları aktif yap
-- Çalıştırma: cPanel phpMyAdmin veya MySQL CLI

UPDATE duyurular SET aktif = 1 WHERE aktif = 0;

-- Kontrol
SELECT id, baslik, aktif, tarih FROM duyurular ORDER BY tarih DESC;
