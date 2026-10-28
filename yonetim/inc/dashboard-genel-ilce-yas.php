<?php
/**
 * İlçe başkanı dashboard'u — "Trabzon İlçe Dağılımı" ve "Yaş Dağılımı" kartları
 * ile Chart.js grafikleri (tüm üyeler üzerinden).
 *
 * @var array $genel_veri  genel_dashboard_verileri() çıktısı
 */
$ilce_toplam = array_sum($genel_veri['ilce_sayilar']);
$yas_maks    = max(1, ...array_values($genel_veri['yas_dagilimi']));
$yas_var     = array_sum($genel_veri['yas_dagilimi']) > 0;
$yas_renkler = ['#818cf8', '#6366f1', '#4f46e5', '#4338ca', '#3730a3', '#312e81'];
$yas_ozet    = [
    ['Ort. Yaş', $genel_veri['yas_ortalama'], '#4f46e5'],
    ['En Genç', $genel_veri['yas_en_genc'], '#10b981'],
    ['En Yaşlı', $genel_veri['yas_en_yasli'], '#f59e0b'],
];
?>
<div class="row g-4 mb-4">
    <!-- Trabzon İlçe Dağılımı -->
    <div class="col-lg-6">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-map text-success"></i> Trabzon İlçe Dağılımı</h5>
                <span class="dash-card__action">Tüm Üyeler</span>
            </div>
            <div class="dash-card__body text-center">
                <?php if (!empty($genel_veri['ilce_etiketler'])): ?>
                    <div style="position:relative; height:200px; max-height:200px;">
                        <canvas id="genelIlceDagilimi"></canvas>
                    </div>
                    <div class="mt-3 d-flex flex-wrap justify-content-center gap-2" style="max-height:90px; overflow-y:auto;">
                        <?php foreach ($genel_veri['ilce_etiketler'] as $i => $etiket): ?>
                            <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.7rem;">
                                <?= htmlspecialchars($etiket) ?>: %<?= $ilce_toplam > 0 ? round(($genel_veri['ilce_sayilar'][$i] / $ilce_toplam) * 100) : 0 ?>
                            </span>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <p class="text-muted small py-4">Trabzon ilçesi verisi bulunamadı.</p>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <!-- Yaş Dağılımı -->
    <div class="col-lg-6">
        <div class="dash-card h-100">
            <div class="dash-card__header">
                <h5 class="dash-card__title"><i class="fa-solid fa-chart-bar" style="color:#6366f1;"></i> Yaş Dağılımı</h5>
                <span class="dash-card__action">Onaylı Üyeler</span>
            </div>
            <div class="dash-card__body">
                <?php if ($yas_var): ?>
                    <div class="d-flex flex-column gap-2 mb-4">
                        <?php $renk_sirasi = 0; foreach ($genel_veri['yas_dagilimi'] as $grup => $sayi): ?>
                            <div class="d-flex align-items-center gap-2">
                                <span style="width:46px;font-size:.78rem;font-weight:600;color:#64748b;flex-shrink:0;"><?= htmlspecialchars($grup) ?></span>
                                <div style="flex:1;background:#f1f5f9;border-radius:6px;height:14px;overflow:hidden;">
                                    <div style="width:<?= round(($sayi / $yas_maks) * 100) ?>%;background:<?= $yas_renkler[$renk_sirasi++] ?? end($yas_renkler) ?>;height:100%;border-radius:6px;transition:width .6s ease;"></div>
                                </div>
                                <span style="width:36px;font-size:.82rem;font-weight:700;color:#1e293b;text-align:right;flex-shrink:0;"><?= number_format($sayi) ?></span>
                            </div>
                        <?php endforeach; ?>
                    </div>
                    <div class="row g-2">
                        <?php foreach ($yas_ozet as [$etiket, $deger, $renk]): ?>
                            <div class="col-4">
                                <div class="text-center p-2 rounded-3" style="background:#f8fafc;border:1px solid #e2e8f0;">
                                    <div style="font-size:1.3rem;font-weight:800;color:<?= $renk ?>;"><?= $deger !== null ? htmlspecialchars((string) $deger) : '—' ?></div>
                                    <div style="font-size:.72rem;color:#64748b;margin-top:2px;"><?= $etiket ?></div>
                                </div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                <?php else: ?>
                    <div class="text-center py-4" style="color:#94a3b8;">
                        <i class="fa-solid fa-chart-bar fa-2x mb-2"></i>
                        <p class="mb-0" style="font-size:.875rem;">Yaş verisi bulunamadı.</p>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    </div>
</div>

<?php if (!empty($genel_veri['kan_etiketler']) || !empty($genel_veri['ilce_etiketler'])): ?>
<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
(function () {
    'use strict';

    function donutOlustur(canvasId, etiketler, sayilar, renkler, legendGoster, birim) {
        var tuval = document.getElementById(canvasId);
        if (!tuval) { return; }
        var toplam = sayilar.reduce(function (a, b) { return a + b; }, 0);
        new Chart(tuval, {
            type: 'doughnut',
            data: {
                labels: etiketler,
                datasets: [{ data: sayilar, backgroundColor: renkler, borderWidth: 2, borderColor: '#fff' }]
            },
            options: {
                responsive: true,
                maintainAspectRatio: false,
                cutout: '55%',
                plugins: {
                    legend: legendGoster
                        ? { display: true, position: 'bottom', labels: { padding: 8, font: { size: 10 } } }
                        : { display: false },
                    tooltip: {
                        callbacks: {
                            label: function (ctx) {
                                var yuzde = toplam > 0 ? ((ctx.raw / toplam) * 100).toFixed(1) : 0;
                                return ' ' + ctx.label + ': ' + ctx.raw + ' ' + birim + ' (%' + yuzde + ')';
                            }
                        }
                    }
                }
            }
        });
    }

    window.addEventListener('load', function () {
        donutOlustur(
            'genelKanGrubuGrafik',
            <?= json_encode($genel_veri['kan_etiketler'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            <?= json_encode($genel_veri['kan_sayilar']) ?>,
            ['#ef4444', '#f97316', '#f59e0b', '#10b981', '#06b6d4', '#3b82f6', '#8b5cf6', '#ec4899'],
            true,
            'kişi'
        );
        donutOlustur(
            'genelIlceDagilimi',
            <?= json_encode($genel_veri['ilce_etiketler'], JSON_UNESCAPED_UNICODE | JSON_HEX_TAG | JSON_HEX_AMP) ?>,
            <?= json_encode($genel_veri['ilce_sayilar']) ?>,
            ['#10b981', '#3b82f6', '#f59e0b', '#ef4444', '#8b5cf6', '#06b6d4', '#f97316', '#ec4899', '#6366f1', '#14b8a6', '#84cc16', '#a855f7'],
            false,
            'üye'
        );
    });
})();
</script>
<?php endif; ?>
