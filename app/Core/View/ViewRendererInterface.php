<?php

declare(strict_types=1);

namespace App\Core\View;

/**
 * Şablon motoru sözleşmesi.
 *
 * Denetleyiciler yalnızca bu arayüze bağımlıdır; ileride farklı bir motora
 * geçilmesi durumunda uygulama katmanı değişmez.
 */
interface ViewRendererInterface
{
    /**
     * Bir sayfa şablonunu ana yerleşim (layout) içine gömerek işler.
     *
     * @param array<string, mixed> $data
     */
    public function renderPage(string $template, SeoMeta $seo, array $data = []): string;

    /**
     * Yerleşim kullanmadan tek bir şablon parçası işler.
     *
     * @param array<string, mixed> $data
     */
    public function partial(string $template, array $data = []): string;

    /**
     * Tüm şablonlarda erişilebilir ortak veriyi tanımlar (site kimliği, menü,
     * aktif yol gibi her sayfada tekrarlanan veriler).
     */
    public function share(string $key, mixed $value): void;
}
