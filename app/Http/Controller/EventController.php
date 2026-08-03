<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Application\Service\StructuredDataFactory;
use App\Core\Exception\HttpNotFoundException;
use App\Core\Http\Response;
use App\Core\Support\Text;
use App\Domain\Content\Repository\EventRepositoryInterface;

/**
 * Etkinlik/haber detay sayfası.
 */
final class EventController
{
    public function __construct(
        private readonly EventRepositoryInterface $events,
        private readonly PageResponder $responder,
        private readonly StructuredDataFactory $structuredData,
    ) {
    }

    /**
     * @throws HttpNotFoundException Slug bir içerikle eşleşmiyorsa.
     */
    public function show(string $slug): Response
    {
        $event = $this->events->findBySlug($slug);

        if ($event === null) {
            throw new HttpNotFoundException(sprintf('Etkinlik bulunamadı: %s', $slug));
        }

        $seo = $this->responder->seo(
            title: $event->title,
            description: Text::excerpt($event->summary, 158),
            canonicalPath: $event->url(),
            breadcrumbs: [
                ['label' => 'Duyurular', 'path' => '/duyurular'],
                ['label' => $event->title, 'path' => $event->url()],
            ],
            structuredData: [$this->structuredData->article($event)],
            image: $event->image,
            type: 'article',
        );

        return $this->responder->page('pages/event', $seo, ['event' => $event]);
    }
}
