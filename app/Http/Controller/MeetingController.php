<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Core\Exception\HttpNotFoundException;
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
    private const DEFAULT_DESIGN = 1;
    private const DESIGNS = [1, 2, 3];
    private const DATA_FILE = '/resources/data/district-chairs.php';

    public function __construct(private readonly ViewRendererInterface $view)
    {
    }

    public function districtChairs(): Response
    {
        return $this->render(self::DEFAULT_DESIGN);
    }

    /**
     * @throws HttpNotFoundException Tasarım numarası tanımlı değilse.
     */
    public function districtChairsDesign(string $tasarim): Response
    {
        $design = ctype_digit($tasarim) ? (int) $tasarim : 0;

        if (!in_array($design, self::DESIGNS, true)) {
            throw new HttpNotFoundException(sprintf('Sunum tasarımı bulunamadı: %s', $tasarim));
        }

        return $this->render($design);
    }

    private function render(int $design): Response
    {
        /** @var list<array{ad: string, ilce: string, fotograf: string}> $chairs */
        $chairs = require dirname(__DIR__, 3) . self::DATA_FILE;

        return Response::html($this->view->partial('pages/meeting/district-chairs', [
            'tasarim'   => $design,
            'baskanlar' => $chairs,
        ]));
    }
}
