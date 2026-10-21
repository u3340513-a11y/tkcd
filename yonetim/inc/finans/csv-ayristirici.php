<?php

declare(strict_types=1);

/**
 * Google Sheets / Excel'den "CSV olarak indirilen" sayfaların okunması (saf fonksiyonlar; veritabanına dokunmaz).
 *
 * Neden var: Aidat, Bağış Kayıtları ve Gider Kayıtları sayfaları veritabanına eksiksiz
 * taşınmalı. Okuyucu hiçbir hücreyi sessizce atmaz: okunamayan her hücre/satır
 * `uyarilar` listesinde satır numarasıyla bildirilir; yapı beklenenden farklıysa
 * `hatalar` dolar ve aktarım yapılmaz.
 */
final class FinansCsvAyristirici
{
    /** Ay başlıkları (büyük harf); aidat sayfasında konum doğrulaması için. */
    private const AY_BASLIKLARI = [
        1 => 'OCAK', 2 => 'ŞUBAT', 3 => 'MART', 4 => 'NİSAN', 5 => 'MAYIS', 6 => 'HAZİRAN',
        7 => 'TEMMUZ', 8 => 'AĞUSTOS', 9 => 'EYLÜL', 10 => 'EKİM', 11 => 'KASIM', 12 => 'ARALIK',
    ];

    private const ISIM_BASLIGI  = 'İSİM SOYİSİM';
    private const MIKTAR_BASLIGI = 'MİKTAR';
    private const TOPLAM_ANAHTARI = 'TOPLAM';

    /**
     * CSV metnini satır/hücre dizisine çevirir (UTF-8 BOM atılır, Windows-1254 UTF-8'e çevrilir,
     * ayraç `,` `;` veya sekme olarak otomatik bulunur, tırnak içi satır sonları korunur).
     *
     * @return array<int, array<int, string>>
     */
    public static function satirlar(string $icerik): array
    {
        if (str_starts_with($icerik, "\xEF\xBB\xBF")) {
            $icerik = substr($icerik, 3);
        }
        if (!mb_check_encoding($icerik, 'UTF-8')) {
            $icerik = mb_convert_encoding($icerik, 'UTF-8', 'Windows-1254');
        }

        $ayrac = self::ayraciBul($icerik);
        $akis  = fopen('php://temp', 'r+');
        fwrite($akis, $icerik);
        rewind($akis);

        $satirlar = [];
        while (($hucreler = fgetcsv($akis, 0, $ayrac, '"', '')) !== false) {
            $satirlar[] = array_map(static fn (?string $h): string => (string) $h, $hucreler);
        }
        fclose($akis);

        return $satirlar;
    }

    private static function ayraciBul(string $icerik): string
    {
        $ornek = substr($icerik, 0, 4096);
        $adaylar = [',' => substr_count($ornek, ','), ';' => substr_count($ornek, ';'), "\t" => substr_count($ornek, "\t")];
        arsort($adaylar);

        return (string) array_key_first($adaylar);
    }

    /**
     * Aidat matrisi sayfasını ayrıştırır.
     *
     * Beklenen yapı: bir satırda yıl başlıkları (2026, 2027 ...; her yıl 12 sütunluk blok başlatır),
     * "İsim Soyisim" başlık satırı, kişi satırları, "AYLIK TOPLAM" satırı.
     *
     * @param array<int, array<int, string>> $satirlar
     * @return array{
     *   uyeler: array<int, array{sira:int, ad:string}>,
     *   odemeler: array<int, array{ad:string, yil:int, ay:int, kurus:int}>,
     *   sayfa_toplamlari: array<int, array<int, int>>,
     *   hesaplanan_toplamlar: array<int, array<int, int>>,
     *   farklar: array<int, array{yil:int, ay:int, sayfa:int, hesaplanan:int, fark:int}>,
     *   uyarilar: string[], hatalar: string[]
     * }
     */
    public static function aidatListesi(array $satirlar): array
    {
        $sonuc = ['uyeler' => [], 'odemeler' => [], 'sayfa_toplamlari' => [], 'hesaplanan_toplamlar' => [],
                  'farklar' => [], 'uyarilar' => [], 'hatalar' => []];

        $baslik = self::satirBul($satirlar, 0, static fn (string $h): bool => self::buyukHarf($h) === self::ISIM_BASLIGI);
        if ($baslik === null) {
            $sonuc['hatalar'][] = '"İsim Soyisim" başlık satırı bulunamadı. Aidat sayfasının CSV çıktısı olduğundan emin olun.';

            return $sonuc;
        }
        [$baslikSatiri, $adSutunu] = $baslik;

        $yilBloklari = self::yilBloklari($satirlar, $baslikSatiri);
        if ($yilBloklari === []) {
            $sonuc['hatalar'][] = 'Başlık satırının üstünde yıl (örn. 2026) hücresi bulunamadı.';

            return $sonuc;
        }
        $hata = self::ayBasliklariniDogrula($satirlar[$baslikSatiri], $yilBloklari);
        if ($hata !== null) {
            $sonuc['hatalar'][] = $hata;

            return $sonuc;
        }

        $gorulen = [];
        for ($i = $baslikSatiri + 1, $adet = count($satirlar); $i < $adet; $i++) {
            $satir = $satirlar[$i];
            $ad = self::adiTemizle($satir[$adSutunu] ?? '');
            if (str_contains(self::buyukHarf($ad), self::TOPLAM_ANAHTARI)) {
                self::sayfaToplamlariniOku($satir, $yilBloklari, $i + 1, $sonuc);
                break;
            }
            if ($ad === '') {
                continue;
            }
            if (isset($gorulen[$ad])) {
                $sonuc['hatalar'][] = 'Satır ' . ($i + 1) . ': "' . $ad . '" listede birden fazla kez geçiyor.';
                continue;
            }
            $gorulen[$ad] = true;
            $sira = ctype_digit(trim($satir[$adSutunu - 1] ?? '')) ? (int) trim($satir[$adSutunu - 1]) : count($sonuc['uyeler']) + 1;
            $sonuc['uyeler'][] = ['sira' => $sira, 'ad' => $ad];
            self::odemeleriOku($satir, $ad, $yilBloklari, $i + 1, $sonuc);
        }

        if ($sonuc['uyeler'] === []) {
            $sonuc['hatalar'][] = 'Aidat listesinde hiç kişi bulunamadı.';
        }
        self::farklariHesapla($sonuc);

        return $sonuc;
    }

    /**
     * Bağış / Gider Kayıtları sayfasını ayrıştırır (sütunlar: Tarih, Firma/Yer, Açıklama, Miktar).
     *
     * @param array<int, array<int, string>> $satirlar
     * @return array{
     *   kayitlar: array<int, array{satir:int, tarih:string, taraf:string, aciklama:string, kurus:int}>,
     *   uyarilar: string[], hatalar: string[]
     * }
     */
    public static function kayitListesi(array $satirlar): array
    {
        $sonuc = ['kayitlar' => [], 'uyarilar' => [], 'hatalar' => []];

        $baslik = self::satirBul($satirlar, 0, static fn (string $h): bool => str_contains(self::buyukHarf($h), self::MIKTAR_BASLIGI));
        if ($baslik === null) {
            $sonuc['hatalar'][] = '"Miktar" başlıklı sütun bulunamadı. Bağış/Gider Kayıtları sayfasının CSV çıktısı olduğundan emin olun.';

            return $sonuc;
        }

        for ($i = $baslik[0] + 1, $adet = count($satirlar); $i < $adet; $i++) {
            $satir = array_pad($satirlar[$i], 4, '');
            [$tarihHam, $taraf, $aciklama, $tutarHam] = array_map('trim', array_slice($satir, 0, 4));
            if ($tarihHam === '' && $taraf === '' && $aciklama === '' && FinansTutar::bosMu($tutarHam)) {
                continue;
            }

            $tarih = FinansDonem::tarihiNormallestir($tarihHam);
            $kurus = FinansTutar::kurusaCevir($tutarHam);
            $taraf = FinansServisi::metniTemizle($taraf);
            if ($tarih === null || $kurus === null || $taraf === '' || mb_strlen($taraf) > FinansServisi::AZAMI_TARAF) {
                $sonuc['uyarilar'][] = 'Satır ' . ($i + 1) . ' aktarılmayacak (tarih/firma/tutar okunamadı): "'
                    . implode(' | ', array_slice($satir, 0, 4)) . '"';
                continue;
            }

            $sonuc['kayitlar'][] = [
                'satir' => $i + 1, 'tarih' => $tarih, 'taraf' => $taraf,
                'aciklama' => mb_substr(FinansServisi::metniTemizle($aciklama), 0, FinansServisi::AZAMI_ACIKLAMA),
                'kurus' => $kurus,
            ];
        }

        return $sonuc;
    }

    // ── İç yardımcılar ──────────────────────────────────────────────────

    /** Türkçe duyarlı büyük harf (mb_strtoupper "i" harfini "I" yapar; burada "İ" olur). */
    private static function buyukHarf(string $metin): string
    {
        return mb_strtoupper(strtr(trim($metin), ['i' => 'İ', 'ı' => 'I']), 'UTF-8');
    }

    private static function adiTemizle(string $ad): string
    {
        return trim((string) preg_replace('/\s+/u', ' ', FinansServisi::metniTemizle($ad)));
    }

    /**
     * @param array<int, array<int, string>> $satirlar
     * @param callable(string): bool $eslesme
     * @return array{0:int, 1:int}|null  [satır no, sütun no]
     */
    private static function satirBul(array $satirlar, int $baslangic, callable $eslesme): ?array
    {
        for ($i = $baslangic, $adet = count($satirlar); $i < $adet; $i++) {
            foreach ($satirlar[$i] as $sutun => $hucre) {
                if ($eslesme($hucre)) {
                    return [$i, $sutun];
                }
            }
        }

        return null;
    }

    /**
     * Başlık satırının üstündeki en yakın yıl satırından [başlangıç sütunu => yıl] üretir.
     *
     * @param array<int, array<int, string>> $satirlar
     * @return array<int, int>
     */
    private static function yilBloklari(array $satirlar, int $baslikSatiri): array
    {
        for ($i = $baslikSatiri - 1; $i >= 0; $i--) {
            $bloklar = [];
            foreach ($satirlar[$i] as $sutun => $hucre) {
                $hucre = trim($hucre);
                if (preg_match('/^\d{4}$/', $hucre) === 1 && FinansDonem::gecerliMi((int) $hucre, 1)) {
                    $bloklar[$sutun] = (int) $hucre;
                }
            }
            if ($bloklar !== []) {
                return $bloklar;
            }
        }

        return [];
    }

    /**
     * Dolu ay başlıklarının beklenen konumda olduğunu doğrular (sütun kayması = yanlış aya yazma riski).
     *
     * @param array<int, string> $baslikSatiri
     * @param array<int, int>    $yilBloklari
     */
    private static function ayBasliklariniDogrula(array $baslikSatiri, array $yilBloklari): ?string
    {
        foreach (array_keys($yilBloklari) as $baslangic) {
            foreach (self::AY_BASLIKLARI as $ay => $beklenen) {
                $hucre = self::buyukHarf($baslikSatiri[$baslangic + $ay - 1] ?? '');
                if ($hucre !== '' && $hucre !== $beklenen) {
                    return 'Ay başlığı beklenen sütunda değil: "' . $hucre . '" yerine "' . $beklenen . '" olmalı. Sayfa yapısı değişmiş olabilir.';
                }
            }
        }

        return null;
    }

    /**
     * @param array<int, string> $satir
     * @param array<int, int>    $yilBloklari
     * @param array<string, mixed> $sonuc
     */
    private static function odemeleriOku(array $satir, string $ad, array $yilBloklari, int $satirNo, array &$sonuc): void
    {
        foreach ($yilBloklari as $baslangic => $yil) {
            foreach (array_keys(self::AY_BASLIKLARI) as $ay) {
                $hucre = $satir[$baslangic + $ay - 1] ?? '';
                if (FinansTutar::bosMu($hucre)) {
                    continue;
                }
                $kurus = FinansTutar::kurusaCevir($hucre);
                if ($kurus === null) {
                    $sonuc['uyarilar'][] = 'Satır ' . $satirNo . ' (' . $ad . ', ' . FinansDonem::AY_ADLARI[$ay] . ' ' . $yil
                        . '): "' . trim($hucre) . '" geçerli bir tutar değil, aktarılmayacak.';
                    continue;
                }
                $sonuc['odemeler'][] = ['ad' => $ad, 'yil' => $yil, 'ay' => $ay, 'kurus' => $kurus];
                $sonuc['hesaplanan_toplamlar'][$yil][$ay] = ($sonuc['hesaplanan_toplamlar'][$yil][$ay] ?? 0) + $kurus;
            }
        }
    }

    /**
     * @param array<int, string> $satir
     * @param array<int, int>    $yilBloklari
     * @param array<string, mixed> $sonuc
     */
    private static function sayfaToplamlariniOku(array $satir, array $yilBloklari, int $satirNo, array &$sonuc): void
    {
        foreach ($yilBloklari as $baslangic => $yil) {
            foreach (array_keys(self::AY_BASLIKLARI) as $ay) {
                $hucre = $satir[$baslangic + $ay - 1] ?? '';
                if (FinansTutar::bosMu($hucre)) {
                    continue;
                }
                $kurus = FinansTutar::kurusaCevir($hucre);
                if ($kurus === null) {
                    $sonuc['uyarilar'][] = 'Satır ' . $satirNo . ' (toplam, ' . FinansDonem::AY_ADLARI[$ay] . ' ' . $yil . '): "' . trim($hucre) . '" okunamadı.';
                    continue;
                }
                $sonuc['sayfa_toplamlari'][$yil][$ay] = $kurus;
            }
        }
    }

    /**
     * Sayfadaki "AYLIK TOPLAM" ile kişi satırlarının toplamı arasındaki farkları çıkarır.
     * Fark varsa kullanıcıya gösterilir; sessizce yutulmaz ve kendiliğinden kasaya yazılmaz.
     *
     * @param array<string, mixed> $sonuc
     */
    private static function farklariHesapla(array &$sonuc): void
    {
        $yillar = array_unique(array_merge(array_keys($sonuc['sayfa_toplamlari']), array_keys($sonuc['hesaplanan_toplamlar'])));
        sort($yillar);
        foreach ($yillar as $yil) {
            foreach (array_keys(self::AY_BASLIKLARI) as $ay) {
                $sayfa = $sonuc['sayfa_toplamlari'][$yil][$ay] ?? 0;
                $hesap = $sonuc['hesaplanan_toplamlar'][$yil][$ay] ?? 0;
                if ($sayfa !== $hesap) {
                    $sonuc['farklar'][] = ['yil' => $yil, 'ay' => $ay, 'sayfa' => $sayfa, 'hesaplanan' => $hesap, 'fark' => $sayfa - $hesap];
                }
            }
        }
    }
}
