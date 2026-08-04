<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Http\Response;

/**
 * Üye Ol sayfası.
 *
 * GET  /uye-ol → Başvuru formunu gösterir.
 * POST /uye-ol → (İkinci faz) Form gönderimini işler.
 *
 * Şu aşamada POST henüz işlenmemektedir; form HTML olarak sunulmaktadır.
 * İkinci fazda e-posta gönderimi ve veritabanı kaydı eklenecektir.
 */
final class MembershipController
{
    public function __construct(private readonly PageResponder $responder)
    {
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

        return $this->responder->page('pages/membership', $seo, [
            'durum'           => null,
            'recaptchaSiteKey' => $_ENV['RECAPTCHA_SITE_KEY'] ?? '',
            'styles'          => ['membership.css'],
        ]);
    }
}
