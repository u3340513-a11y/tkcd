<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Http\Response;

/**
 * Yönetim Kurulu sayfası denetleyicisi.
 *
 * Üye verisi dosya tabanlı kaynaktan okunur; DB entegrasyonu
 * sağlandığında yalnızca bu sınıf güncellenir.
 */
final class BoardController
{
    public function __construct(private readonly PageResponder $responder)
    {
    }

    public function index(): Response
    {
        $seo = $this->responder->seo(
            title: 'Yönetim Kurulu',
            description: 'Trabzonlu Kamu Çalışanları Derneği yönetim kurulu üyeleri, '
                . 'görev dağılımı ve organizasyon yapısı.',
            canonicalPath: '/yonetim-kurulu',
            breadcrumbs: [['label' => 'Yönetim Kurulu', 'path' => '/yonetim-kurulu']],
        );

        /** @var list<array{slug:string,ad:string,unvan:string,fotograf:string,biyografi:string,gorevler:list<string>,sosyal:array<string,string>}> $uyeler */
        $uyeler = require dirname(__DIR__, 3) . '/resources/data/board.php';

        return $this->responder->page('pages/board', $seo, [
            'styles' => ['board.css'],
            'uyeler' => $uyeler,
        ]);
    }
}
