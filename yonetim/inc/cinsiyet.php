<?php
/**
 * Üye cinsiyet tespiti (liste ikonları ve renkler için).
 *
 * Neden var: aynı kadın isimleri listesi üç dosyada kopya olarak duruyordu ve
 * canlı aramada kayıttaki cinsiyet alanı hiç dikkate alınmıyordu; künyede
 * "Kadın" olan üye aramada erkek ikonuyla görünüyordu. Tek kaynak burasıdır.
 */

/** Kayıttaki cinsiyet değerinin kadın/erkek karşılıkları (küçük harf). */
const CINSIYET_KADIN_DEGERLERI = ['kadın', 'kadin', 'female', 'k'];
const CINSIYET_ERKEK_DEGERLERI = ['erkek', 'male', 'e'];

/** Cinsiyet alanı boşken ad üzerinden tahmin için kullanılan kadın adları (büyük harf). */
const CINSIYET_KADIN_ISIMLERI = [
    'SEMRA','AYŞEGÜL','BEGÜM','HATİCE','FATMA','AYŞE','EMİNE','ZEYNEP','MERYEM','ELİF',
    'HÜLYA','GAMZE','MERVE','BÜŞRA','ESRA','SEDA','DERYA','KÜBRA','ASLI','PELİN','TUĞBA',
    'DEMET','ÖZLEM','SİNEM','GÜL','NUR','MELİS','DİLAN','BURCU','CANAN','SULTAN','MELİKE',
    'YASEMİN','EDA','BERNA','SELEN','PINAR','BANU','YEŞİM','EBRU','FADİME','NURAN','SELMA',
    'DİLEK','FİLİZ','ARZU','LEYLA','SİBEL','HALE','JALE','GONCA','MÜGE','NESLİHAN','NAZLI',
    'MİNE','SELİN','ESMA','FAZİLET','NESRİN','REYHAN','AHSEN','İPEK','ÖZGE','GÜLAY','SÜREYYA',
    'DİDEM','HANDAN','NURTEN','ŞERİFE','SABİHA','ZEHRA','ÜMMÜHAN','RABİA','BÜŞRANUR','FATMANUR',
    'GÜLSÜM','KÜBRANUR','ŞEYMA','BETÜL','SÜMEYYE','KADRİYE','HAVVA','SONGÜL','DÖNDÜ','NURAY',
    'FİRDEVS','AYTEN','AYSEL','GÜLER','NURSEL','NURCAN','MELEK','NURHAN','PERİHAN','SUZAN',
    'SUNA','ŞENNUR','İLKAY','GÜLDEN','GÜLŞAH','SEVAL','SEVİL','SEVİM','NİHAL','NİLÜFER',
    'NİLAY','MELTEM','ÜLKÜ','DUYGU','NURŞEN',
];

/**
 * Üyenin kadın olup olmadığını döndürür.
 *
 * Öncelik: kayıttaki cinsiyet alanı. Alan boş/tanınmıyorsa ilk ada göre tahmin edilir.
 *
 * Girdi: 'cinsiyet' ve 'adi_soyadi' anahtarlı üye satırı.
 * Çıktı: true = kadın, false = erkek/bilinmiyor.
 *
 * @param array<string, mixed> $uye
 */
function uye_kadin_mi(array $uye): bool
{
    $cinsiyet = mb_strtolower(trim((string) ($uye['cinsiyet'] ?? '')), 'UTF-8');

    if (in_array($cinsiyet, CINSIYET_KADIN_DEGERLERI, true)) {
        return true;
    }
    if (in_array($cinsiyet, CINSIYET_ERKEK_DEGERLERI, true)) {
        return false;
    }

    $ilkIsim = mb_strtoupper(explode(' ', trim((string) ($uye['adi_soyadi'] ?? '')))[0], 'UTF-8');

    return in_array($ilkIsim, CINSIYET_KADIN_ISIMLERI, true);
}
