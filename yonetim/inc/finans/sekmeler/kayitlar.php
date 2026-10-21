<?php

declare(strict_types=1);

/**
 * Bağış / Gider Kayıtları sekmesi: giriş formu + kayıt listesi.
 *
 * @var FinansDeposu $finansDepo
 * @var string       $kayitTuru  bagis | gider
 * @var string       $aktifSekme
 * @var int          $yil
 * @var int[]        $yillar
 * @var bool         $kayitGirebilir
 */

$bagisMi   = $kayitTuru === FinansDeposu::TUR_BAGIS;
$etiketler = $bagisMi
    ? ['baslik' => 'Yeni Bağış Kaydı', 'taraf' => 'Firma Adı', 'tutar' => 'Bağış Miktarı (TL)', 'islem' => 'bagis_kaydet', 'ikon' => 'fa-hand-holding-heart']
    : ['baslik' => 'Yeni Gider Kaydı', 'taraf' => 'Ödenen Yer / Firma', 'tutar' => 'Gider Miktarı (TL)', 'islem' => 'gider_kaydet', 'ikon' => 'fa-receipt'];
?>

<div class="d-flex justify-content-end mb-3"><?= finans_yil_secici($aktifSekme, $yil, $yillar) ?></div>

<?php if ($kayitGirebilir): ?>
    <div class="card border-0 shadow-sm rounded-4">
        <div class="card-header bg-white border-0 pt-3">
            <h5 class="fw-bold mb-0"><i class="fa-solid <?= $etiketler['ikon'] ?> me-2"></i><?= finans_e($etiketler['baslik']) ?></h5>
            <p class="small text-muted mb-0">Kayıt anında kasa toplamına <?= $bagisMi ? 'eklenir' : 'düşülür' ?>.</p>
        </div>
        <div class="card-body">
            <form method="post" action="index.php?sayfa=finans" class="row g-3" autocomplete="off">
                <?= csrf_hidden_alan() ?>
                <input type="hidden" name="islem" value="<?= $etiketler['islem'] ?>">
                <input type="hidden" name="yil" value="<?= $yil ?>">
                <div class="col-md-2">
                    <label for="fk-tarih" class="form-label small fw-semibold">Tarih</label>
                    <input type="date" id="fk-tarih" name="tarih" class="form-control" value="<?= date('Y-m-d') ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="fk-taraf" class="form-label small fw-semibold"><?= finans_e($etiketler['taraf']) ?></label>
                    <input type="text" id="fk-taraf" name="taraf" class="form-control" maxlength="<?= FinansServisi::AZAMI_TARAF ?>" required>
                </div>
                <div class="col-md-3">
                    <label for="fk-aciklama" class="form-label small fw-semibold">Açıklama</label>
                    <input type="text" id="fk-aciklama" name="aciklama" class="form-control" maxlength="<?= FinansServisi::AZAMI_ACIKLAMA ?>">
                </div>
                <div class="col-md-2">
                    <label for="fk-tutar" class="form-label small fw-semibold"><?= finans_e($etiketler['tutar']) ?></label>
                    <input type="text" id="fk-tutar" name="tutar" class="form-control" inputmode="decimal" placeholder="1.500,00" required>
                </div>
                <div class="col-md-2 d-flex align-items-end">
                    <button type="submit" class="btn btn-dark w-100"><i class="fa-solid fa-floppy-disk me-1"></i>Kaydet</button>
                </div>
            </form>
        </div>
    </div>
<?php endif; ?>

<?php
$tabloTuru = $kayitTuru;
include __DIR__ . '/_tablo.php';
