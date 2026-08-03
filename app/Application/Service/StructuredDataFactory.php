<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Config;
use App\Domain\Content\Entity\Event;

/**
 * Schema.org JSON-LD blokları üretir.
 *
 * Neden: Arama motorlarının derneği bir kuruluş olarak tanıması, iletişim
 * bilgilerini ve etkinlikleri zengin sonuç olarak göstermesi için gereklidir.
 *
 * Girdi : site yapılandırması ve varlıklar
 * Çıktı : head bölümüne basılacak dizi yapısında JSON-LD
 */
final class StructuredDataFactory
{
    public function __construct(private readonly Config $config)
    {
    }

    /**
     * @return array<string, mixed>
     */
    public function organization(): array
    {
        $site = $this->config->array('site');
        /** @var array<string, string> $contact */
        $contact = (array) ($site['contact'] ?? []);
        $baseUrl = $this->config->string('app.url');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'NGO',
            'name' => (string) ($site['name'] ?? ''),
            'alternateName' => (string) ($site['short_name'] ?? ''),
            'url' => $baseUrl,
            'logo' => $baseUrl . '/assets/img/logo.png',
            'description' => (string) ($site['description'] ?? ''),
            'foundingDate' => (string) ($site['founded_year'] ?? ''),
            'email' => $contact['email'] ?? '',
            'telephone' => $contact['phone'] ?? '',
            'address' => [
                '@type' => 'PostalAddress',
                'streetAddress' => $contact['address'] ?? '',
                'addressLocality' => $contact['address_locality'] ?? '',
                'postalCode' => $contact['postal_code'] ?? '',
                'addressCountry' => $contact['country'] ?? 'TR',
            ],
            'areaServed' => 'TR',
            'sameAs' => $this->socialUrls(),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function website(): array
    {
        $baseUrl = $this->config->string('app.url');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'WebSite',
            'name' => $this->config->string('site.name'),
            'url' => $baseUrl,
            'inLanguage' => 'tr-TR',
        ];
    }

    /**
     * @param list<array{label: string, path: string}> $items
     * @return array<string, mixed>
     */
    public function breadcrumbs(array $items): array
    {
        $baseUrl = $this->config->string('app.url');
        $position = 0;

        return [
            '@context' => 'https://schema.org',
            '@type' => 'BreadcrumbList',
            'itemListElement' => array_map(
                static function (array $item) use (&$position, $baseUrl): array {
                    ++$position;

                    return [
                        '@type' => 'ListItem',
                        'position' => $position,
                        'name' => $item['label'],
                        'item' => $baseUrl . $item['path'],
                    ];
                },
                $items,
            ),
        ];
    }

    /**
     * @return array<string, mixed>
     */
    public function article(Event $event): array
    {
        $baseUrl = $this->config->string('app.url');

        return [
            '@context' => 'https://schema.org',
            '@type' => 'Article',
            'headline' => $event->title,
            'description' => $event->summary,
            'datePublished' => $event->publishedAt,
            'inLanguage' => 'tr-TR',
            'mainEntityOfPage' => $baseUrl . $event->url(),
            'image' => $event->image === null ? [] : [$baseUrl . $event->image],
            'publisher' => [
                '@type' => 'Organization',
                'name' => $this->config->string('site.name'),
                'logo' => [
                    '@type' => 'ImageObject',
                    'url' => $baseUrl . '/assets/img/logo.png',
                ],
            ],
        ];
    }

    /**
     * @return list<string>
     */
    private function socialUrls(): array
    {
        return array_values(array_filter(array_map(
            static fn (array $social): string => (string) ($social['url'] ?? ''),
            $this->config->array('social'),
        )));
    }
}
