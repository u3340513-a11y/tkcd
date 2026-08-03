<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Http\Response;

/**
 * Duyurular sayfası denetleyicisi.
 *
 * Veriyi dosya tabanlı kaynaktan okur, tarihe göre sıralar ve
 * şablona hazır hâlde iletir. Admin panel ile DB entegrasyonu
 * sağlandığında yalnızca bu sınıf güncellenir.
 */
final class AnnouncementController
{
    public function __construct(private readonly PageResponder $responder)
    {
    }

    public function index(): Response
    {
        $seo = $this->responder->seo(
            title: 'Duyurular',
            description: 'Derneğimizin güncel duyuruları, etkinlik duyurularımız ve '
                . 'yıllık raporlarımıza bu sayfadan ulaşabilirsiniz.',
            canonicalPath: '/duyurular',
            breadcrumbs: [['label' => 'Duyurular', 'path' => '/duyurular']],
        );

        /** @var list<array{slug:string,title:string,summary:string,category:string,published_at:string,highlighted:bool}> $raw */
        $raw = require dirname(__DIR__, 3) . '/resources/data/announcements.php';

        usort($raw, static fn(array $a, array $b): int => strcmp($b['published_at'], $a['published_at']));

        return $this->responder->page('pages/announcements', $seo, [
            'styles'        => ['announcements.css'],
            'duyurular'     => $raw,
        ]);
    }
}
