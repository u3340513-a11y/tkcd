<?php

declare(strict_types=1);

/**
 * Aidat sekmesi: kişi × ay matrisi (kim ödedi / ödemedi), giriş formları ve ödeme kayıt listesi.
 *
 * Bu sekme kişi bazlı bilgi içerdiği için yalnızca AIDAT_GORUNTULE yetkisine sahip hesaplara gösterilir.
 *
 * @var FinansDeposu $finansDepo
 * @var string       $aktifSekme
 * @var int          $yil
 * @var int[]        $yillar
 * @var bool         $kayitGirebilir
 * @var DateTimeImmutable $bugun
 */

$uyeler   = $finansDepo->aidatUyeleri();
$odemeler = $finansDepo->aidatOdemeleri($yil);

$bugunYil = (int) $bugun->format('Y');
$bugunAy  = (int) $bugun->format('n');

/** Ay, "ödenmesi beklenen" mi: geçmiş yıl ya da bu yılın içinde bulunulan aya kadar. */
$vadesiGeldiMi = static fn (int $ay): bool => $yil < $bugunYil || ($yil === $bugunYil && $ay <= $bugunAy);

$ayToplamlari = array_fill(1, 12, 0);
$yilToplami   = 0;
$odemeyenSayisi = 0;
foreach ($uyeler as $uye) {
    foreach ($odemeler[(int) $uye['id']] ?? [] as $ay => $kurus) {
        $ayToplamlari[$ay] += $kurus;
        $yilToplami += $kurus;
    }
    if ($yil === $bugunYil && !isset($odemeler[(int) $uye['id']][$bugunAy])) {
        $odemeyenSayisi++;
    }
}
?>

<div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mb-3">
    <div class="d-flex gap-2 flex-wrap">
        <span class="badge text-bg-light border fs-6 fw-semibold"><i class="fa-solid fa-users me-1"></i><?= count($uyeler) ?> kişi</span>
        <span class="badge text-bg-light border fs-6 fw-semibold"><i class="fa-solid fa-coins me-1"></i><?= $yil ?> toplamı: <?= finans_e(FinansTutar::bicimle($yilToplami)) ?></span>
        <?php if ($yil === $bugunYil): ?>
            <span class="badge <?= $odemeyenSayisi > 0 ? 'text-bg-danger' : 'text-bg-success' ?> fs-6 fw-semibold">
                <?= finans_e(FinansDonem::AY_ADLARI[$bugunAy]) ?> ayında ödemeyen: <?= $odemeyenSayisi ?>
            </span>
        <?php endif; ?>
    </div>
    <?= finans_yil_secici('aidat', $yil, $yillar) ?>
</div>

<?php if ($kayitGirebilir): ?>
    <div class="row g-3 mb-4">
        <div class="col-lg-8">
            <div class="card border-0 shadow-sm rounded-4 h-100" id="fa-odeme-karti">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-circle-plus me-2"></i>Aidat Ödemesi Ekle</h5>
                    <p class="small text-muted mb-0">Matristeki kırmızı hücreye tıklarsanız kişi ve ay otomatik seçilir.</p>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?sayfa=finans" class="row g-3" autocomplete="off">
                        <?= csrf_hidden_alan() ?>
                        <input type="hidden" name="islem" value="aidat_kaydet">
                        <input type="hidden" name="yil" value="<?= $yil ?>">
                        <div class="col-md-4">
                            <label for="fa-uye" class="form-label small fw-semibold">Kişi</label>
                            <select id="fa-uye" name="aidat_uye_id" class="form-select" required>
                                <option value="">Seçiniz</option>
                                <?php foreach ($uyeler as $uye): ?>
                                    <option value="<?= (int) $uye['id'] ?>"><?= finans_e($uye['ad_soyad']) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <label for="fa-ay" class="form-label small fw-semibold">Ay</label>
                            <select id="fa-ay" name="ay" class="form-select" required>
                                <?php foreach (FinansDonem::AY_ADLARI as $ay => $ayAdi): ?>
                                    <option value="<?= $ay ?>" <?= $ay === $bugunAy ? 'selected' : '' ?>><?= finans_e($ayAdi) ?></option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label for="fa-tarih" class="form-label small fw-semibold">Ödeme Tarihi</label>
                            <input type="date" id="fa-tarih" name="tarih" class="form-control" value="<?= $bugun->format('Y-m-d') ?>" required>
                        </div>
                        <div class="col-md-3">
                            <label for="fa-tutar" class="form-label small fw-semibold">Tutar (TL)</label>
                            <input type="text" id="fa-tutar" name="tutar" class="form-control" inputmode="decimal" placeholder="1.500,00" required>
                        </div>
                        <div class="col-md-9">
                            <label for="fa-aciklama" class="form-label small fw-semibold">Açıklama</label>
                            <input type="text" id="fa-aciklama" name="aciklama" class="form-control" maxlength="<?= FinansServisi::AZAMI_ACIKLAMA ?>">
                        </div>
                        <div class="col-md-3 d-flex align-items-end">
                            <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Kaydet</button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
        <div class="col-lg-4">
            <div class="card border-0 shadow-sm rounded-4 h-100">
                <div class="card-header bg-white border-0 pt-3">
                    <h5 class="fw-bold mb-0"><i class="fa-solid fa-user-plus me-2"></i>Kişi Ekle</h5>
                    <p class="small text-muted mb-0">Aidat listesine yeni kişi.</p>
                </div>
                <div class="card-body">
                    <form method="post" action="index.php?sayfa=finans" class="d-grid gap-3" autocomplete="off">
                        <?= csrf_hidden_alan() ?>
                        <input type="hidden" name="islem" value="aidat_uye_ekle">
                        <input type="hidden" name="yil" value="<?= $yil ?>">
                        <div>
                            <label for="fa-yeni-ad" class="form-label small fw-semibold">Ad Soyad</label>
                            <input type="text" id="fa-yeni-ad" name="ad_soyad" class="form-control" maxlength="<?= FinansServisi::AZAMI_TARAF ?>" required>
                        </div>
                        <button type="submit" class="btn btn-outline-dark"><i class="fa-solid fa-plus me-1"></i>Ekle</button>
                    </form>
                </div>
            </div>
        </div>
    </div>
<?php endif; ?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h5 class="fw-bold mb-0"><?= $yil ?> Aidat Tablosu</h5>
        <p class="small text-muted mb-0">
            <span class="text-success fw-semibold">Yeşil</span> ödendi ·
            <span class="text-danger fw-semibold">Kırmızı</span> ödenmedi ·
            <span class="text-muted">Gri</span> vadesi gelmedi
        </p>
    </div>
    <div class="table-responsive">
        <?php if ($uyeler === []): ?>
            <div class="text-center text-muted py-5">Henüz kişi eklenmemiş. "Kişi Ekle" ile başlayın ya da Excel Aktarımı sekmesini kullanın.</div>
        <?php else: ?>
            <table class="table table-sm align-middle mb-0 text-nowrap">
                <caption class="visually-hidden"><?= $yil ?> yılı kişi bazlı aylık aidat ödeme tablosu</caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col" class="text-center">#</th>
                        <th scope="col">İsim Soyisim</th>
                        <?php foreach (FinansDonem::AY_ADLARI as $ayAdi): ?>
                            <th scope="col" class="text-end"><?= finans_e(mb_substr($ayAdi, 0, 3, 'UTF-8')) ?></th>
                        <?php endforeach; ?>
                        <th scope="col" class="text-end">Toplam</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($uyeler as $uye):
                        $uyeId = (int) $uye['id'];
                        $uyeToplam = array_sum($odemeler[$uyeId] ?? []);
                    ?>
                        <tr>
                            <td class="text-center text-muted"><?= (int) $uye['sira'] ?></td>
                            <th scope="row" class="fw-semibold"><?= finans_e($uye['ad_soyad']) ?></th>
                            <?php foreach (FinansDonem::AY_ADLARI as $ay => $ayAdi):
                                $kurus = $odemeler[$uyeId][$ay] ?? null;
                            ?>
                                <?php if ($kurus !== null): ?>
                                    <td class="text-end text-success fw-semibold"><?= finans_e(FinansTutar::bicimle($kurus, false)) ?></td>
                                <?php elseif ($vadesiGeldiMi($ay)): ?>
                                    <td class="text-end">
                                        <?php if ($kayitGirebilir): ?>
                                            <button type="button" class="btn btn-link btn-sm text-danger p-0 text-decoration-none fw-bold"
                                                    data-fa-uye="<?= $uyeId ?>" data-fa-ay="<?= $ay ?>"
                                                    aria-label="<?= finans_e($uye['ad_soyad'] . ' ' . $ayAdi . ' aidatı ödenmedi, ödeme ekle') ?>">—</button>
                                        <?php else: ?>
                                            <span class="text-danger fw-bold">—</span>
                                        <?php endif; ?>
                                    </td>
                                <?php else: ?>
                                    <td class="text-end text-muted">·</td>
                                <?php endif; ?>
                            <?php endforeach; ?>
                            <td class="text-end fw-bold"><?= finans_e(FinansTutar::bicimle($uyeToplam, false)) ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
                <tfoot class="table-light fw-bold">
                    <tr>
                        <th scope="row" colspan="2">Aylık Toplam</th>
                        <?php foreach ($ayToplamlari as $kurus): ?>
                            <td class="text-end"><?= finans_e(FinansTutar::bicimle($kurus, false)) ?></td>
                        <?php endforeach; ?>
                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($yilToplami)) ?></td>
                    </tr>
                </tfoot>
            </table>
        <?php endif; ?>
    </div>
</div>

<script>
(function () {
    var uye = document.getElementById('fa-uye');
    var ay = document.getElementById('fa-ay');
    if (!uye || !ay) { return; }
    document.querySelectorAll('[data-fa-uye]').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
            uye.value = dugme.getAttribute('data-fa-uye');
            ay.value = dugme.getAttribute('data-fa-ay');
            document.getElementById('fa-odeme-karti').scrollIntoView({ behavior: 'smooth', block: 'center' });
            document.getElementById('fa-tutar').focus();
        });
    });
})();
</script>

<?php
$tabloTuru = FinansDeposu::TUR_AIDAT;
include __DIR__ . '/_tablo.php';
