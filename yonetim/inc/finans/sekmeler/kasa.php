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

$aylik  = $finansDepo->aylikToplamlar($yil);
$devreden = $finansDepo->yilOncesiBakiye($yil);

include __DIR__ . '/_kasa-ozet.php';
?>

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
