<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Config;
use App\Core\Http\Response;
use App\Core\View\SeoMeta;
use App\Core\View\ViewRendererInterface;

/**
 * Sayfa yanıtı üretmenin ortak adımlarını üstlenir.
 *
 * Neden: Her denetleyicide tekrarlanacak olan "SEO üst verisini kur, yapısal
 * veriyi ekle, şablonu işle, yanıtı sarmala" akışı tek yerde toplanır.
 * Denetleyiciler bu servisi kalıtım yerine bileşim (composition) ile kullanır.
 */
final class PageResponder
{
    public function __construct(
        private readonly ViewRendererInterface $view,
        private readonly Config $config,
        private readonly StructuredDataFactory $structuredData,
    ) {
    }

    /**
     * @param list<array{label: string, path: string}> $breadcrumbs
     * @param list<array<string, mixed>> $structuredData Sayfaya özel ek JSON-LD blokları
     */
    public function seo(
        string $title,
        string $description,
        string $canonicalPath,
        array $breadcrumbs = [],
        array $structuredData = [],
        ?string $image = null,
        bool $indexable = true,
        string $type = 'website',
    ): SeoMeta {
        $blocks = [
            $this->structuredData->organization(),
            $this->structuredData->website(),
            ...$structuredData,
        ];

        if ($breadcrumbs !== []) {
            $blocks[] = $this->structuredData->breadcrumbs(
                [['label' => 'Ana Sayfa', 'path' => '/'], ...$breadcrumbs],
            );
        }

        return new SeoMeta(
            title: $this->composeTitle($title, $canonicalPath),
            description: $description,
            canonicalPath: $canonicalPath,
            image: $image ?? $this->config->string('site.seo.default_image'),
            indexable: $indexable,
            type: $type,
            structuredData: $blocks,
            breadcrumbs: $breadcrumbs,
        );
    }

    /**
     * @param array<string, mixed> $data
     */
    public function page(string $template, SeoMeta $seo, array $data = [], int $status = 200): Response
    {
        return Response::html($this->view->renderPage($template, $seo, $data), $status);
    }

    /**
     * Ana sayfada marka adı başlıkta tekrarlanmaz; alt sayfalarda kurumsal
     * son ek eklenerek arama sonuçlarında tutarlı bir görünüm sağlanır.
     */
    private function composeTitle(string $title, string $canonicalPath): string
    {
        if ($canonicalPath === '/') {
            return $title;
        }

        return $title . $this->config->string('site.seo.title_suffix');
    }
}
