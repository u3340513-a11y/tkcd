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

// Grafik serileri (TL; yalnızca gösterim için kuruştan çevrilir, hesaplar kuruş üzerinden yapılır).
$seri = ['aidat' => [], 'bagis' => [], 'devir' => [], 'gider' => [], 'kasa' => [], 'odeyen' => []];
$kumulatifKurus = $finansDepo->yilOncesiBakiye($yil);
$kpi = ['gelir' => 0, 'gider' => 0, 'aidat' => 0, 'bagis' => 0, 'devir' => 0];
foreach (FinansDonem::AY_ADLARI as $ay => $ayAdi) {
    $a = $aylik[$ay][FinansDeposu::TUR_AIDAT]['kurus'] ?? 0;
    $b = $aylik[$ay][FinansDeposu::TUR_BAGIS]['kurus'] ?? 0;
    $d = $aylik[$ay][FinansDeposu::TUR_DEVIR]['kurus'] ?? 0;
    $g = $aylik[$ay][FinansDeposu::TUR_GIDER]['kurus'] ?? 0;
    $kumulatifKurus += $a + $b + $d - $g;
    foreach (['aidat' => $a, 'bagis' => $b, 'devir' => $d, 'gider' => $g, 'kasa' => $kumulatifKurus] as $anahtar => $kurus) {
        $seri[$anahtar][] = $kurus / FinansTutar::KURUS_CARPANI;
    }
    $seri['odeyen'][] = $aylik[$ay][FinansDeposu::TUR_AIDAT]['adet'] ?? 0;
    $kpi['aidat'] += $a;
    $kpi['bagis'] += $b;
    $kpi['devir'] += $d;
    $kpi['gider'] += $g;
}
$kpi['gelir'] = $kpi['aidat'] + $kpi['bagis'] + $kpi['devir'];
$veriVar = $kpi['gelir'] + $kpi['gider'] > 0;
$uyeSayisi = $aidatGorebilir ? count($finansDepo->aidatUyeleri()) : 0;
$ayEtiketleri = array_map(static fn (string $ad): string => mb_substr($ad, 0, 3, 'UTF-8'), array_values(FinansDonem::AY_ADLARI));
$grafikVerisi = json_encode(
    ['etiket' => $ayEtiketleri, 'seri' => $seri, 'kpi' => array_map(static fn (int $k): float => $k / FinansTutar::KURUS_CARPANI, $kpi), 'uyeSayisi' => $uyeSayisi],
    JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP | JSON_HEX_APOS | JSON_HEX_QUOT | JSON_THROW_ON_ERROR
);
$kartlar = [
    ['Toplam Gelir', $kpi['gelir'], 'fa-arrow-trend-up', '#16a085'],
    ['Toplam Gider', $kpi['gider'], 'fa-arrow-trend-down', '#c0392b'],
    ['Net Sonuç', $kpi['gelir'] - $kpi['gider'], 'fa-scale-balanced', '#0f3460'],
    ['Yıl Sonu Kasa', $kumulatifKurus, 'fa-vault', '#8e44ad'],
];

$disaAktarim = [
    'tumu'  => ['Tüm hareketler', $disaAktarabilir],
    'aidat' => ['Aidat', $disaAktarabilir && $aidatGorebilir],
    'bagis' => ['Bağış', $disaAktarabilir],
    'gider' => ['Gider', $disaAktarabilir],
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

<?php if (!$veriVar): ?>
    <div class="card border-0 shadow-sm rounded-4 mb-4">
        <div class="card-body text-center text-muted py-5">
            <i class="fa-solid fa-chart-column fs-2 d-block mb-2"></i><?= $yil ?> yılı için henüz grafik oluşturacak kayıt yok.
        </div>
    </div>
<?php else: ?>
    <div class="row g-3 mb-3">
        <?php foreach ($kartlar as [$etiket, $kurus, $ikon, $renk]): ?>
            <div class="col-sm-6 col-xl-3">
                <div class="card border-0 shadow-sm rounded-4 h-100">
                    <div class="card-body d-flex align-items-center gap-3">
                        <span class="rounded-circle d-inline-flex align-items-center justify-content-center flex-shrink-0"
                              style="width:48px;height:48px;background:<?= $renk ?>1a;color:<?= $renk ?>;"><i class="fa-solid <?= $ikon ?>"></i></span>
                        <div>
                            <div class="small text-muted"><?= finans_e($etiket) ?></div>
                            <div class="fw-bold fs-5"><?= finans_para_html($kurus) ?></div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endforeach; ?>
    </div>

    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-0">Aylık Gelir / Gider ve Kasa Seyri</h6>
                    <p class="small text-muted">Sütunlar aylık gelir ve gideri, çizgi kümülatif kasa bakiyesini gösterir.</p>
                    <div class="finans-grafik" style="height:340px;"><canvas id="grafik-aylik" role="img" aria-label="<?= $yil ?> aylık gelir, gider ve kümülatif kasa grafiği"></canvas></div>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-body">
                    <h6 class="fw-bold mb-0">Gelir Dağılımı</h6>
                    <p class="small text-muted">Aidat, bağış ve devir payı.</p>
                    <div class="finans-grafik" style="height:340px;"><canvas id="grafik-dagilim" role="img" aria-label="<?= $yil ?> gelir dağılımı grafiği"></canvas></div>
                </div>
            </div>
        </div>
        <?php if ($aidatGorebilir && $uyeSayisi > 0): ?>
            <div class="col-12">
                <div class="card border-0 shadow-sm rounded-4">
                    <div class="card-body">
                        <h6 class="fw-bold mb-0">Aidat Tahsilatı: Ödeyen Kişi Sayısı</h6>
                        <p class="small text-muted">Her ay aidat ödeyen kişi sayısı; kesikli çizgi toplam kişi sayısıdır (<?= $uyeSayisi ?>).</p>
                        <div class="finans-grafik"><canvas id="grafik-tahsilat" role="img" aria-label="<?= $yil ?> aylık aidat ödeyen kişi sayısı grafiği"></canvas></div>
                    </div>
                </div>
            </div>
        <?php endif; ?>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.1/dist/chart.umd.min.js"></script>
    <script>
    (function () {
        if (typeof Chart === 'undefined') { return; }
        var veri = <?= $grafikVerisi ?>;
        var para = function (n) { return new Intl.NumberFormat('tr-TR', { style: 'currency', currency: 'TRY', maximumFractionDigits: 0 }).format(n); };
        var temel = { responsive: true, maintainAspectRatio: false, interaction: { mode: 'index', intersect: false },
            plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } } } };
        var tl = { callbacks: { label: function (c) { return ' ' + c.dataset.label + ': ' + para(c.parsed.y !== undefined ? c.parsed.y : c.parsed); } } };

        var aylik = document.getElementById('grafik-aylik');
        if (aylik) {
            var setler = [
                { type: 'bar', label: 'Aidat', data: veri.seri.aidat, backgroundColor: '#16a085', stack: 'gelir', borderRadius: 4, order: 2 },
                { type: 'bar', label: 'Bağış', data: veri.seri.bagis, backgroundColor: '#2980b9', stack: 'gelir', borderRadius: 4, order: 2 }
            ];
            if (veri.kpi.devir !== 0) {
                setler.push({ type: 'bar', label: 'Devir', data: veri.seri.devir, backgroundColor: '#8e44ad', stack: 'gelir', borderRadius: 4, order: 2 });
            }
            setler.push({ type: 'bar', label: 'Gider', data: veri.seri.gider, backgroundColor: '#c0392b', stack: 'gider', borderRadius: 4, order: 2 });
            setler.push({ type: 'line', label: 'Kasa (kümülatif)', data: veri.seri.kasa, borderColor: '#0f3460', backgroundColor: 'rgba(15,52,96,.08)',
                borderWidth: 3, tension: .35, fill: true, pointRadius: 3, order: 1 });
            new Chart(aylik, { data: { labels: veri.etiket, datasets: setler },
                options: Object.assign({}, temel, { plugins: Object.assign({}, temel.plugins, { tooltip: tl }),
                    scales: { x: { stacked: true, grid: { display: false } },
                              y: { stacked: false, ticks: { callback: function (v) { return para(v); } } } } }) });
        }

        var dagilim = document.getElementById('grafik-dagilim');
        if (dagilim) {
            new Chart(dagilim, { type: 'doughnut',
                data: { labels: ['Aidat', 'Bağış', 'Devir'], datasets: [{ data: [veri.kpi.aidat, veri.kpi.bagis, veri.kpi.devir],
                    backgroundColor: ['#16a085', '#2980b9', '#8e44ad'], borderColor: '#fff', borderWidth: 3 }] },
                options: { responsive: true, maintainAspectRatio: false, cutout: '65%',
                    plugins: { legend: { position: 'bottom', labels: { usePointStyle: true, boxWidth: 8 } },
                        tooltip: { callbacks: { label: function (c) { return ' ' + c.label + ': ' + para(c.parsed); } } } } } });
        }

        var tahsilat = document.getElementById('grafik-tahsilat');
        if (tahsilat) {
            new Chart(tahsilat, { data: { labels: veri.etiket, datasets: [
                { type: 'bar', label: 'Ödeyen kişi', data: veri.seri.odeyen, backgroundColor: '#16a085', borderRadius: 4 },
                { type: 'line', label: 'Toplam kişi', data: veri.etiket.map(function () { return veri.uyeSayisi; }),
                    borderColor: '#94a3b8', borderDash: [6, 6], borderWidth: 2, pointRadius: 0 }] },
                options: Object.assign({}, temel, { scales: { x: { grid: { display: false } },
                    y: { beginAtZero: true, suggestedMax: veri.uyeSayisi, ticks: { precision: 0 } } } }) });
        }
    })();
    </script>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4">
    <div class="card-header bg-white border-0 pt-3">
        <h5 class="fw-bold mb-0"><?= $yil ?> Aylık Rapor</h5>
        <p class="small text-muted mb-0">Yalnızca aktif (iptal edilmemiş) kayıtlar dahildir.</p>
    </div>
    <div class="finans-tablo-kap">
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
