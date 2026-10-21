<?php

declare(strict_types=1);

/**
 * Raporlar sekmesi: seçili yılın aylık aidat / bağış / gider özeti + CSV dışa aktarım bağlantıları.
 *
 * @var FinansDeposu $finansDepo
 * @var int          $yil
 * @var int[]        $yillar
 * @var bool         $aidatGorebilir
 */

$aylik = $finansDepo->aylikToplamlar($yil);

$disaAktarim = [
    'tumu'  => ['Tüm hareketler', true],
    'aidat' => ['Aidat', $aidatGorebilir],
    'bagis' => ['Bağış', true],
    'gider' => ['Gider', true],
];

$yilToplam = ['aidat' => 0, 'bagis' => 0, 'gider' => 0, 'adet_aidat' => 0, 'adet_bagis' => 0, 'adet_gider' => 0];
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <?php foreach ($disaAktarim as $tur => [$etiket, $gorunur]): ?>
            <?php if ($gorunur): ?>
                <a class="btn btn-outline-dark btn-sm" href="<?= finans_e('index.php?' . http_build_query(['sayfa' => 'finans-disa-aktar', 'tur' => $tur, 'yil' => $yil])) ?>">
                    <i class="fa-solid fa-file-csv me-1"></i><?= finans_e($etiket) ?> (<?= $yil ?>) CSV
                </a>
            <?php endif; ?>
        <?php endforeach; ?>
    </div>
    <?= finans_yil_secici('raporlar', $yil, $yillar) ?>
</div>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <h5 class="fw-bold mb-0"><?= $yil ?> Aylık Rapor</h5>
        <p class="small text-muted mb-0">Yalnızca aktif (iptal edilmemiş) kayıtlar dahildir.</p>
    </div>
    <div class="table-responsive">
        <table class="table align-middle mb-0">
            <caption class="visually-hidden"><?= $yil ?> yılı aylık aidat, bağış ve gider adet ve tutar raporu</caption>
            <thead class="table-light">
                <tr>
                    <th scope="col">Ay</th>
                    <th scope="col" class="text-end">Aidat (adet)</th>
                    <th scope="col" class="text-end">Aidat (₺)</th>
                    <th scope="col" class="text-end">Bağış (adet)</th>
                    <th scope="col" class="text-end">Bağış (₺)</th>
                    <th scope="col" class="text-end">Gider (adet)</th>
                    <th scope="col" class="text-end">Gider (₺)</th>
                    <th scope="col" class="text-end">Net</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach (FinansDonem::AY_ADLARI as $ay => $ayAdi):
                    $a = $aylik[$ay][FinansDeposu::TUR_AIDAT] ?? ['kurus' => 0, 'adet' => 0];
                    $b = $aylik[$ay][FinansDeposu::TUR_BAGIS] ?? ['kurus' => 0, 'adet' => 0];
                    $g = $aylik[$ay][FinansDeposu::TUR_GIDER] ?? ['kurus' => 0, 'adet' => 0];
                    $d = $aylik[$ay][FinansDeposu::TUR_DEVIR]['kurus'] ?? 0;
                    $yilToplam['aidat'] += $a['kurus'] + $d;
                    $yilToplam['bagis'] += $b['kurus'];
                    $yilToplam['gider'] += $g['kurus'];
                    $yilToplam['adet_aidat'] += $a['adet'];
                    $yilToplam['adet_bagis'] += $b['adet'];
                    $yilToplam['adet_gider'] += $g['adet'];
                ?>
                    <tr>
                        <th scope="row" class="fw-semibold"><?= finans_e($ayAdi) ?></th>
                        <td class="text-end"><?= $a['adet'] ?></td>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($a['kurus'] + $d)) ?></td>
                        <td class="text-end"><?= $b['adet'] ?></td>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($b['kurus'])) ?></td>
                        <td class="text-end"><?= $g['adet'] ?></td>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($g['kurus'])) ?></td>
                        <td class="text-end fw-semibold"><?= finans_para_html($a['kurus'] + $d + $b['kurus'] - $g['kurus']) ?></td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
            <tfoot class="table-light fw-bold">
                <tr>
                    <th scope="row">Yıl Toplamı</th>
                    <td class="text-end"><?= $yilToplam['adet_aidat'] ?></td>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($yilToplam['aidat'])) ?></td>
                    <td class="text-end"><?= $yilToplam['adet_bagis'] ?></td>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($yilToplam['bagis'])) ?></td>
                    <td class="text-end"><?= $yilToplam['adet_gider'] ?></td>
                    <td class="text-end"><?= finans_e(FinansTutar::bicimle($yilToplam['gider'])) ?></td>
                    <td class="text-end"><?= finans_para_html($yilToplam['aidat'] + $yilToplam['bagis'] - $yilToplam['gider']) ?></td>
                </tr>
            </tfoot>
        </table>
    </div>
</div>
