<?php

declare(strict_types=1);

namespace App\Core\View;

use App\Core\Config;
use App\Core\Exception\ViewNotFoundException;
use App\Core\Support\Html;
use App\Core\Support\Text;
use Throwable;

/**
 * Saf PHP şablon motoru.
 *
 * Neden: Ek bir şablon kütüphanesi (ve derleme adımı) getirmeden, OPcache
 * ile hızlı çalışan, cPanel'de sorunsuz dağıtılabilen bir çözüm sağlar.
 *
 * Şablonlar $view değişkeni üzerinden kaçırma, varlık ve ikon yardımcılarına
 * erişir; ham çıktı basma alışkanlığı engellenmiş olur.
 */
final class PhpViewRenderer implements ViewRendererInterface
{
    private const LAYOUT = 'layouts/base';
    private const TEMPLATE_PATTERN = '#^[a-z0-9][a-z0-9._/-]*$#i';

    /** @var array<string, mixed> */
    private array $shared = [];

    private readonly string $viewPath;

    public function __construct(
        private readonly Config $config,
        private readonly AssetManager $assets,
        private readonly IconSet $icons,
    ) {
        $this->viewPath = rtrim($this->config->string('app.paths.views'), '/');
    }

    /**
     * Tüm şablonlarda erişilebilir olacak veriyi tanımlar (site bilgileri,
     * menü, aktif yol gibi her sayfada tekrar eden veriler).
     */
    public function share(string $key, mixed $value): void
    {
        $this->shared[$key] = $value;
    }

    public function renderPage(string $template, SeoMeta $seo, array $data = []): string
    {
        // SEO üst verisi hem sayfa şablonuna (kırıntı navigasyonu gibi kullanımlar
        // için) hem de yerleşime aktarılır.
        $data = ['seo' => $seo] + $data;

        return $this->partial(self::LAYOUT, ['content' => $this->partial($template, $data)] + $data);
    }

    public function partial(string $template, array $data = []): string
    {
        $file = $this->resolve($template);

        // Şablon içinden erişilen değişkenler: paylaşılan veri + yerel veri.
        $scope = [...$this->shared, ...$data, 'view' => $this];

        ob_start();

        try {
            (static function (string $__file, array $__scope): void {
                extract($__scope, EXTR_SKIP);

                require $__file;
            })($file, $scope);
        } catch (Throwable $exception) {
            ob_end_clean();

            throw $exception;
        }

        return (string) ob_get_clean();
    }

    // ------------------------------------------------------------------
    // Şablon yardımcıları
    // ------------------------------------------------------------------

    public function e(?string $value): string
    {
        return Html::escape($value);
    }

    public function link(?string $value): string
    {
        return Html::url($value);
    }

    public function asset(string $path): string
    {
        return $this->assets->url($path);
    }

    public function absolute(string $path): string
    {
        return $this->assets->absolute($path);
    }

    public function assetExists(string $path): bool
    {
        return $this->assets->exists($path);
    }

    public function icon(string $name, string $class = 'icon'): string
    {
        return $this->icons->render($name, $class);
    }

    /**
     * @param array<string, mixed> $data
     */
    public function json(array $data): string
    {
        return Html::json($data);
    }

    public function excerpt(string $value, int $limit = 160): string
    {
        return Text::excerpt($value, $limit);
    }

    public function date(string $isoDate): string
    {
        return Text::longDate($isoDate);
    }

    /**
     * @param array<string, bool>|list<string> $classes
     */
    public function classes(array $classes): string
    {
        return Html::classes($classes);
    }

    public function config(string $key, string $default = ''): string
    {
        return $this->config->string($key, $default);
    }

    /**
     * Dizin dışına çıkma (path traversal) girişimlerini engelleyerek
     * şablon dosyasının gerçek yolunu çözer.
     */
    private function resolve(string $template): string
    {
        if (preg_match(self::TEMPLATE_PATTERN, $template) !== 1 || str_contains($template, '..')) {
            throw new ViewNotFoundException(sprintf('Geçersiz şablon adı: %s', $template));
        }

        $file = $this->viewPath . '/' . $template . '.php';
        $real = realpath($file);

        if ($real === false || !str_starts_with($real, $this->viewPath . DIRECTORY_SEPARATOR)) {
            throw new ViewNotFoundException(sprintf('Şablon bulunamadı: %s', $template));
        }

        return $real;
    }
}
