<?php

declare(strict_types=1);

/**
 * Finans verisi için veritabanı erişimi (Repository).
 *
 * İş kuralları ve yetki burada DEĞİL, FinansServisi'ndedir. Bu sınıf yalnızca
 * hazırlanmış sorgularla okur/yazar; hiçbir kayıt fiziksel olarak silinmez.
 */
final class FinansDeposu
{
    public const TUR_AIDAT = 'aidat';
    public const TUR_BAGIS = 'bagis';
    public const TUR_GIDER = 'gider';
    public const TUR_DEVIR = 'devir';

    /** Kasaya GELİR yazan türler (gider çıkarılır). */
    public const GELIR_TURLERI = [self::TUR_AIDAT, self::TUR_BAGIS, self::TUR_DEVIR];

    private const HAREKET_KOLONLARI = 'h.id, h.tur, h.tarih, h.donem_yil, h.donem_ay, h.tutar_kurus, '
        . 'h.aidat_uye_id, u.ad_soyad AS aidat_uye_adi, h.taraf, h.aciklama, h.kaynak, h.durum, '
        . 'h.iptal_nedeni, h.iptal_eden, h.iptal_tarihi, h.olusturan, h.olusturma_tarihi';

    public function __construct(private readonly PDO $db)
    {
    }

    public function baglanti(): PDO
    {
        return $this->db;
    }

    // ── AİDAT ÜYELERİ ───────────────────────────────────────────────────

    /** @return array<int, array{id:int, sira:int, ad_soyad:string}> */
    public function aidatUyeleri(): array
    {
        $sorgu = $this->db->query(
            'SELECT id, sira, ad_soyad FROM finans_aidat_uyeleri WHERE aktif = 1 ORDER BY sira ASC, ad_soyad ASC'
        );

        return array_map(
            static fn (array $s): array => ['id' => (int) $s['id'], 'sira' => (int) $s['sira'], 'ad_soyad' => (string) $s['ad_soyad']],
            $sorgu->fetchAll(PDO::FETCH_ASSOC)
        );
    }

    public function aidatUyesiBul(int $id): ?array
    {
        $sorgu = $this->db->prepare('SELECT id, ad_soyad FROM finans_aidat_uyeleri WHERE id = ? AND aktif = 1');
        $sorgu->execute([$id]);
        $satir = $sorgu->fetch(PDO::FETCH_ASSOC);

        return $satir === false ? null : $satir;
    }

    public function aidatUyesiAdlaBul(string $ad): ?int
    {
        $sorgu = $this->db->prepare('SELECT id FROM finans_aidat_uyeleri WHERE ad_soyad = ?');
        $sorgu->execute([$ad]);
        $id = $sorgu->fetchColumn();

        return $id === false ? null : (int) $id;
    }

    /** Aynı ad varsa PDOException (SQLSTATE 23000) fırlatır. */
    public function aidatUyesiEkle(string $ad, ?int $sira = null): int
    {
        $sira ??= $this->sonrakiAidatSirasi();
        $sorgu = $this->db->prepare('INSERT INTO finans_aidat_uyeleri (sira, ad_soyad) VALUES (?, ?)');
        $sorgu->execute([$sira, $ad]);

        return (int) $this->db->lastInsertId();
    }

    /** Kişiyi listeden çıkarır (silmez, pasife alır). Çıktı: çıkarıldıysa true. */
    public function aidatUyesiCikar(int $id): bool
    {
        $sorgu = $this->db->prepare('UPDATE finans_aidat_uyeleri SET aktif = 0 WHERE id = ? AND aktif = 1');
        $sorgu->execute([$id]);

        return $sorgu->rowCount() === 1;
    }

    /** Daha önce çıkarılmış aynı isimli kişiyi listenin sonuna geri alır. Çıktı: kişi id'si veya null. */
    public function aidatUyesiYenidenEtkinlestir(string $ad): ?int
    {
        $sorgu = $this->db->prepare('UPDATE finans_aidat_uyeleri SET aktif = 1, sira = ? WHERE ad_soyad = ? AND aktif = 0');
        $sorgu->execute([$this->sonrakiAidatSirasi(), $ad]);

        return $sorgu->rowCount() === 1 ? $this->aidatUyesiAdlaBul($ad) : null;
    }

    private function sonrakiAidatSirasi(): int
    {
        return (int) $this->db->query('SELECT COALESCE(MAX(sira), 0) + 1 FROM finans_aidat_uyeleri')->fetchColumn();
    }

    // ── HAREKETLER ──────────────────────────────────────────────────────

    /**
     * Yeni hareket ekler. Benzersiz anahtar çakışırsa PDOException (23000) fırlatır.
     *
     * @param array{tur:string, tarih:string, donem_yil:int, donem_ay:int, tutar_kurus:int,
     *              aidat_uye_id?:?int, taraf?:?string, aciklama?:?string, kaynak?:string,
     *              kaynak_anahtari?:?string, aidat_anahtari?:?string, olusturan:string} $k
     */
    public function hareketEkle(array $k): int
    {
        $sorgu = $this->db->prepare(
            'INSERT INTO finans_hareketler
                (tur, tarih, donem_yil, donem_ay, tutar_kurus, aidat_uye_id, taraf, aciklama,
                 kaynak, kaynak_anahtari, aidat_anahtari, olusturan)
             VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)'
        );
        $sorgu->execute([
            $k['tur'], $k['tarih'], $k['donem_yil'], $k['donem_ay'], $k['tutar_kurus'],
            $k['aidat_uye_id'] ?? null, $k['taraf'] ?? null, $k['aciklama'] ?? null,
            $k['kaynak'] ?? 'manuel', $k['kaynak_anahtari'] ?? null, $k['aidat_anahtari'] ?? null,
            $k['olusturan'],
        ]);

        return (int) $this->db->lastInsertId();
    }

    public function kaynakAnahtariVarMi(string $anahtar): bool
    {
        $sorgu = $this->db->prepare('SELECT 1 FROM finans_hareketler WHERE kaynak_anahtari = ? LIMIT 1');
        $sorgu->execute([$anahtar]);

        return $sorgu->fetchColumn() !== false;
    }

    public function aidatAnahtariVarMi(string $anahtar): bool
    {
        $sorgu = $this->db->prepare('SELECT 1 FROM finans_hareketler WHERE aidat_anahtari = ? LIMIT 1');
        $sorgu->execute([$anahtar]);

        return $sorgu->fetchColumn() !== false;
    }

    public function hareketBul(int $id): ?array
    {
        $sorgu = $this->db->prepare(
            'SELECT ' . self::HAREKET_KOLONLARI . '
               FROM finans_hareketler h
               LEFT JOIN finans_aidat_uyeleri u ON u.id = h.aidat_uye_id
              WHERE h.id = ?'
        );
        $sorgu->execute([$id]);
        $satir = $sorgu->fetch(PDO::FETCH_ASSOC);

        return $satir === false ? null : $satir;
    }

    /**
     * Aktif hareketi iptal eder (silmez). Aidat anahtarını boşaltır ki düzeltme girilebilsin.
     *
     * Çıktı: iptal edildiyse true; kayıt yok veya zaten iptalse false.
     */
    public function hareketIptalEt(int $id, string $neden, string $kullanici): bool
    {
        $sorgu = $this->db->prepare(
            "UPDATE finans_hareketler
                SET durum = 'iptal', aidat_anahtari = NULL, iptal_nedeni = ?, iptal_eden = ?, iptal_tarihi = NOW()
              WHERE id = ? AND durum = 'aktif'"
        );
        $sorgu->execute([$neden, $kullanici, $id]);

        return $sorgu->rowCount() === 1;
    }

    /**
     * Sayfalı hareket listesi.
     *
     * @return array<int, array<string, mixed>>
     */
    public function hareketler(string $tur, int $yil, bool $iptalDahil, int $limit, int $ofset): array
    {
        [$kosul, $parametreler] = $this->hareketKosulu($tur, $yil, $iptalDahil);
        $sorgu = $this->db->prepare(
            'SELECT ' . self::HAREKET_KOLONLARI . "
               FROM finans_hareketler h
               LEFT JOIN finans_aidat_uyeleri u ON u.id = h.aidat_uye_id
              WHERE {$kosul}
              ORDER BY h.tarih DESC, h.id DESC
              LIMIT " . max(1, $limit) . ' OFFSET ' . max(0, $ofset)
        );
        $sorgu->execute($parametreler);

        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    }

    public function hareketSayisi(string $tur, int $yil, bool $iptalDahil): int
    {
        [$kosul, $parametreler] = $this->hareketKosulu($tur, $yil, $iptalDahil);
        $sorgu = $this->db->prepare("SELECT COUNT(*) FROM finans_hareketler h WHERE {$kosul}");
        $sorgu->execute($parametreler);

        return (int) $sorgu->fetchColumn();
    }

    /**
     * @return array{0:string, 1:array<int, string|int>}
     */
    private function hareketKosulu(string $tur, int $yil, bool $iptalDahil): array
    {
        $kosullar = ['h.tur = ?'];
        $parametreler = [$tur];
        if ($yil > 0) {
            $kosullar[] = 'h.donem_yil = ?';
            $parametreler[] = $yil;
        }
        if (!$iptalDahil) {
            $kosullar[] = "h.durum = 'aktif'";
        }

        return [implode(' AND ', $kosullar), $parametreler];
    }

    // ── TOPLAMLAR / ÖZETLER ─────────────────────────────────────────────

    /**
     * Aktif kayıtların türlere göre toplamı (tüm zamanlar).
     *
     * @return array<string, array{kurus:int, adet:int}>
     */
    public function turToplamlari(): array
    {
        $sorgu = $this->db->query(
            "SELECT tur, SUM(tutar_kurus) AS kurus, COUNT(*) AS adet
               FROM finans_hareketler WHERE durum = 'aktif' AND tur <> 'devir' GROUP BY tur"
        );
        $sonuc = [];
        foreach ($sorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
            $sonuc[(string) $satir['tur']] = ['kurus' => (int) $satir['kurus'], 'adet' => (int) $satir['adet']];
        }

        return $sonuc;
    }

    /**
     * Bir yılın ay/tür kırılımı (aktif kayıtlar).
     *
     * @return array<int, array<string, array{kurus:int, adet:int}>>  [ay => [tur => [...]]]
     */
    public function aylikToplamlar(int $yil): array
    {
        $sorgu = $this->db->prepare(
            "SELECT donem_ay, tur, SUM(tutar_kurus) AS kurus, COUNT(*) AS adet
               FROM finans_hareketler
              WHERE durum = 'aktif' AND tur <> 'devir' AND donem_yil = ?
              GROUP BY donem_ay, tur"
        );
        $sorgu->execute([$yil]);
        $sonuc = [];
        foreach ($sorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
            $sonuc[(int) $satir['donem_ay']][(string) $satir['tur']] = [
                'kurus' => (int) $satir['kurus'],
                'adet'  => (int) $satir['adet'],
            ];
        }

        return $sonuc;
    }

    /** Yıl başına kadar olan (o yıl hariç) aktif kasa bakiyesi: kümülatif hesap için devreden tutar. */
    public function yilOncesiBakiye(int $yil): int
    {
        $sorgu = $this->db->prepare(
            "SELECT COALESCE(SUM(CASE WHEN tur = 'gider' THEN -tutar_kurus ELSE tutar_kurus END), 0)
               FROM finans_hareketler WHERE durum = 'aktif' AND tur <> 'devir' AND donem_yil < ?"
        );
        $sorgu->execute([$yil]);

        return (int) $sorgu->fetchColumn();
    }

    /**
     * Yılın aidat ödemeleri.
     *
     * @return array<int, array<int, int>>  [aidat_uye_id => [ay => toplam kuruş]]
     */
    public function aidatOdemeleri(int $yil): array
    {
        $sorgu = $this->db->prepare(
            "SELECT aidat_uye_id, donem_ay, SUM(tutar_kurus) AS kurus
               FROM finans_hareketler
              WHERE tur = 'aidat' AND durum = 'aktif' AND donem_yil = ?
              GROUP BY aidat_uye_id, donem_ay"
        );
        $sorgu->execute([$yil]);
        $sonuc = [];
        foreach ($sorgu->fetchAll(PDO::FETCH_ASSOC) as $satir) {
            $sonuc[(int) $satir['aidat_uye_id']][(int) $satir['donem_ay']] = (int) $satir['kurus'];
        }

        return $sonuc;
    }

    /**
     * Veride geçen yıllar (artan) + verilen yıl.
     *
     * @return int[]
     */
    public function yillar(int $ekYil): array
    {
        $yillar = array_map('intval', $this->db->query('SELECT DISTINCT donem_yil FROM finans_hareketler')->fetchAll(PDO::FETCH_COLUMN));
        $yillar[] = $ekYil;
        $yillar = array_values(array_unique($yillar));
        sort($yillar);

        return $yillar;
    }
}
