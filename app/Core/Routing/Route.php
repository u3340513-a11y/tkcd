<?php

declare(strict_types=1);

namespace App\Core\Routing;

/**
 * Tek bir rota tanımı.
 *
 * Yol şablonunda "{parametre}" yer tutucuları desteklenir; eşleşen değerler
 * denetleyici metoduna sırayla aktarılır. Parametreler yalnızca küçük harf,
 * rakam ve tire içerebilir (slug); bu kısıt sayesinde yol üzerinden zararlı
 * girdi taşınamaz.
 */
final class Route
{
    private const PLACEHOLDER_PATTERN = '#^\{[a-zA-Z_][a-zA-Z0-9_]*\}$#';
    private const SLUG_PATTERN = '([a-z0-9]+(?:-[a-z0-9]+)*)';

    private readonly ?string $compiledPattern;

    public function __construct(
        public readonly string $method,
        public readonly string $path,
        public readonly string $controller,
        public readonly string $action,
        public readonly string $name,
    ) {
        $this->compiledPattern = $this->compile($path);
    }

    public function isDynamic(): bool
    {
        return $this->compiledPattern !== null;
    }

    /**
     * @return list<string>|null Eşleşme yoksa null, varsa parametre listesi.
     */
    public function match(string $method, string $path): ?array
    {
        if ($this->method !== $method) {
            return null;
        }

        if ($this->compiledPattern === null) {
            return $this->path === $path ? [] : null;
        }

        if (preg_match($this->compiledPattern, $path, $matches) !== 1) {
            return null;
        }

        array_shift($matches);

        return array_values($matches);
    }

    /**
     * Statik rotalarda null döner; böylece eşleştirme sırasında düz karşılaştırma
     * yapılır ve gereksiz regex maliyeti oluşmaz.
     */
    private function compile(string $path): ?string
    {
        if (!str_contains($path, '{')) {
            return null;
        }

        $segments = preg_split('#(\{[a-zA-Z_][a-zA-Z0-9_]*\})#', $path, -1, PREG_SPLIT_DELIM_CAPTURE);

        if ($segments === false) {
            return null;
        }

        $pattern = '';

        foreach ($segments as $segment) {
            if ($segment === '') {
                continue;
            }

            $pattern .= preg_match(self::PLACEHOLDER_PATTERN, $segment) === 1
                ? self::SLUG_PATTERN
                : preg_quote($segment, '#');
        }

        return '#^' . $pattern . '$#';
    }
}
