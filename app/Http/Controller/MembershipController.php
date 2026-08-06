<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\MembershipService;
use App\Application\Service\PageResponder;
use App\Core\Env;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Core\Log\LoggerInterface;

/**
 * Üye Ol sayfası.
 *
 * GET  /uye-ol  → Başvuru formunu gösterir; matematik doğrulama sorusu üretir.
 * POST /uye-ol  → Matematik doğrulamayı kontrol eder, formu işler,
 *                 dernek_uyeler tablosuna 'bekleyen' kaydeder (PRG deseni).
 */
final class MembershipController
{
    public function __construct(
        private readonly PageResponder     $responder,
        private readonly MembershipService $membershipService,
        private readonly Request           $request,
        private readonly LoggerInterface   $logger,
    ) {
    }

    public function index(): Response
    {
        $title       = 'Üye Ol';
        $description = 'Trabzonlu kamu çalışanları olarak birlik ve dayanışma çatımıza katılın; '
            . 'üyelik başvuru formunu doldurun.';

        $seo = $this->responder->seo(
            title: $title,
            description: $description,
            canonicalPath: '/uye-ol',
            breadcrumbs: [['label' => $title, 'path' => '/uye-ol']],
        );

        $durum = trim((string) ($this->request->query['durum'] ?? ''));

        // Matematik doğrulama sorusu: her sayfa yüklenişinde farklı
        [$captchaA, $captchaB, $captchaToken] = $this->generateMathCaptcha();

        return $this->responder->page('pages/membership', $seo, [
            'durum'        => in_array($durum, ['basarili', 'hata'], true) ? $durum : null,
            'captchaA'     => $captchaA,
            'captchaB'     => $captchaB,
            'captchaToken' => $captchaToken,
            'styles'       => ['membership.css'],
            'scripts'      => ['membership.js'],
        ]);
    }

    /**
     * Üyelik başvuru formu POST işleyicisi.
     *
     * İşlem sırası:
     *  1. Matematik doğrulaması (HMAC imzalı, replay-safe).
     *  2. Form verilerini MembershipService aracılığıyla doğrula ve kaydet.
     *  3. PRG desenine göre yönlendir.
     */
    public function store(): Response
    {
        try {
            // 1. Matematik doğrulaması
            if (!$this->verifyMathCaptcha($this->request->body)) {
                $this->logger->error('Matematik doğrulaması başarısız.');
                return Response::redirect('/uye-ol?durum=hata&hata_mesaji=' . urlencode('Matematik dogrulamasi basarisiz'));
            }

            // 2. Form verisi doğrulama + kayıt
            $this->membershipService->apply($this->request->body);

            return Response::redirect('/uye-ol?durum=basarili');
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Üyelik başvurusu doğrulama hatası: ' . $e->getMessage());

            return Response::redirect('/uye-ol?durum=hata&hata_mesaji=' . urlencode($e->getMessage()));
        } catch (\Throwable $e) {
            $this->logger->exception($e);

            return Response::redirect('/uye-ol?durum=hata&hata_mesaji=' . urlencode($e->getMessage()));
        }
    }

    /**
     * Matematik doğrulama sorusu üretir.
     *
     * Token, a + b değerini ve zaman dilimini HMAC ile imzalar.
     * Böylece sayfa kapatılıp tekrar açılsa bile eski token'lar
     * bir saatlik süre içinde geçerli kalır (saat sınırı geçişlerini kapsar).
     *
     * @return array{int, int, string} [$a, $b, $token]
     */
    private function generateMathCaptcha(): array
    {
        $a     = random_int(1, 12);
        $b     = random_int(1, 12);
        $secret    = $this->captchaSecret();
        $timeSlot  = (int) floor(time() / 3600);
        $token = hash_hmac('sha256', "{$a}:{$b}:{$timeSlot}", $secret);

        return [$a, $b, $token];
    }

    /**
     * Kullanıcının matematik doğrulamasını HMAC ile kontrol eder.
     *
     * Replay koruması: token, a ve b değerleri ile saatlik zaman dilimini içerir.
     * Önceki saat dilimi de kabul edilir (saat sınırı geçişi için tolerans).
     *
     * @param array<string, mixed> $post
     */
    private function verifyMathCaptcha(array $post): bool
    {
        $a               = (int) ($post['captcha_a']     ?? 0);
        $b               = (int) ($post['captcha_b']     ?? 0);
        $submittedToken  = trim((string) ($post['captcha_token']  ?? ''));
        $userAnswerRaw   = trim((string) ($post['captcha_answer'] ?? ''));

        if ($userAnswerRaw === '' || !ctype_digit($userAnswerRaw)) {
            return false;
        }

        if ((int) $userAnswerRaw !== ($a + $b)) {
            return false;
        }

        $secret   = $this->captchaSecret();
        $timeSlot = (int) floor(time() / 3600);

        // Geçerli saat ve önceki saat kabul edilir
        foreach ([$timeSlot, $timeSlot - 1] as $slot) {
            $expected = hash_hmac('sha256', "{$a}:{$b}:{$slot}", $secret);
            if (hash_equals($expected, $submittedToken)) {
                return true;
            }
        }

        return false;
    }

    /**
     * HMAC imzası için sunucu tarafı gizli anahtar.
     * .env'den RECAPTCHA_SECRET_KEY veya DB_PASSWORD'ü yedek kullanır.
     */
    private function captchaSecret(): string
    {
        $key = Env::string('RECAPTCHA_SECRET_KEY');

        if ($key === '') {
            $key = Env::string('DB_PASSWORD');
        }

        return $key !== '' ? $key : 'tkcd-fallback-secret-2024';
    }
}
