<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Http\Response;
use App\Core\View\ViewRendererInterface;

/**
 * Toplantılarda ekrana yansıtılan tam ekran sunum sayfaları.
 *
 * Sunum sayfaları site üst/alt bilgisi taşımaz ve arama motorlarına kapalıdır;
 * bu yüzden ana yerleşim (layout) yerine bağımsız bir şablonla üretilir.
 */
final class MeetingController
{
    private const DATA_FILE = '/resources/data/district-chairs.php';

    public function __construct(private readonly ViewRendererInterface $view)
    {
    }

    public function districtChairs(): Response
    {
        /** @var list<array{ad: string, ilce: string, gorsel: string}> $chairs */
        $chairs = require dirname(__DIR__, 3) . self::DATA_FILE;

        return Response::html($this->view->partial('pages/meeting/district-chairs', [
            'baskanlar' => $chairs,
        ]));
    }
}
