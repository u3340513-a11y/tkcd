<?php

declare(strict_types=1);

/**
 * Kasa özet kartları: anlık bakiye + toplam aidat / bağış / gider.
 *
 * Neden ayrı dosya: Hem tam Kasa sekmesi hem de yalnızca özet görebilen hesaplar
 * (yonetim_*) aynı kartları kullanır; kişi veya kayıt bazlı hiçbir veri içermez.
 *
 * @var FinansServisi $finansServis
 */

$ozet = $finansServis->kasaOzeti();

$kartlar = [
    ['Toplam Aidat', $ozet['aidat'], 'fa-table-cells', '#16a085'],
    ['Toplam Bağış', $ozet['bagis'], 'fa-hand-holding-heart', '#2980b9'],
    ['Toplam Gider', $ozet['gider'], 'fa-receipt', '#c0392b'],
];
?>

<div class="row g-3 mb-4">
    <div class="col-lg-5">
        <div class="rounded-4 p-4 h-100 text-white" style="background:linear-gradient(135deg,#0f3460,#16213e);box-shadow:0 10px 30px rgba(15,52,96,.25);">
            <div class="small text-uppercase fw-semibold" style="opacity:.75;letter-spacing:.06em;">Kasa Toplam (Anlık Bakiye)</div>
            <div class="fw-bold mt-1" style="font-size:2.4rem;line-height:1.1;" aria-live="polite">
                <?= finans_e(FinansTutar::bicimle($ozet['bakiye'])) ?>
            </div>
            <div class="small mt-2" style="opacity:.7;">Gelir <?= finans_e(FinansTutar::bicimle($ozet['gelir'])) ?> − Gider <?= finans_e(FinansTutar::bicimle($ozet['gider'])) ?></div>
        </div>
    </div>
    <div class="col-lg-7">
        <div class="row g-3 h-100">
            <?php foreach ($kartlar as [$etiket, $tutar, $ikon, $renk]): ?>
                <div class="col-sm-6">
                    <div class="card border-0 shadow-sm rounded-4 h-100">
                        <div class="card-body d-flex align-items-center gap-3">
                            <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                                  style="width:44px;height:44px;background:<?= $renk ?>1a;color:<?= $renk ?>;"><i class="fa-solid <?= $ikon ?>"></i></span>
                            <div>
                                <div class="small text-muted"><?= finans_e($etiket) ?></div>
                                <div class="fw-bold fs-5"><?= finans_e(FinansTutar::bicimle($tutar)) ?></div>
                            </div>
                        </div>
                    </div>
                </div>
            <?php endforeach; ?>
        </div>
    </div>
</div>
