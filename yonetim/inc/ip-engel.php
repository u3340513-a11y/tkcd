<?php

declare(strict_types=1);

/**
 * Giriş denemelerine dayalı IP engelleme.
 *
 * Kurallar:
 *   - Sistemde olmayan ve kayıtlı hiçbir kullanıcı adına benzemeyen bir
 *     kullanıcı adıyla giriş denenirse IP anında engellenir.
 *   - Kayıtlı bir kullanıcı adına yakın (yazım hatası olabilecek) bir ad
 *     denenirse ilk seferde sadece sayılır; aynı IP'den ikinci kez olursa
 *     (YAKIN_PENCERE_SANIYE içinde) engellenir.
 *   - Engeller kalıcıdır; yalnızca geliştirici rolü kaldırabilir.
 *   - MUAF IP'ler (.env → LOGIN_BAN_MUAF_IPLER) hiçbir zaman engellenmez.
 */

if (!function_exists('istemci_ip_al')) {
    /**
     * Neden var: X-Forwarded-For istemci tarafından sahtelenebilir; bu yüzden
     * varsayılan olarak REMOTE_ADDR kullanılır. Sunucu güvenilir bir ters
     * vekil (ör. Cloudflare) arkasındaysa .env → GUVENILIR_IP_BASLIGI ile
     * (örn. HTTP_CF_CONNECTING_IP) o başlık okunur.
     *
     * Çıktı: geçerli IP veya belirlenemezse boş string.
     */
    function istemci_ip_al(): string
    {
        $baslikAdi = env_al('GUVENILIR_IP_BASLIGI', '');
        $aday      = '';

        if ($baslikAdi !== '' && preg_match('/^HTTP_[A-Z0-9_]+$/', $baslikAdi) === 1) {
            $aday = trim(explode(',', (string) ($_SERVER[$baslikAdi] ?? ''))[0]);
        }
        if ($aday === '') {
            $aday = (string) ($_SERVER['REMOTE_ADDR'] ?? '');
        }

        return filter_var($aday, FILTER_VALIDATE_IP) !== false ? $aday : '';
    }
}

if (!function_exists('ip_engel_sayfasi_goster')) {
    /**
     * Engellenmiş istemciye gösterilen yanıtı gönderir ve çalışmayı bitirir.
     *
     * Neden POST'ta yönlendirme: Engel sayfası bir form gönderiminin yanıtı
     * olursa tarayıcı yenilemesi aynı kullanıcı adı/şifreyi yeniden gönderir
     * ve engel kaldırıldığında IP anında tekrar engellenir (POST/Redirect/GET).
     */
    function ip_engel_sayfasi_goster(): void
    {
        if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
            header('Location: /yonetim/', true, 303);
            exit;
        }

        http_response_code(403);
        header('Content-Type: text/html; charset=UTF-8');
        echo '<!DOCTYPE html><html lang="tr"><head><meta charset="UTF-8">'
            . '<meta name="robots" content="noindex"><title>Erişim Engellendi</title></head>'
            . '<body style="font-family:sans-serif;text-align:center;padding:4rem 1rem;">'
            . '<h1>Erişim Engellendi</h1>'
            . '<p>Bu IP adresinden erişim güvenlik nedeniyle engellenmiştir. '
            . 'Sistem yöneticisiyle iletişime geçin.</p></body></html>';
        exit;
    }
}

final class IpEngelServisi
{
    public const SONUC_UYARI      = 'uyari';
    public const SONUC_ENGELLENDI = 'engellendi';

    public const SEBEP_BILINMEYEN_KULLANICI = 'bilinmeyen_kullanici';
    public const SEBEP_YAKIN_KULLANICI      = 'yakin_kullanici_tekrari';

    /** Yakın kullanıcı adı denemesinde engellemeye yol açan deneme sayısı. */
    private const YAKIN_DENEME_LIMITI = 2;
    /** Yakın denemelerin birbirine bağlı sayılacağı süre (24 saat). */
    private const YAKIN_PENCERE_SANIYE = 86400;
    /** Bu uzunluk ve altındaki kayıtlı adlar için izin verilen yazım farkı 1'dir. */
    private const KISA_AD_UZUNLUGU = 5;
    private const KISA_AD_MAX_FARK = 1;
    private const UZUN_AD_MAX_FARK = 2;

    private const MAX_KULLANICI_UZUNLUGU = 100;
    private const MAX_SIFRE_UZUNLUGU     = 255;
    private const MAX_USER_AGENT         = 500;

    /** @var string[] */
    private array $muafIpler;

    public function __construct(private PDO $db, string $muafIpListesi = '')
    {
        $this->muafIpler = array_values(array_filter(
            array_map('trim', explode(',', $muafIpListesi)),
            static fn(string $ip): bool => $ip !== ''
        ));
    }

    public function engelliMi(string $ip): bool
    {
        if ($ip === '' || $this->muafMi($ip)) {
            return false;
        }

        try {
            $sorgu = $this->db->prepare('SELECT 1 FROM engelli_ipler WHERE ip_adresi = ? LIMIT 1');
            $sorgu->execute([$ip]);
            return $sorgu->fetchColumn() !== false;
        } catch (PDOException $e) {
            error_log('[IP_ENGEL] Engel sorgusu başarısız: ' . $e->getMessage());
            return false;
        }
    }

    /**
     * Kayıtlı olmayan bir kullanıcı adıyla yapılan giriş denemesini değerlendirir.
     *
     * Girdi : denenen kullanıcı adı ve şifre, istemci IP'si, tarayıcı bilgisi
     * Çıktı : SONUC_ENGELLENDI (IP engellendi) | SONUC_UYARI (yakın ad, sayıldı) | null (işlem yapılmadı)
     */
    public function bilinmeyenKullaniciDenemesi(string $kullanici, string $sifre, string $ip, ?string $userAgent): ?string
    {
        if ($ip === '' || $this->muafMi($ip)) {
            return null;
        }

        $kullanici = mb_substr($kullanici, 0, self::MAX_KULLANICI_UZUNLUGU);

        try {
            if (!$this->kayitliAdaYakinMi($kullanici)) {
                $this->engelle($ip, $kullanici, $sifre, self::SEBEP_BILINMEYEN_KULLANICI, $userAgent);
                return self::SONUC_ENGELLENDI;
            }

            if ($this->yakinDenemeyiSay($ip, $kullanici, $sifre) >= self::YAKIN_DENEME_LIMITI) {
                $this->engelle($ip, $kullanici, $sifre, self::SEBEP_YAKIN_KULLANICI, $userAgent);
                return self::SONUC_ENGELLENDI;
            }

            return self::SONUC_UYARI;
        } catch (PDOException $e) {
            error_log('[IP_ENGEL] Deneme değerlendirilemedi: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    public function listele(int $limit, int $offset): array
    {
        $sorgu = $this->db->prepare(
            'SELECT id, ip_adresi, denenen_kullanici, denenen_sifre, sebep, user_agent, engellendi_tarih
               FROM engelli_ipler
              ORDER BY engellendi_tarih DESC
              LIMIT :limit OFFSET :offset'
        );
        $sorgu->bindValue(':limit', $limit, PDO::PARAM_INT);
        $sorgu->bindValue(':offset', $offset, PDO::PARAM_INT);
        $sorgu->execute();

        return $sorgu->fetchAll(PDO::FETCH_ASSOC);
    }

    public function toplamEngelSayisi(): int
    {
        return (int) $this->db->query('SELECT COUNT(*) FROM engelli_ipler')->fetchColumn();
    }

    /**
     * Engeli kaldırır ve yakın deneme sayacını sıfırlar.
     *
     * Çıktı: kaldırılan IP adresi; kayıt yoksa null.
     */
    public function engeliKaldir(int $id): ?string
    {
        $bul = $this->db->prepare('SELECT ip_adresi FROM engelli_ipler WHERE id = ?');
        $bul->execute([$id]);
        $ip = $bul->fetchColumn();
        if ($ip === false) {
            return null;
        }

        $this->db->beginTransaction();
        try {
            $this->db->prepare('DELETE FROM engelli_ipler WHERE id = ?')->execute([$id]);
            $this->db->prepare('DELETE FROM giris_yakin_denemeleri WHERE ip_adresi = ?')->execute([$ip]);
            $this->db->commit();
        } catch (PDOException $e) {
            $this->db->rollBack();
            throw $e;
        }

        return (string) $ip;
    }

    /**
     * İki kullanıcı adının "yazım hatası kadar yakın" olup olmadığını döner.
     * Büyük/küçük harf farkı yok sayılır; Türkçe karakterler tek karakter sayılır.
     */
    public static function adlarYakinMi(string $denenen, string $kayitli): bool
    {
        $a = mb_strtolower($denenen);
        $b = mb_strtolower($kayitli);

        $izinliFark = mb_strlen($b) <= self::KISA_AD_UZUNLUGU
            ? self::KISA_AD_MAX_FARK
            : self::UZUN_AD_MAX_FARK;

        if (abs(mb_strlen($a) - mb_strlen($b)) > $izinliFark) {
            return false;
        }

        return self::duzenlemeMesafesi($a, $b) <= $izinliFark;
    }

    /** Karakterleri tek baytlık sembollere çevirip levenshtein uygular (çok baytlı güvenli). */
    private static function duzenlemeMesafesi(string $a, string $b): int
    {
        $harita = [];
        $kodla  = static function (string $metin) use (&$harita): string {
            $kodlu = '';
            foreach (mb_str_split($metin) as $karakter) {
                $harita[$karakter] ??= chr(count($harita));
                $kodlu .= $harita[$karakter];
            }
            return $kodlu;
        };

        return levenshtein($kodla($a), $kodla($b));
    }

    private function kayitliAdaYakinMi(string $kullanici): bool
    {
        $adlar = $this->db->query('SELECT kullanici_adi FROM dernek_yoneticiler')->fetchAll(PDO::FETCH_COLUMN);
        foreach ($adlar as $kayitli) {
            if (self::adlarYakinMi($kullanici, (string) $kayitli)) {
                return true;
            }
        }
        return false;
    }

    /** Yakın denemeyi sayar; pencere dolmuşsa sayaç 1'den başlar. Güncel sayıyı döner. */
    private function yakinDenemeyiSay(string $ip, string $kullanici, string $sifre): int
    {
        $bul = $this->db->prepare(
            'SELECT deneme_sayisi, son_deneme FROM giris_yakin_denemeleri WHERE ip_adresi = ?'
        );
        $bul->execute([$ip]);
        $kayit = $bul->fetch(PDO::FETCH_ASSOC);

        $sayi = 1;
        if ($kayit !== false && (time() - strtotime((string) $kayit['son_deneme'])) <= self::YAKIN_PENCERE_SANIYE) {
            $sayi = (int) $kayit['deneme_sayisi'] + 1;
        }

        $this->db->prepare(
            'INSERT INTO giris_yakin_denemeleri (ip_adresi, deneme_sayisi, son_kullanici, son_sifre, son_deneme)
             VALUES (?, ?, ?, ?, NOW())
             ON DUPLICATE KEY UPDATE deneme_sayisi = VALUES(deneme_sayisi),
                                     son_kullanici = VALUES(son_kullanici),
                                     son_sifre     = VALUES(son_sifre),
                                     son_deneme    = VALUES(son_deneme)'
        )->execute([$ip, $sayi, $kullanici, mb_substr($sifre, 0, self::MAX_SIFRE_UZUNLUGU)]);

        return $sayi;
    }

    private function engelle(string $ip, string $kullanici, string $sifre, string $sebep, ?string $userAgent): void
    {
        $this->db->prepare(
            'INSERT INTO engelli_ipler (ip_adresi, denenen_kullanici, denenen_sifre, sebep, user_agent)
             VALUES (?, ?, ?, ?, ?)
             ON DUPLICATE KEY UPDATE denenen_kullanici = VALUES(denenen_kullanici),
                                     denenen_sifre     = VALUES(denenen_sifre),
                                     sebep             = VALUES(sebep),
                                     user_agent        = VALUES(user_agent),
                                     engellendi_tarih  = NOW()'
        )->execute([
            $ip,
            $kullanici,
            mb_substr($sifre, 0, self::MAX_SIFRE_UZUNLUGU),
            $sebep,
            $userAgent !== null ? mb_substr($userAgent, 0, self::MAX_USER_AGENT) : null,
        ]);

        $this->db->prepare('DELETE FROM giris_yakin_denemeleri WHERE ip_adresi = ?')->execute([$ip]);
    }

    private function muafMi(string $ip): bool
    {
        return in_array($ip, $this->muafIpler, true);
    }
}
