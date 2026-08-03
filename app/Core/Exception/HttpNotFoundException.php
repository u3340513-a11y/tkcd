<?php

declare(strict_types=1);

namespace App\Core\Exception;

use RuntimeException;

/**
 * İstenen kaynak bulunamadığında fırlatılır ve 404 sayfasına dönüştürülür.
 */
final class HttpNotFoundException extends RuntimeException
{
}
