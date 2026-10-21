<?php

declare(strict_types=1);

/**
 * Excel Aktarımı sekmesi: Google Sheet sayfalarının CSV'lerini yükle → önizle → onayla.
 *
 * Önizleme veritabanına yazmaz; onaydan sonra tek işlemde (transaction) yazılır ve işlem
 * tekrarlandığında aynı kayıtlar çoğaltılmaz (idempotent).
 *
 * @var int $yil
 */

$onizleme = $_SESSION['finans_aktarim'] ?? null;
$onizleme = is_array($onizleme) ? $onizleme : null;

$sayfalar = [
    'dosya_aidat' => 'Aidat',
    'dosya_bagis' => 'Bağış Kayıtları',
    'dosya_gider' => 'Gider Kayıtları',
];
?>

<div class="card border-0 shadow-sm rounded-4 mb-4">
    <div class="card-header bg-white border-0 pt-3">
        <h5 class="fw-bold mb-0"><i class="fa-solid fa-file-import me-2"></i>1) Excel / Google Sheet Dosyalarını Yükle</h5>
        <p class="small text-muted mb-0">
            Sheet'te ilgili sayfayı açın → <strong>Dosya → İndir → Virgülle ayrılmış değerler (.csv)</strong>. En az bir dosya yeterlidir.
            "Aylık Özet" sayfaları kayıtlardan hesaplandığı için ayrıca yüklenmez. Bu adımda veritabanına hiçbir şey yazılmaz.
        </p>
    </div>
    <div class="card-body">
        <form method="post" action="index.php?sayfa=finans" enctype="multipart/form-data" class="row g-3">
            <?= csrf_hidden_alan() ?>
            <input type="hidden" name="islem" value="aktar_onizle">
            <input type="hidden" name="yil" value="<?= $yil ?>">
            <?php foreach ($sayfalar as $alan => $etiket): ?>
                <div class="col-md-4">
                    <label for="ak-<?= $alan ?>" class="form-label small fw-semibold"><?= finans_e($etiket) ?> (.csv)</label>
                    <input type="file" id="ak-<?= $alan ?>" name="<?= $alan ?>" class="form-control" accept=".csv,.txt,text/csv">
                </div>
            <?php endforeach; ?>
            <div class="col-12">
                <button type="submit" class="btn btn-dark"><i class="fa-solid fa-magnifying-glass me-1"></i>Önizle</button>
            </div>
        </form>
    </div>
</div>

<?php if ($onizleme === null): ?>
    <?php return; ?>
<?php endif; ?>

<?php
$aidat = $onizleme['aidat'];
$bagis = $onizleme['bagis'];
$gider = $onizleme['gider'];
?>

<form method="post" action="index.php?sayfa=finans" id="finans-aktarim-formu">
    <?= csrf_hidden_alan() ?>
    <input type="hidden" name="islem" value="aktar_onayla">
    <input type="hidden" name="yil" value="<?= $yil ?>">
    <input type="hidden" name="jeton" value="<?= finans_e($onizleme['jeton']) ?>">

    <h5 class="fw-bold mb-3"><i class="fa-solid fa-clipboard-check me-2"></i>2) Önizleme ve Onay</h5>

    <?php if (is_array($aidat)):
        $odemeToplam = array_sum(array_column($aidat['odemeler'], 'kurus'));
    ?>
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0">Aidat</h6></div>
            <div class="card-body">
                <p class="mb-2">
                    <strong><?= count($aidat['uyeler']) ?></strong> kişi,
                    <strong><?= count($aidat['odemeler']) ?></strong> ödeme,
                    toplam <strong><?= finans_e(FinansTutar::bicimle((int) $odemeToplam)) ?></strong>.
                </p>

                <?php foreach ($aidat['uyarilar'] as $uyari): ?>
                    <div class="alert alert-warning py-2 small mb-2"><?= finans_e($uyari) ?></div>
                <?php endforeach; ?>

                <?php if ($aidat['farklar'] !== []): ?>
                    <div class="alert alert-warning small">
                        <strong>Sayfadaki "AYLIK TOPLAM" ile kişi satırlarının toplamı uyuşmayan aylar var.</strong>
                        Hiçbir tutar kendiliğinden eklenmez. Sayfadaki toplam daha yüksekse ve fark gerçek bir giriş ise,
                        "Devir" olarak kasaya eklemek için işaretleyebilirsiniz.
                    </div>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <caption class="visually-hidden">Aylık toplam farkları</caption>
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Dönem</th>
                                    <th scope="col" class="text-end">Sayfadaki Toplam</th>
                                    <th scope="col" class="text-end">Kişi Satırları Toplamı</th>
                                    <th scope="col" class="text-end">Fark</th>
                                    <th scope="col">Devir olarak ekle</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($aidat['farklar'] as $fark):
                                    $anahtar = $fark['yil'] . '-' . $fark['ay'];
                                ?>
                                    <tr>
                                        <td><?= finans_e(FinansDonem::AY_ADLARI[$fark['ay']] . ' ' . $fark['yil']) ?></td>
                                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($fark['sayfa'])) ?></td>
                                        <td class="text-end"><?= finans_e(FinansTutar::bicimle($fark['hesaplanan'])) ?></td>
                                        <td class="text-end fw-semibold"><?= finans_para_html($fark['fark']) ?></td>
                                        <td>
                                            <?php if ($fark['fark'] > 0): ?>
                                                <div class="form-check">
                                                    <input class="form-check-input" type="checkbox" name="devir_sec[]" id="devir-<?= finans_e($anahtar) ?>" value="<?= finans_e($anahtar) ?>">
                                                    <label class="form-check-label small" for="devir-<?= finans_e($anahtar) ?>">Ekle</label>
                                                </div>
                                            <?php else: ?>
                                                <span class="small text-muted">Kontrol edin (eklenemez)</span>
                                            <?php endif; ?>
                                        </td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endif; ?>

    <?php foreach (['bagis' => ['Bağış Kayıtları', $bagis], 'gider' => ['Gider Kayıtları', $gider]] as $tur => [$etiket, $veri]): ?>
        <?php if (!is_array($veri)) { continue; } ?>
        <div class="card border-0 shadow-sm rounded-4 mb-3">
            <div class="card-header bg-white border-0 pt-3"><h6 class="fw-bold mb-0"><?= finans_e($etiket) ?></h6></div>
            <div class="card-body">
                <?php foreach ($veri['uyarilar'] as $uyari): ?>
                    <div class="alert alert-warning py-2 small mb-2"><?= finans_e($uyari) ?></div>
                <?php endforeach; ?>
                <?php if ($veri['kayitlar'] === []): ?>
                    <p class="text-muted mb-0">Aktarılacak kayıt bulunamadı.</p>
                <?php else: ?>
                    <p class="small text-muted">Örnek/şablon satırlar olabilir; istemediklerinizin işaretini kaldırın.</p>
                    <div class="table-responsive">
                        <table class="table table-sm align-middle">
                            <caption class="visually-hidden"><?= finans_e($etiket) ?> önizleme listesi</caption>
                            <thead class="table-light">
                                <tr>
                                    <th scope="col">Aktar</th>
                                    <th scope="col">Satır</th>
                                    <th scope="col">Tarih</th>
                                    <th scope="col">Firma / Yer</th>
                                    <th scope="col">Açıklama</th>
                                    <th scope="col" class="text-end">Tutar</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($veri['kayitlar'] as $indeks => $kayit): ?>
                                    <tr>
                                        <td>
                                            <input class="form-check-input" type="checkbox" name="<?= $tur ?>_sec[]" value="<?= (int) $indeks ?>"
                                                   id="<?= $tur ?>-<?= (int) $indeks ?>" checked aria-label="<?= finans_e($etiket) ?> satır <?= (int) $kayit['satir'] ?> aktar">
                                        </td>
                                        <td><?= (int) $kayit['satir'] ?></td>
                                        <td><?= finans_e(date('d.m.Y', strtotime((string) $kayit['tarih']))) ?></td>
                                        <td><?= finans_e($kayit['taraf']) ?></td>
                                        <td><?= finans_e($kayit['aciklama']) ?></td>
                                        <td class="text-end"><?= finans_e(FinansTutar::bicimle((int) $kayit['kurus'])) ?></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                <?php endif; ?>
            </div>
        </div>
    <?php endforeach; ?>

    <div class="alert alert-info small">
        Onayladığınızda tüm kayıtlar tek işlemde yazılır; bir hata olursa hiçbiri yazılmaz. Aynı dosyayı tekrar aktarırsanız mevcut kayıtlar çoğaltılmaz.
    </div>
    <button type="submit" class="btn btn-success"><i class="fa-solid fa-check me-1"></i>Onayla ve Veritabanına Aktar</button>
</form>
