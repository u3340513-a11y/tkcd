<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Http\Response;

final class MembershipController
{
    public function __construct(private readonly PageResponder $responder)
    {
    }

    public function index(): Response
    {
        $title = 'Üye Ol';
        $description = 'Trabzonlu kamu çalışanları olarak birlik ve dayanışma çatımıza katılın; '
            . 'üyelik başvuru koşulları ve süreci hakkında bilgi alın.';

        $seo = $this->responder->seo(
            title: $title,
            description: $description,
            canonicalPath: '/uye-ol',
            breadcrumbs: [['label' => $title, 'path' => '/uye-ol']],
        );

        return $this->responder->page('pages/section', $seo, [
            'pageTitle' => $title,
            'pageDescription' => $description,
            'parentLabel' => 'Üyelik',
        ]);
    }
}
