<?php

declare(strict_types=1);

/**
 * Ortak kayıt listesi (aidat / bağış / gider): sayfalı, iptal edilenleri isteğe bağlı gösterir.
 *
 * @var FinansDeposu $finansDepo
 * @var string       $tabloTuru     aidat | bagis | gider
 * @var string       $aktifSekme
 * @var int          $yil
 * @var bool         $kayitGirebilir
 */

$sayfaNo     = max(1, (int) ($_GET['s'] ?? 1));
$iptalDahil  = ($_GET['iptal'] ?? '') === '1';
$toplamKayit = $finansDepo->hareketSayisi($tabloTuru, $yil, $iptalDahil);
$toplamSayfa = max(1, (int) ceil($toplamKayit / FINANS_SAYFA_BOYUTU));
$sayfaNo     = min($sayfaNo, $toplamSayfa);
$satirlar    = $finansDepo->hareketler($tabloTuru, $yil, $iptalDahil, FINANS_SAYFA_BOYUTU, ($sayfaNo - 1) * FINANS_SAYFA_BOYUTU);

$aidatMi    = $tabloTuru === FinansDeposu::TUR_AIDAT;
$tarafBasligi = $aidatMi ? 'Kişi' : ($tabloTuru === FinansDeposu::TUR_BAGIS ? 'Firma' : 'Ödenen Yer / Firma');
$sayfaUrl = static fn (int $s): string => finans_url(['sekme' => $aktifSekme, 'yil' => $yil, 's' => $s] + ($iptalDahil ? ['iptal' => 1] : []));
?>

<div class="card border-0 shadow-sm rounded-4 mt-4">
    <div class="card-header bg-white border-0 pt-3 d-flex justify-content-between align-items-center flex-wrap gap-2">
        <h5 class="fw-bold mb-0"><?= $yil ?> Kayıtları <span class="badge bg-secondary-subtle text-secondary-emphasis ms-1"><?= number_format($toplamKayit, 0, ',', '.') ?></span></h5>
        <div class="d-flex align-items-center gap-3 flex-wrap">
            <a class="small" href="<?= finans_e(finans_url(['sekme' => $aktifSekme, 'yil' => $yil] + ($iptalDahil ? [] : ['iptal' => 1]))) ?>">
                <?= $iptalDahil ? 'İptal edilenleri gizle' : 'İptal edilenleri göster' ?>
            </a>
            <a class="btn btn-sm btn-outline-success" href="<?= finans_e('index.php?' . http_build_query(['sayfa' => 'finans-disa-aktar', 'tur' => $tabloTuru, 'yil' => $yil])) ?>">
                <i class="fa-solid fa-file-csv me-1"></i>CSV indir
            </a>
        </div>
    </div>

    <?php if ($satirlar === []): ?>
        <div class="card-body text-center text-muted py-5">
            <i class="fa-regular fa-folder-open fs-2 d-block mb-2"></i>Bu yıl için henüz kayıt yok.
        </div>
    <?php else: ?>
        <div class="table-responsive">
            <table class="table table-hover align-middle mb-0">
                <caption class="visually-hidden"><?= finans_e($tarafBasligi) ?> kayıt listesi</caption>
                <thead class="table-light">
                    <tr>
                        <th scope="col">Tarih</th>
                        <?php if ($aidatMi): ?><th scope="col">Dönem</th><?php endif; ?>
                        <th scope="col"><?= finans_e($tarafBasligi) ?></th>
                        <th scope="col">Açıklama</th>
                        <th scope="col" class="text-end">Tutar</th>
                        <th scope="col">Kaydeden</th>
                        <th scope="col" class="text-end">İşlem</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($satirlar as $h): ?>
                        <?php $iptal = $h['durum'] === 'iptal'; ?>
                        <tr class="<?= $iptal ? 'text-muted' : '' ?>">
                            <td class="text-nowrap <?= $iptal ? 'text-decoration-line-through' : '' ?>"><?= finans_e(date('d.m.Y', strtotime((string) $h['tarih']))) ?></td>
                            <?php if ($aidatMi): ?>
                                <td class="text-nowrap"><?= finans_e(FinansDonem::AY_ADLARI[(int) $h['donem_ay']] . ' ' . $h['donem_yil']) ?></td>
                            <?php endif; ?>
                            <td class="fw-semibold <?= $iptal ? 'text-decoration-line-through' : '' ?>"><?= finans_e($aidatMi ? $h['aidat_uye_adi'] : $h['taraf']) ?></td>
                            <td class="small"><?= finans_e($h['aciklama']) ?>
                                <?php if ($iptal): ?>
                                    <div class="text-danger">İptal: <?= finans_e($h['iptal_nedeni']) ?> <span class="text-muted">(<?= finans_e($h['iptal_eden']) ?>, <?= finans_e(date('d.m.Y H:i', strtotime((string) $h['iptal_tarihi']))) ?>)</span></div>
                                <?php endif; ?>
                            </td>
                            <td class="text-end fw-semibold text-nowrap <?= $iptal ? 'text-decoration-line-through' : '' ?>"><?= finans_e(FinansTutar::bicimle((int) $h['tutar_kurus'])) ?></td>
                            <td class="small text-muted"><?= finans_e($h['olusturan']) ?><?= $h['kaynak'] === 'excel' ? ' <span class="badge bg-light text-secondary border">Excel</span>' : '' ?></td>
                            <td class="text-end">
                                <?php if ($iptal): ?>
                                    <span class="badge bg-danger-subtle text-danger-emphasis">İptal</span>
                                <?php elseif ($kayitGirebilir): ?>
                                    <button type="button" class="btn btn-sm btn-outline-danger" data-finans-iptal="<?= (int) $h['id'] ?>">
                                        <i class="fa-solid fa-ban me-1"></i>İptal
                                    </button>
                                <?php endif; ?>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </div>
        <?php if ($toplamSayfa > 1): ?>
            <nav class="p-3" aria-label="Sayfalama">
                <ul class="pagination pagination-sm mb-0 justify-content-center flex-wrap">
                    <?php for ($s = 1; $s <= $toplamSayfa; $s++): ?>
                        <li class="page-item <?= $s === $sayfaNo ? 'active' : '' ?>">
                            <a class="page-link" href="<?= finans_e($sayfaUrl($s)) ?>"><?= $s ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>
        <?php endif; ?>
    <?php endif; ?>
</div>
