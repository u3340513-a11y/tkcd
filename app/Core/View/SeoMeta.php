<?php

declare(strict_types=1);

namespace App\Core\View;

/**
 * Sayfa başına SEO üst verisini taşıyan değişmez değer nesnesi.
 *
 * Girdi : başlık, açıklama, kanonik yol, görsel, indeksleme tercihi, JSON-LD
 * Çıktı : head bölümünde kullanılacak tutarlı üst veri
 */
final class SeoMeta
{
    /**
     * @param list<array<string, mixed>> $structuredData Şema.org JSON-LD blokları
     * @param list<array{label: string, path: string}> $breadcrumbs
     */
    public function __construct(
        public readonly string $title,
        public readonly string $description,
        public readonly string $canonicalPath = '/',
        public readonly ?string $image = null,
        public readonly bool $indexable = true,
        public readonly string $type = 'website',
        public readonly array $structuredData = [],
        public readonly array $breadcrumbs = [],
    ) {
    }

    /**
     * @param list<array<string, mixed>> $data
     */
    public function withStructuredData(array $data): self
    {
        return new self(
            $this->title,
            $this->description,
            $this->canonicalPath,
            $this->image,
            $this->indexable,
            $this->type,
            [...$this->structuredData, ...$data],
            $this->breadcrumbs,
        );
    }
}
