<?php

declare(strict_types=1);

/**
 * Finans kayıtlarını CSV olarak indirir (index.php dosya indirme sayfaları arasında, HTML'den önce çalışır).
 *
 * GET parametreleri: yil (0 = tümü), tur (aidat|bagis|gider|devir|tumu).
 * Aidat satırları kişi adı içerdiğinden yalnızca AIDAT_GORUNTULE yetkisiyle dahil edilir.
 * Excel formül enjeksiyonuna karşı "=", "+", "-", "@" ile başlayan metinler kaçışlanır.
 *
 * @var PDO $db_baglanti
 */

if (($_SESSION['oturum'] ?? false) !== true) {
    http_response_code(403);
    exit('Yetkisiz erişim!');
}

require_once __DIR__ . '/finans/baslat.php';

if (!FinansYetki::oturumIcinVarMi(FinansYetki::DISA_AKTARIM) || !FinansYetki::oturumIcinVarMi(FinansYetki::KASA_GORUNTULE)) {
    http_response_code(403);
    exit('Erişim Engellendi.');
}

const FINANS_CSV_AZAMI_SATIR = 100000;

$istenenTur = (string) ($_GET['tur'] ?? 'tumu');
$turler = $istenenTur === 'tumu'
    ? [FinansDeposu::TUR_AIDAT, FinansDeposu::TUR_BAGIS, FinansDeposu::TUR_GIDER, FinansDeposu::TUR_DEVIR]
    : (in_array($istenenTur, [FinansDeposu::TUR_AIDAT, FinansDeposu::TUR_BAGIS, FinansDeposu::TUR_GIDER, FinansDeposu::TUR_DEVIR], true) ? [$istenenTur] : []);
if (!FinansYetki::oturumIcinVarMi(FinansYetki::AIDAT_GORUNTULE)) {
    $turler = array_values(array_diff($turler, [FinansDeposu::TUR_AIDAT]));
}
$yil = (int) ($_GET['yil'] ?? 0);
$yil = FinansDonem::gecerliMi($yil, 1) ? $yil : 0;

$depo = finans_baslat($db_baglanti)['servis']->depo();

$csvHucresi = static function (mixed $deger): string {
    $metin = (string) ($deger ?? '');

    return preg_match('/^[=+\-@\t\r]/', $metin) === 1 ? "'" . $metin : $metin;
};

$dosyaAdi = 'finans-' . ($istenenTur === 'tumu' ? 'tum-kayitlar' : $istenenTur) . '-' . ($yil > 0 ? $yil : 'tum-yillar') . '-' . date('Ymd') . '.csv';
header('Content-Type: text/csv; charset=UTF-8');
header('Content-Disposition: attachment; filename="' . $dosyaAdi . '"');
header('X-Content-Type-Options: nosniff');

$cikis = fopen('php://output', 'w');
fwrite($cikis, "\xEF\xBB\xBF");
fputcsv($cikis, ['Tür', 'Tarih', 'Dönem', 'Kişi / Taraf', 'Açıklama', 'Tutar (TL)', 'Durum', 'İptal Nedeni', 'Kaynak', 'Kaydeden'], ';', '"', '');

foreach ($turler as $tur) {
    foreach ($depo->hareketler($tur, $yil, true, FINANS_CSV_AZAMI_SATIR, 0) as $h) {
        fputcsv($cikis, [
            $tur,
            date('d.m.Y', strtotime((string) $h['tarih'])),
            FinansDonem::AY_ADLARI[(int) $h['donem_ay']] . ' ' . $h['donem_yil'],
            $csvHucresi($h['aidat_uye_adi'] ?? $h['taraf']),
            $csvHucresi($h['aciklama']),
            number_format(((int) $h['tutar_kurus']) / FinansTutar::KURUS_CARPANI, 2, ',', ''),
            $h['durum'] === 'iptal' ? 'İptal' : 'Aktif',
            $csvHucresi($h['iptal_nedeni']),
            $h['kaynak'],
            $csvHucresi($h['olusturan']),
        ], ';', '"', '');
    }
}
fclose($cikis);

log_kaydet($db_baglanti, 'excel_indir', 'Finans kayıtları CSV olarak indirildi (' . $istenenTur . ', ' . ($yil > 0 ? $yil : 'tüm yıllar') . ').');
