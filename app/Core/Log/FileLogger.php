<?php

declare(strict_types=1);

namespace App\Core\Log;

use App\Core\Config;
use Throwable;

/**
 * Günlük bazlı dosya kaydedici.
 *
 * Neden: Hatalar ziyaretçiye gösterilmez ama kaybolmamalıdır. Kayıtlar web
 * kökü dışındaki storage/logs dizininde tutulur ve tarayıcıdan erişilemez.
 */
final class FileLogger implements LoggerInterface
{
    private readonly string $directory;

    public function __construct(private readonly Config $config)
    {
        $this->directory = rtrim($this->config->string('app.paths.logs'), '/');
    }

    public function error(string $message, array $context = []): void
    {
        $this->write('ERROR', $message, $context);
    }

    public function exception(Throwable $exception): void
    {
        $this->write('ERROR', $exception->getMessage(), [
            'type' => $exception::class,
            'file' => $exception->getFile(),
            'line' => $exception->getLine(),
        ]);
    }

    /**
     * @param array<string, scalar|null> $context
     */
    private function write(string $level, string $message, array $context): void
    {
        if (!is_dir($this->directory) && !mkdir($this->directory, 0750, true) && !is_dir($this->directory)) {
            return;
        }

        $line = sprintf(
            "[%s] %s: %s %s%s",
            date('c'),
            $level,
            str_replace(["\r", "\n"], ' ', $message),
            $context === [] ? '' : json_encode($context, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            PHP_EOL,
        );

        @file_put_contents(
            $this->directory . '/app-' . date('Y-m-d') . '.log',
            $line,
            FILE_APPEND | LOCK_EX,
        );
    }
}
