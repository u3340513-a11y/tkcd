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
 * POST /uye-ol  → Formu doğrular, dernek_uyeler tablosuna 'bekleyen' olarak kaydeder
 *                 ve PRG (Post/Redirect/Get) deseniyle yönlendirir.
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

        return $this->responder->page('pages/membership', $seo, [
            'durum'            => in_array($durum, ['basarili', 'hata'], true) ? $durum : null,
            'recaptchaSiteKey' => Env::string('RECAPTCHA_SITE_KEY'),
            'styles'           => ['membership.css'],
            'scripts'          => ['membership.js'],
        ]);
    }

    /**
     * Üyelik başvuru formu POST işleyicisi.
     *
     * PRG (Post/Redirect/Get) deseni uygulanır:
     *  - Başarıda  → /uye-ol?durum=basarili
     *  - Hata      → /uye-ol?durum=hata
     *
     * Bu desen, sayfa yenilendiğinde formun tekrar gönderilmesini önler.
     */
    public function store(): Response
    {
        try {
            $this->membershipService->apply($this->request->body);

            return Response::redirect('/uye-ol?durum=basarili');
        } catch (\InvalidArgumentException $e) {
            // Doğrulama hatası: güvenli, kullanıcıya gösterilebilir
            $this->logger->error('Üyelik başvurusu doğrulama hatası: ' . $e->getMessage());

            // Debug modunda hata nedenini URL'de gönder (prod'da kaldırılacak).
            $debugSuffix = Env::bool('APP_DEBUG')
                ? '&hata_mesaji=' . urlencode($e->getMessage())
                : '';

            return Response::redirect('/uye-ol?durum=hata' . $debugSuffix);
        } catch (\Throwable $e) {
            // Beklenmeyen hata (DB bağlantı sorunu vb.): logla, kullanıcıya sızdırma
            $this->logger->exception($e);

            $debugSuffix = Env::bool('APP_DEBUG')
                ? '&hata_mesaji=' . urlencode($e->getMessage())
                : '';

            return Response::redirect('/uye-ol?durum=hata' . $debugSuffix);
        }
    }
}
