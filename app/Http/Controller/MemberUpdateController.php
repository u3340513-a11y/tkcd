<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Env;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Log\LoggerInterface;
use PDO;

/**
 * Üye bilgi güncelleme sayfası.
 *
 * Mevcut üyelerin doğum tarihi ve ikamet ilçesi bilgilerini
 * cep telefonu doğrulaması ile güncellemelerini sağlar.
 *
 * GET  /bilgi-guncelleme          → Telefon doğrulama formunu gösterir
 * POST /bilgi-guncelleme/dogrula  → Telefonu DB'de arar, güncelleme formunu döndürür
 * POST /bilgi-guncelleme          → Güncellemeyi DB'ye yazar (PRG)
 */
final class MemberUpdateController
{
    public function __construct(
        private readonly PageResponder  $responder,
        private readonly Request        $request,
        private readonly LoggerInterface $logger,
    ) {
    }

    /**
     * Telefon doğrulama formunu gösterir.
     */
    public function index(): Response
    {
        $seo = $this->responder->seo(
            title: 'Bilgi Güncelleme',
            description: 'Mevcut üyelerimiz doğum tarihi ve ikamet bilgilerini güncelleyebilir.',
            canonicalPath: '/bilgi-guncelleme',
            indexable: false,
        );

        $durum = trim((string) ($this->request->query['durum'] ?? ''));

        [$captchaA, $captchaB, $captchaToken] = $this->generateMathCaptcha();

        return $this->responder->page('pages/member-update', $seo, [
            'adim'         => 'telefon',
            'durum'        => in_array($durum, ['basarili', 'bulunamadi', 'hata'], true) ? $durum : null,
            'captchaA'     => $captchaA,
            'captchaB'     => $captchaB,
            'captchaToken' => $captchaToken,
            'styles'       => ['member-update.css'],
            'scripts'      => ['turkey-districts.js', 'member-update.js'],
        ]);
    }

    /**
     * Telefonu DB'de arar. Bulursa güncelleme formunu gösterir.
     */
    public function verify(): Response
    {
        if (!$this->verifyMathCaptcha($this->request->body)) {
            return Response::redirect('/bilgi-guncelleme?durum=hata');
        }

        $telefonRaw = trim((string) ($this->request->body['telefon'] ?? ''));
        $telefon    = $this->normalizeTelefon($telefonRaw);

        if ($telefon === '') {
            return Response::redirect('/bilgi-guncelleme?durum=hata');
        }

        $pdo = $this->pdo();
        $stmt = $pdo->prepare(
            "SELECT id, adi_soyadi, telefon, dogum_tarihi, ikamet_ili, ikamet_ilcesi
               FROM dernek_uyeler
              WHERE REPLACE(REPLACE(telefon, ' ', ''), '-', '') = ?
                AND onay_durumu = 'onayli'
              LIMIT 1"
        );
        $stmt->execute([$telefon]);
        $uye = $stmt->fetch(PDO::FETCH_ASSOC);

        if (!$uye) {
            return Response::redirect('/bilgi-guncelleme?durum=bulunamadi');
        }

        $seo = $this->responder->seo(
            title: 'Bilgi Güncelleme',
            description: 'Bilgilerinizi güncelleyin.',
            canonicalPath: '/bilgi-guncelleme',
            indexable: false,
        );

        [$captchaA, $captchaB, $captchaToken] = $this->generateMathCaptcha();

        // Mevcut doğum tarihini görüntüleme formatına çevir
        $dogumGosterim = $this->formatDogumTarihi($uye['dogum_tarihi'] ?? '');

        return $this->responder->page('pages/member-update', $seo, [
            'adim'            => 'guncelleme',
            'durum'           => null,
            'uye'             => $uye,
            'dogumGosterim'   => $dogumGosterim,
            'captchaA'        => $captchaA,
            'captchaB'        => $captchaB,
            'captchaToken'    => $captchaToken,
            'styles'          => ['member-update.css'],
            'scripts'         => ['turkey-districts.js', 'member-update.js'],
        ]);
    }

    /**
     * Güncellemeyi DB'ye yazar ve PRG ile yönlendirir.
     */
    public function store(): Response
    {
        try {
            if (!$this->verifyMathCaptcha($this->request->body)) {
                return Response::redirect('/bilgi-guncelleme?durum=hata');
            }

            $uyeId       = (int) ($this->request->body['uye_id'] ?? 0);
            $telefonHash = trim((string) ($this->request->body['telefon_hash'] ?? ''));
            $dogumRaw    = trim((string) ($this->request->body['dogum_tarihi'] ?? ''));
            $ikametIli   = trim((string) ($this->request->body['ikamet_ili'] ?? ''));
            $ikametIlcesi = trim((string) ($this->request->body['ikamet_ilcesi'] ?? ''));

            if ($uyeId <= 0 || $telefonHash === '') {
                return Response::redirect('/bilgi-guncelleme?durum=hata');
            }

            // Telefon hash doğrulama — HMAC ile üye ID ve telefon eşleşmesi
            $pdo = $this->pdo();
            $stmt = $pdo->prepare(
                "SELECT id, telefon FROM dernek_uyeler WHERE id = ? AND onay_durumu = 'onayli' LIMIT 1"
            );
            $stmt->execute([$uyeId]);
            $uye = $stmt->fetch(PDO::FETCH_ASSOC);

            if (!$uye) {
                return Response::redirect('/bilgi-guncelleme?durum=hata');
            }

            // Hash doğrulama
            $expectedHash = hash_hmac('sha256', $uye['id'] . ':' . $uye['telefon'], $this->captchaSecret());
            if (!hash_equals($expectedHash, $telefonHash)) {
                return Response::redirect('/bilgi-guncelleme?durum=hata');
            }

            // Doğum tarihini DB formatına çevir (DD/MM/YYYY → DD/MM/YYYY olarak sakla)
            $dogumTarihi = '';
            if ($dogumRaw !== '') {
                if (preg_match('/^(\d{2})\/(\d{2})\/(\d{4})$/', $dogumRaw, $m)) {
                    $dogumTarihi = $dogumRaw; // DD/MM/YYYY olarak sakla
                }
            }

            // Güncelleme
            $updateFields = [];
            $updateValues = [];

            if ($dogumTarihi !== '') {
                $updateFields[] = 'dogum_tarihi = ?';
                $updateValues[] = $dogumTarihi;
            }

            if ($ikametIli !== '') {
                $updateFields[] = 'ikamet_ili = ?';
                $updateValues[] = $ikametIli;
            }

            if ($ikametIlcesi !== '') {
                $updateFields[] = 'ikamet_ilcesi = ?';
                $updateValues[] = $ikametIlcesi;
            }

            if ($updateFields === []) {
                return Response::redirect('/bilgi-guncelleme?durum=hata');
            }

            $updateValues[] = $uyeId;
            $sql = 'UPDATE dernek_uyeler SET ' . implode(', ', $updateFields) . ' WHERE id = ?';
            $stmt = $pdo->prepare($sql);
            $stmt->execute($updateValues);

            $this->logger->error(sprintf(
                'Üye bilgi güncelleme: #%d — dogum: %s, il: %s, ilce: %s',
                $uyeId,
                $dogumTarihi ?: '-',
                $ikametIli ?: '-',
                $ikametIlcesi ?: '-'
            ));

            return Response::redirect('/bilgi-guncelleme?durum=basarili');
        } catch (\Throwable $e) {
            $this->logger->exception($e);
            return Response::redirect('/bilgi-guncelleme?durum=hata');
        }
    }

    /**
     * Mevcut dogum_tarihi değerini GG/AA/YYYY formatına çevirir.
     */
    private function formatDogumTarihi(string $value): string
    {
        $value = trim($value);
        if ($value === '' || $value === '0000-00-00') {
            return '';
        }

        // Zaten DD/MM/YYYY formatında
        if (preg_match('/^\d{2}\/\d{2}\/\d{4}$/', $value)) {
            return $value;
        }

        // DD.MM.YYYY → DD/MM/YYYY
        if (preg_match('/^(\d{2})\.(\d{2})\.(\d{4})$/', $value, $m)) {
            return "{$m[1]}/{$m[2]}/{$m[3]}";
        }

        // YYYY-MM-DD → DD/MM/YYYY
        if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $value, $m)) {
            return "{$m[3]}/{$m[2]}/{$m[1]}";
        }

        // Sadece yıl → boş bırak, kullanıcı yeniden girsin
        return '';
    }

    /**
     * Telefon numarasını normalize eder: boşluk, tire temizler.
     */
    private function normalizeTelefon(string $input): string
    {
        $cleaned = preg_replace('/[\s\-\(\)]/', '', $input) ?? '';
        if (preg_match('/^0?5\d{9}$/', $cleaned)) {
            // Başında 0 yoksa ekle
            if (strlen($cleaned) === 10) {
                $cleaned = '0' . $cleaned;
            }
            return $cleaned;
        }
        return '';
    }

    /**
     * PDO bağlantısı oluşturur.
     */
    private function pdo(): PDO
    {
        $host    = Env::string('DB_HOST', '127.0.0.1');
        $port    = Env::string('DB_PORT', '3306');
        $dbname  = Env::string('DB_DATABASE', '');
        $user    = Env::string('DB_USERNAME', '');
        $pass    = Env::string('DB_PASSWORD', '');
        $charset = Env::string('DB_CHARSET', 'utf8mb4');

        $dsn = "mysql:host={$host};port={$port};dbname={$dbname};charset={$charset}";

        return new PDO($dsn, $user, $pass, [
            PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
            PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
            PDO::ATTR_EMULATE_PREPARES   => false,
        ]);
    }

    /**
     * @return array{int, int, string}
     */
    private function generateMathCaptcha(): array
    {
        $a        = random_int(1, 12);
        $b        = random_int(1, 12);
        $secret   = $this->captchaSecret();
        $timeSlot = (int) floor(time() / 3600);
        $token    = hash_hmac('sha256', "{$a}:{$b}:{$timeSlot}", $secret);

        return [$a, $b, $token];
    }

    /**
     * @param array<string, mixed> $post
     */
    private function verifyMathCaptcha(array $post): bool
    {
        $a              = (int) ($post['captcha_a']     ?? 0);
        $b              = (int) ($post['captcha_b']     ?? 0);
        $submittedToken = trim((string) ($post['captcha_token']  ?? ''));
        $userAnswerRaw  = trim((string) ($post['captcha_answer'] ?? ''));

        if ($userAnswerRaw === '' || !ctype_digit($userAnswerRaw)) {
            return false;
        }

        if ((int) $userAnswerRaw !== ($a + $b)) {
            return false;
        }

        $secret   = $this->captchaSecret();
        $timeSlot = (int) floor(time() / 3600);

        foreach ([$timeSlot, $timeSlot - 1] as $slot) {
            $expected = hash_hmac('sha256', "{$a}:{$b}:{$slot}", $secret);
            if (hash_equals($expected, $submittedToken)) {
                return true;
            }
        }

        return false;
    }

    private function captchaSecret(): string
    {
        $key = Env::string('RECAPTCHA_SECRET_KEY');
        if ($key === '') {
            $key = Env::string('DB_PASSWORD');
        }
        return $key !== '' ? $key : 'tkcd-fallback-secret-2024';
    }
}
