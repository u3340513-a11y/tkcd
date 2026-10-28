<?php
/**
 * İlçe başkanı dashboard'u — "Son Eklenen Üyeler" ve "Kan Grubu Dağılımı" kolonları
 * (yönetim dashboard'undaki dash-card görünümü, tüm üyeler üzerinden).
 *
 * @var array $genel_veri  genel_dashboard_verileri() çıktısı
 * @var array $avatar_renkleri
 */
$kan_toplam = array_sum($genel_veri['kan_sayilar']);
?>
<!-- SOL: Son Eklenen Üyeler (tüm üyeler) -->
<div class="col-lg-4">
    <div class="dash-card h-100">
        <div class="dash-card__header">
            <h5 class="dash-card__title"><i class="fa-solid fa-user-plus text-primary"></i> Son Eklenen Üyeler</h5>
            <span class="dash-card__action">Tüm Üyeler</span>
        </div>
        <div class="dash-card__body">
            <?php if (!empty($genel_veri['son_uyeler'])): ?>
                <?php foreach ($genel_veri['son_uyeler'] as $uye): ?>
                    <div class="dash-member-item">
                        <div class="dash-member-item__avatar" style="background: <?= avatarRengi($uye['adi_soyadi'], $avatar_renkleri) ?>">
                            <?= htmlspecialchars(basHarfleri($uye['adi_soyadi'])) ?>
                        </div>
                        <div class="dash-member-item__info">
                            <div class="dash-member-item__name"><?= htmlspecialchars($uye['adi_soyadi']) ?></div>
                            <div class="dash-member-item__meta"><?= htmlspecialchars($uye['kurum'] ?: ($uye['gorev_unvan'] ?: '—')) ?></div>
                        </div>
                        <span class="dash-member-item__date">
                            <?= (!empty($uye['uyelik_tarihi']) && $uye['uyelik_tarihi'] !== '0000-00-00') ? date('d.m.Y', strtotime($uye['uyelik_tarihi'])) : '—' ?>
                        </span>
                    </div>
                <?php endforeach; ?>
            <?php else: ?>
                <p class="text-muted small text-center py-3">Henüz üye kaydı bulunamadı.</p>
            <?php endif; ?>
        </div>
    </div>
</div>

<!-- ORTA: Kan Grubu Dağılımı (tüm üyeler) -->
<div class="col-lg-4">
    <div class="dash-card h-100">
        <div class="dash-card__header">
            <h5 class="dash-card__title"><i class="fa-solid fa-droplet text-danger"></i> Kan Grubu Dağılımı</h5>
            <span class="dash-card__action">Tüm Üyeler</span>
        </div>
        <div class="dash-card__body text-center">
            <?php if (!empty($genel_veri['kan_etiketler'])): ?>
                <div style="position:relative; height:200px; max-height:200px;">
                    <canvas id="genelKanGrubuGrafik"></canvas>
                </div>
                <div class="mt-3 d-flex flex-wrap justify-content-center gap-2">
                    <?php foreach ($genel_veri['kan_etiketler'] as $i => $etiket): ?>
                        <span class="badge bg-light text-dark border px-2 py-1" style="font-size:0.7rem;">
                            <?= htmlspecialchars($etiket) ?>: %<?= $kan_toplam > 0 ? round(($genel_veri['kan_sayilar'][$i] / $kan_toplam) * 100) : 0 ?>
                        </span>
                    <?php endforeach; ?>
                </div>
            <?php else: ?>
                <p class="text-muted small py-4">Kan grubu verisi bulunamadı.</p>
            <?php endif; ?>

            <?php if (!empty($genel_veri['calisma_etiketler'])): ?>
                <hr class="my-4">
                <h6 class="fw-bold text-start mb-3" style="font-size:.85rem;color:#475569;">
                    <i class="fa-solid fa-briefcase me-1" style="color:#6366f1;"></i> Çalışma Şekli Dağılımı
                </h6>
                <div style="position:relative; height:<?= max(120, count($genel_veri['calisma_etiketler']) * 44) ?>px;">
                    <canvas id="genelCalismaSekliGrafik"></canvas>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>
