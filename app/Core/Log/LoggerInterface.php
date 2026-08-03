<?php

declare(strict_types=1);

namespace App\Core\Log;

use Throwable;

interface LoggerInterface
{
    /**
     * @param array<string, scalar|null> $context
     */
    public function error(string $message, array $context = []): void;

    public function exception(Throwable $exception): void;
}
