<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\HomePageService;
use App\Application\Service\PageResponder;
use App\Core\Config;
use App\Core\Http\Response;

/**
 * Anasayfa denetleyicisi.
 *
 * Sorumluluğu yalnızca HTTP katmanıdır: veriyi servisten ister, SEO üst
 * verisini kurar ve yanıtı döndürür.
 */
final class HomeController
{
    public function __construct(
        private readonly HomePageService $homePage,
        private readonly PageResponder $responder,
        private readonly Config $config,
    ) {
    }

    public function index(): Response
    {
        $seo = $this->responder->seo(
            title: $this->config->string('site.tagline') . ' | ' . $this->config->string('site.name'),
            description: $this->config->string('site.seo.default_description'),
            canonicalPath: '/',
        );

        return $this->responder->page('pages/home', $seo, [
            'model' => $this->homePage->build(),
            'styles' => ['home.css'],
        ]);
    }
}
