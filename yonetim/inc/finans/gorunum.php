<?php

declare(strict_types=1);

/**
 * Finans sayfaları için ortak görünüm yardımcıları.
 */

/** HTML kaçışı (XSS koruması) — tüm kullanıcı kaynaklı metinler bundan geçer. */
function finans_e(mixed $deger): string
{
    return htmlspecialchars((string) ($deger ?? ''), ENT_QUOTES, 'UTF-8');
}

/**
 * Finans sayfası adresi üretir.
 *
 * @param array<string, string|int> $parametreler
 */
function finans_url(array $parametreler = []): string
{
    return 'index.php?' . http_build_query(['sayfa' => 'finans'] + $parametreler);
}

/** Tutarı biçimlendirir; negatif bakiye kırmızı gösterilir. */
function finans_para_html(int $kurus): string
{
    $sinif = $kurus < 0 ? 'text-danger' : '';

    return '<span class="' . $sinif . '">' . finans_e(FinansTutar::bicimle($kurus)) . '</span>';
}

/** Yıl seçici formu (GET). */
function finans_yil_secici(string $sekme, int $yil, array $yillar): string
{
    $secenekler = '';
    foreach ($yillar as $y) {
        $secenekler .= '<option value="' . $y . '"' . ($y === $yil ? ' selected' : '') . '>' . $y . '</option>';
    }

    return '<form method="get" action="index.php" class="d-flex align-items-center gap-2">'
        . '<input type="hidden" name="sayfa" value="finans"><input type="hidden" name="sekme" value="' . finans_e($sekme) . '">'
        . '<label for="finans-yil" class="small fw-semibold text-muted mb-0">Yıl</label>'
        . '<select id="finans-yil" name="yil" class="form-select form-select-sm" style="width:auto;" onchange="this.form.submit()">'
        . $secenekler . '</select></form>';
}
