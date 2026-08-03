<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\NavigationProvider;
use App\Core\Config;
use App\Core\Http\Response;
use App\Core\Support\Html;
use App\Domain\Content\Repository\EventRepositoryInterface;

/**
 * Dinamik sitemap.xml üretimi.
 *
 * Neden: Menü veya içerik değiştiğinde sitemap'in elle güncellenmesi gerekmez;
 * arama motorları her zaman güncel bir liste okur.
 */
final class SitemapController
{
    private const EVENT_LIMIT = 100;

    public function __construct(
        private readonly NavigationProvider $navigation,
        private readonly EventRepositoryInterface $events,
        private readonly Config $config,
    ) {
    }

    public function index(): Response
    {
        $baseUrl = $this->config->string('app.url');
        $today = date('Y-m-d');

        $urls = array_map(
            static fn (array $item): string => self::urlNode(
                $baseUrl . ($item['path'] === '/' ? '' : $item['path']),
                $today,
                $item['priority'],
            ),
            $this->navigation->flattenPaths(),
        );

        $urls[] = self::urlNode($baseUrl . '/uye-ol', $today, '0.9');

        foreach ($this->events->findLatest(self::EVENT_LIMIT) as $event) {
            $urls[] = self::urlNode($baseUrl . $event->url(), $event->publishedAt, '0.6');
        }

        $xml = '<?xml version="1.0" encoding="UTF-8"?>' . PHP_EOL
            . '<urlset xmlns="http://www.sitemaps.org/schemas/sitemap/0.9">' . PHP_EOL
            . implode('', $urls)
            . '</urlset>';

        return Response::xml($xml);
    }

    private static function urlNode(string $location, string $lastModified, string $priority): string
    {
        return sprintf(
            "  <url><loc>%s</loc><lastmod>%s</lastmod><priority>%s</priority></url>%s",
            Html::escape($location),
            Html::escape($lastModified),
            Html::escape($priority),
            PHP_EOL,
        );
    }
}
