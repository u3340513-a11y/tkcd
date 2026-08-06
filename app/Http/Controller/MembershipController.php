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
 * GET  /uye-ol  → Başvuru formunu gösterir.
 * POST /uye-ol  → reCAPTCHA doğrular, formu işler, dernek_uyeler tablosuna 'bekleyen'
 *                 olarak kaydeder ve PRG (Post/Redirect/Get) deseniyle yönlendirir.
 */
final class MembershipController
{
    private const RECAPTCHA_VERIFY_URL = 'https://www.google.com/recaptcha/api/siteverify';

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

        $durum       = trim((string) ($this->request->query['durum'] ?? ''));
        $siteKey     = Env::string('RECAPTCHA_SITE_KEY');

        return $this->responder->page('pages/membership', $seo, [
            'durum'            => in_array($durum, ['basarili', 'hata'], true) ? $durum : null,
            'recaptchaSiteKey' => $siteKey,
            'styles'           => ['membership.css'],
            'scripts'          => ['membership.js'],
            'headScripts'      => $siteKey !== ''
                ? ['https://www.google.com/recaptcha/api.js']
                : [],
        ]);
    }

    /**
     * Üyelik başvuru formu POST işleyicisi.
     *
     * İşlem sırası:
     *  1. Google reCAPTCHA v2 token'ı sunucu tarafında doğrula.
     *  2. Form verilerini MembershipService aracılığıyla doğrula ve kaydet.
     *  3. PRG desenine göre yönlendir.
     */
    public function store(): Response
    {
        try {
            // 1. reCAPTCHA sunucu tarafı doğrulaması
            $secretKey = Env::string('RECAPTCHA_SECRET_KEY');
            if ($secretKey !== '' && !$this->verifyCaptcha($secretKey)) {
                $this->logger->error('reCAPTCHA doğrulaması başarısız.');
                return Response::redirect('/uye-ol?durum=hata');
            }

            // 2. Form verisi doğrulama + kayıt
            $this->membershipService->apply($this->request->body);

            return Response::redirect('/uye-ol?durum=basarili');
        } catch (\InvalidArgumentException $e) {
            $this->logger->error('Üyelik başvurusu doğrulama hatası: ' . $e->getMessage());

            $debugSuffix = Env::bool('APP_DEBUG')
                ? '&hata_mesaji=' . urlencode($e->getMessage())
                : '';

            return Response::redirect('/uye-ol?durum=hata' . $debugSuffix);
        } catch (\Throwable $e) {
            $this->logger->exception($e);

            $debugSuffix = Env::bool('APP_DEBUG')
                ? '&hata_mesaji=' . urlencode($e->getMessage())
                : '';

            return Response::redirect('/uye-ol?durum=hata' . $debugSuffix);
        }
    }

    /**
     * Google reCAPTCHA v2 token'ını siteverify API'si ile doğrular.
     *
     * Neden: İstemci tarafı widget kolayca atlatılabilir; sunucu tarafı
     * doğrulama olmadan reCAPTCHA güvenlik sağlamaz.
     *
     * @param string $secretKey  Gizli anahtar (.env: RECAPTCHA_SECRET_KEY)
     * @return bool              true → bot değil, false → reddedilmeli
     */
    private function verifyCaptcha(string $secretKey): bool
    {
        $token = trim((string) ($this->request->body['g-recaptcha-response'] ?? ''));

        if ($token === '') {
            return false;
        }

        $payload = http_build_query([
            'secret'   => $secretKey,
            'response' => $token,
            'remoteip' => $this->request->clientIp,
        ]);

        $context = stream_context_create([
            'http' => [
                'method'  => 'POST',
                'header'  => 'Content-Type: application/x-www-form-urlencoded',
                'content' => $payload,
                'timeout' => 5,
            ],
        ]);

        $raw = @file_get_contents(self::RECAPTCHA_VERIFY_URL, false, $context);

        if ($raw === false) {
            // Ağ hatası: güvenli tarafta kal, isteği geç
            $this->logger->error('reCAPTCHA API erişim hatası; istek geçiriliyor.');
            return true;
        }

        /** @var array{success: bool}|null $result */
        $result = json_decode($raw, true);

        return isset($result['success']) && $result['success'] === true;
    }
}
