<?php

declare(strict_types=1);

/**
 * Finans / Aidat sayfası (sekmeli): Kasa, Aidat, Bağış, Gider, Raporlar, Excel Aktarımı.
 *
 * Her sekme kendi yetkisiyle korunur; yetkisiz sekme adı istense bile ilk izinli sekmeye düşülür.
 * Kayıt/iptal/aktarım işlemleri POST ile inc/finans-islem.php'ye gider (CSRF korumalı).
 *
 * @var PDO $db_baglanti
 */

if (($_SESSION['oturum'] ?? false) !== true) {
    die('Yetkisiz erişim!');
}

require_once __DIR__ . '/finans/baslat.php';
require_once __DIR__ . '/finans/gorunum.php';

if (!FinansYetki::menuGorunurMu()) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Finans sayfası yetkili hesaplara açıktır.</div></div>';

    return;
}

const FINANS_SAYFA_BOYUTU = 50;

$finans       = finans_baslat($db_baglanti);
$finansServis = $finans['servis'];
$finansDepo   = $finansServis->depo();

$kasaGorebilir  = FinansYetki::oturumIcinVarMi(FinansYetki::KASA_GORUNTULE);
$aidatGorebilir = FinansYetki::oturumIcinVarMi(FinansYetki::AIDAT_GORUNTULE);
$kayitGirebilir = FinansYetki::oturumIcinVarMi(FinansYetki::KAYIT_GIR);
$aktarabilir    = FinansYetki::oturumIcinVarMi(FinansYetki::AKTARIM) && $aidatGorebilir && $kasaGorebilir;

/** @var array<string, array{0:string, 1:string, 2:bool}> sekme => [etiket, ikon, görünür] */
$sekmeler = [
    'kasa'     => ['Kasa', 'fa-vault', $kasaGorebilir],
    'aidat'    => ['Aidat', 'fa-table-cells', $aidatGorebilir],
    'bagis'    => ['Bağış Kayıtları', 'fa-hand-holding-heart', $kasaGorebilir],
    'gider'    => ['Gider Kayıtları', 'fa-receipt', $kasaGorebilir],
    'raporlar' => ['Raporlar', 'fa-chart-column', $kasaGorebilir],
    'aktar'    => ['Excel Aktarımı', 'fa-file-import', $aktarabilir],
];
$izinliSekmeler = array_keys(array_filter($sekmeler, static fn (array $s): bool => $s[2]));
$aktifSekme = in_array($_GET['sekme'] ?? '', $izinliSekmeler, true) ? (string) $_GET['sekme'] : $izinliSekmeler[0];

$bugun = new DateTimeImmutable('now');
$yil = (int) ($_GET['yil'] ?? $bugun->format('Y'));
$yil = FinansDonem::gecerliMi($yil, 1) ? $yil : (int) $bugun->format('Y');
$yillar = $finansDepo->yillar((int) $bugun->format('Y'));
if (!in_array($yil, $yillar, true)) {
    $yillar[] = $yil;
    sort($yillar);
}

$flash = $_SESSION['finans_flash'] ?? null;
unset($_SESSION['finans_flash']);
?>

<style>
    /* .table-responsive her zaman overflow açar ve iç içe kaydırma çubuğu üretir; geniş ekranda kapatıyoruz. */
    .finans-tablo-kap { overflow: visible; }
    @media (max-width: 991.98px) { .finans-tablo-kap { overflow-x: auto; -webkit-overflow-scrolling: touch; } }
    .finans-matris { font-size: .8rem; }
    .finans-matris th, .finans-matris td { padding: .3rem .4rem; }
    .finans-matris tbody th { white-space: nowrap; }
    .finans-matris td { white-space: nowrap; }
    @media (max-width: 991.98px) { .finans-matris { min-width: 860px; } }
    .finans-grafik { position: relative; height: 300px; }
</style>


<div class="container-fluid py-4 px-md-4">
    <div class="d-flex justify-content-between align-items-center mb-3 flex-wrap gap-3">
        <div>
            <h2 class="fw-bold text-dark mb-1"><i class="fa-solid fa-coins me-2" style="color:#e0a526;"></i>Finans / Aidat</h2>
            <p class="text-muted mb-0 small">Kasa, aidat, bağış ve gider kayıtları. Kayıtlar silinmez; hatalı kayıt iptal edilir.</p>
        </div>
    </div>

    <?php if (is_array($flash)): ?>
        <div class="alert alert-<?= finans_e($flash['tip']) ?> py-2" role="status"><?= finans_e($flash['mesaj']) ?></div>
    <?php endif; ?>

    <ul class="nav nav-tabs mb-4" role="tablist" aria-label="Finans sekmeleri">
        <?php foreach ($sekmeler as $anahtar => [$etiket, $ikon, $gorunur]): ?>
            <?php if ($gorunur): ?>
                <li class="nav-item" role="presentation">
                    <a class="nav-link fw-semibold <?= $anahtar === $aktifSekme ? 'active' : '' ?>"
                       href="<?= finans_e(finans_url(['sekme' => $anahtar, 'yil' => $yil])) ?>"
                       <?= $anahtar === $aktifSekme ? 'aria-current="page"' : '' ?>>
                        <i class="fa-solid <?= $ikon ?> me-1"></i><?= finans_e($etiket) ?>
                    </a>
                </li>
            <?php endif; ?>
        <?php endforeach; ?>
    </ul>

    <?php
    switch ($aktifSekme) {
        case 'aidat':
            include __DIR__ . '/finans/sekmeler/aidat.php';
            break;
        case 'bagis':
        case 'gider':
            $kayitTuru = $aktifSekme;
            include __DIR__ . '/finans/sekmeler/kayitlar.php';
            break;
        case 'raporlar':
            include __DIR__ . '/finans/sekmeler/raporlar.php';
            break;
        case 'aktar':
            include __DIR__ . '/finans/sekmeler/aktar.php';
            break;
        default:
            include __DIR__ . '/finans/sekmeler/kasa.php';
    }
    ?>
</div>

<dialog id="finans-iptal-dialog" style="border:0;border-radius:14px;padding:0;max-width:440px;width:92%;box-shadow:0 20px 60px rgba(0,0,0,.3);">
    <form method="post" action="index.php?sayfa=finans" class="p-4">
        <?= csrf_hidden_alan() ?>
        <input type="hidden" name="islem" value="iptal_et">
        <input type="hidden" name="yil" value="<?= $yil ?>">
        <input type="hidden" name="sekme" value="<?= finans_e($aktifSekme) ?>">
        <input type="hidden" name="hareket_id" id="finans-iptal-id" value="">
        <h5 class="fw-bold mb-1">Kaydı iptal et</h5>
        <p class="small text-muted">Kayıt silinmez; "iptal" olarak saklanır ve kasa toplamından düşer.</p>
        <label for="finans-iptal-neden" class="form-label small fw-semibold">İptal nedeni</label>
        <textarea id="finans-iptal-neden" name="neden" class="form-control" rows="3" minlength="3" maxlength="300" required></textarea>
        <div class="d-flex justify-content-end gap-2 mt-3">
            <button type="button" class="btn btn-light" id="finans-iptal-vazgec">Vazgeç</button>
            <button type="submit" class="btn btn-danger">İptal Et</button>
        </div>
    </form>
</dialog>

<script>
(function () {
    var dialog = document.getElementById('finans-iptal-dialog');
    if (!dialog) { return; }
    document.querySelectorAll('[data-finans-iptal]').forEach(function (dugme) {
        dugme.addEventListener('click', function () {
            document.getElementById('finans-iptal-id').value = dugme.getAttribute('data-finans-iptal');
            document.getElementById('finans-iptal-neden').value = '';
            dialog.showModal();
        });
    });
    document.getElementById('finans-iptal-vazgec').addEventListener('click', function () { dialog.close(); });
})();
</script>
