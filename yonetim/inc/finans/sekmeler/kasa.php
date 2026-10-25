<?php

declare(strict_types=1);

/**
 * Kasa sekmesi: anlık bakiye, tür toplamları ve seçili yılın aylık net/kümülatif tablosu.
 *
 * @var FinansServisi $finansServis
 * @var FinansDeposu  $finansDepo
 * @var int           $yil
 * @var int[]         $yillar
 */

$ozet   = $finansServis->kasaOzeti();
$aylik  = $finansDepo->aylikToplamlar($yil);
$devreden = $finansDepo->yilOncesiBakiye($yil);

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

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0"><?= $yil ?> Aylık Kasa Özeti</h5>
        <?= finans_yil_secici('kasa', $yil, $yillar) ?>
    </div>
    <div class="finans-tablo-kap">
        <table class="table align-middle mb-0">
            <caption class="visually-hidden"><?= $yil ?> yılı aylık aidat, bağış, gider, net ve kümülatif kasa tablosu</caption>
            <thead class="table-light">
                <tr>
                    <th scope="col">Ay</th>
                    <th scope="col" class="text-end">Aidat</th>
                    <th scope="col" class="text-end">Bağış</th>
                    <th scope="col" class="text-end">Gider</th>
                    <th scope="col" class="text-end">Net</th>
                    <th scope="col" class="text-end">Kasa (Kümülatif)</th>
                </tr>
            </thead>
            <tbody>
                <?php
                $toplamlar = ['gelir_aidat' => 0, 'bagis' => 0, 'gider' => 0];
                $kumulatif = $devreden;
                foreach (FinansDonem::AY_ADLARI as $ay => $ayAdi):
                    $aidat = $aylik[$ay][FinansDeposu::TUR_AIDAT]['kurus'] ?? 0;
                    $bagis = $aylik[$ay][FinansDeposu::TUR_BAGIS]['kurus'] ?? 0;
                    $devir = $aylik[$ay][FinansDeposu::TUR_DEVIR]['kurus'] ?? 0;
                    $gider = $aylik[$ay][FinansDeposu::TUR_GIDER]['kurus'] ?? 0;
                    $net = $aidat + $bagis + $devir - $gider;
                    $kumulatif += $net;
                    $toplamlar['gelir_aidat'] += $aidat + $devir;
                    $toplamlar['bagis'] += $bagis;
                    $toplamlar['gider'] += $gider;
                    $bos = ($aidat + $bagis + $devir + $gider) === 0;
                ?>
                    <tr class="<?= $bos ? 'text-muted' : '' ?>">
                        <th scope="row" class="fw-semibold"><?= finans_e($ayAdi) ?></th>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($aidat + $devir)) ?></td>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($bagis)) ?></td>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($gider)) ?></td>
                        <td class="text-end fw-semibold"><?= finans_para_html($net) ?></td>
                        <td class="text-end fw-bold"><?= finans_para_html($kumulatif) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <th scope="row">Yıl Toplamı</th>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($toplamlar['gelir_aidat'])) ?></td>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($toplamlar['bagis'])) ?></td>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($toplamlar['gider'])) ?></td>
                    <td class="text-end"><?= finans_para_html($toplamlar['gelir_aidat'] + $toplamlar['bagis'] - $toplamlar['gider']) ?></td>
                    <td class="text-end"><?= finans_para_html($kumulatif) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
    <?php if ($devreden !== 0): ?>
        <div class="card-footer bg-white border-0 small text-muted">Önceki yıllardan devreden bakiye: <?= finans_e(FinansTutar::bicimle($devreden)) ?> (kümülatif sütuna dahildir).</div>
    <?php endif; ?>
</div>
