<?php

/**
 * Kurum Birleştirme Aracı
 *
 * Geliştirici rolüne özel bir araç. Veritabanındaki serbest metin
 * `kurum` alanında farklı yazımlardan oluşan aynı kurumlari tespit eder
 * ve toplu olarak tek bir isim altında birleştirir.
 *
 * Güvenlik:
 *   - Yalnızca geliştirici rolü erişebilir
 *   - Prepared statement ile SQL injection koruması
 *   - CSRF koruması (session tabanlı token)
 *   - htmlspecialchars ile XSS koruması
 *   - İşlem sonrası log kaydı
 */

// Yetki kontrolü (sadece geliştirici)
if (!$is_gelistirici) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold"><i class="fa-solid fa-lock me-2"></i>Erişim Engellendi: Bu sayfa sadece Geliştirici hesabına açıktır.</div></div>';
    return;
}

// CSRF token oluştur
if (empty($_SESSION['csrf_kurum_birlestir'])) {
    $_SESSION['csrf_kurum_birlestir'] = bin2hex(random_bytes(32));
}
$csrf_token = $_SESSION['csrf_kurum_birlestir'];

$islem_mesaji = '';
$islem_tipi   = '';

// ─── BİRLEŞTİRME İŞLEMİ ────────────────────────────────────────────
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['kurum_birlestir'])) {
    // CSRF doğrula
    if (!hash_equals($csrf_token, $_POST['csrf_token'] ?? '')) {
        $islem_mesaji = 'Güvenlik doğrulaması başarısız. Sayfayı yenileyin.';
        $islem_tipi   = 'danger';
    } else {
        $yeni_isim = trim($_POST['yeni_kurum'] ?? '');

        // Tekli veya çoklu seçim desteği
        $eski_isimler = [];
        if (!empty($_POST['eski_kurumlar']) && is_array($_POST['eski_kurumlar'])) {
            foreach ($_POST['eski_kurumlar'] as $ek) {
                $temiz = trim($ek);
                if ($temiz !== '' && $temiz !== $yeni_isim) {
                    $eski_isimler[] = $temiz;
                }
            }
        } elseif (!empty($_POST['eski_kurum'])) {
            $tek = trim($_POST['eski_kurum']);
            if ($tek !== '' && $tek !== $yeni_isim) {
                $eski_isimler[] = $tek;
            }
        }

        if (empty($eski_isimler) || empty($yeni_isim)) {
            $islem_mesaji = 'En az bir eski kurum adı seçin ve yeni kurum adını girin.';
            $islem_tipi   = 'danger';
        } else {
            try {
                $db_baglanti->beginTransaction();

                $toplam_etkilenen = 0;
                $guncellenen_isimler = [];
                $guncelle = $db_baglanti->prepare(
                    "UPDATE dernek_uyeler SET kurum = ? WHERE kurum = ?"
                );

                foreach ($eski_isimler as $eski) {
                    $guncelle->execute([$yeni_isim, $eski]);
                    $satir = $guncelle->rowCount();
                    if ($satir > 0) {
                        $toplam_etkilenen += $satir;
                        $guncellenen_isimler[] = '"' . $eski . '" (' . $satir . ')';
                    }
                }

                $db_baglanti->commit();

                if ($toplam_etkilenen > 0) {
                    $log_detay = implode(', ', $guncellenen_isimler) . ' → "' . $yeni_isim . '"';
                    log_kaydet(
                        $db_baglanti,
                        'kurum_birlestir',
                        $log_detay . ' (Toplam ' . $toplam_etkilenen . ' üye güncellendi)',
                        'dernek_uyeler'
                    );
                    $islem_mesaji = '<strong>' . $toplam_etkilenen . '</strong> üyenin kurum adı "<strong>' . htmlspecialchars($yeni_isim) . '</strong>" olarak güncellendi.<br><small class="text-muted">Değişen: ' . htmlspecialchars(implode(', ', $guncellenen_isimler)) . '</small>';
                    $islem_tipi   = 'success';
                } else {
                    $islem_mesaji = 'Seçilen kurum adlarıyla kayıtlı üye bulunamadı.';
                    $islem_tipi   = 'warning';
                }

                // Token yenile
                $_SESSION['csrf_kurum_birlestir'] = bin2hex(random_bytes(32));
                $csrf_token = $_SESSION['csrf_kurum_birlestir'];
            } catch (\PDOException $e) {
                if ($db_baglanti->inTransaction()) {
                    $db_baglanti->rollBack();
                }
                error_log('Kurum birleştirme hatası: ' . $e->getMessage());
                $islem_mesaji = 'Veritabanı hatası oluştu. Lütfen tekrar deneyin.';
                $islem_tipi   = 'danger';
            }
        }
    }
}

// ─── KURUM VERİLERİNİ ÇEK ───────────────────────────────────────────
try {
    // Tüm kurumlar ve üye sayıları
    $kurum_sorgu = $db_baglanti->query(
        "SELECT kurum, COUNT(*) as adet
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND kurum IS NOT NULL AND kurum != ''
          GROUP BY kurum
          ORDER BY kurum ASC"
    );
    $tum_kurumlar = $kurum_sorgu->fetchAll(PDO::FETCH_ASSOC);

    // Olası benzerlikleri tespit et (Levenshtein yaklaşımı PHP tarafında)
    $benzer_gruplar = [];
    $islenmis = [];

    foreach ($tum_kurumlar as $i => $k1) {
        if (in_array($i, $islenmis, true)) continue;
        $grup = [$k1];

        foreach ($tum_kurumlar as $j => $k2) {
            if ($i === $j || in_array($j, $islenmis, true)) continue;

            $ad1 = mb_strtolower(trim($k1['kurum']), 'UTF-8');
            $ad2 = mb_strtolower(trim($k2['kurum']), 'UTF-8');

            // Noktalama ve boşluk temizlenmiş hali karşılaştır
            $temiz1 = preg_replace('/[\s\.\,\-\_]+/u', '', $ad1);
            $temiz2 = preg_replace('/[\s\.\,\-\_]+/u', '', $ad2);

            $benzer = false;

            // Tam eşleşme (normalize edilmiş)
            if ($temiz1 === $temiz2) {
                $benzer = true;
            }
            // Birisi diğerinin içinde
            elseif (mb_strlen($temiz1, 'UTF-8') >= 4 && mb_strlen($temiz2, 'UTF-8') >= 4) {
                if (mb_strpos($temiz1, $temiz2, 0, 'UTF-8') !== false || mb_strpos($temiz2, $temiz1, 0, 'UTF-8') !== false) {
                    $benzer = true;
                }
                // Levenshtein (sadece kısa isimler için, performans nedeniyle)
                elseif (mb_strlen($ad1, 'UTF-8') <= 30 && mb_strlen($ad2, 'UTF-8') <= 30) {
                    $mesafe = levenshtein($ad1, $ad2);
                    $max_uzunluk = max(mb_strlen($ad1, 'UTF-8'), mb_strlen($ad2, 'UTF-8'));
                    if ($max_uzunluk > 0 && ($mesafe / $max_uzunluk) < 0.3) {
                        $benzer = true;
                    }
                }
            }

            if ($benzer) {
                $grup[] = $k2;
                $islenmis[] = $j;
            }
        }

        if (count($grup) > 1) {
            $islenmis[] = $i;
            $benzer_gruplar[] = $grup;
        }
    }
} catch (\PDOException $e) {
    error_log('Kurum listesi hatası: ' . $e->getMessage());
    $tum_kurumlar = [];
    $benzer_gruplar = [];
}
?>

<div class="container-fluid py-4 px-md-4">

    <!-- Başlık -->
    <div class="row mb-4">
        <div class="col-12">
            <div class="d-flex flex-column flex-md-row align-items-md-center justify-content-between gap-2">
                <div>
                    <h2 class="fw-bold text-dark mb-1">
                        <i class="fa-solid fa-code-merge me-2" style="color:#00838f;"></i>Kurum Birleştirme Aracı
                    </h2>
                    <p class="text-muted mb-0">Farklı yazılmış kurum adlarını tek bir isim altında birleştirin.</p>
                </div>
                <a href="index.php" class="btn btn-outline-secondary btn-sm fw-bold px-3">
                    <i class="fa-solid fa-arrow-left me-1"></i>Dashboard'a Dön
                </a>
            </div>
        </div>
    </div>

    <!-- İşlem Mesajı -->
    <?php if ($islem_mesaji): ?>
        <div class="row mb-4">
            <div class="col-12">
                <div class="alert alert-<?= $islem_tipi; ?> alert-dismissible fade show shadow-sm border-0" role="alert">
                    <i class="fa-solid fa-<?= $islem_tipi === 'success' ? 'circle-check' : ($islem_tipi === 'warning' ? 'triangle-exclamation' : 'circle-xmark'); ?> me-2"></i>
                    <?= $islem_mesaji; ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
            </div>
        </div>
    <?php endif; ?>

    <div class="row g-4">

        <!-- SOL: Benzer Kurumlar (Otomatik Tespit) -->
        <div class="col-lg-7">
            <div class="rounded-4 shadow-sm overflow-hidden" style="background: linear-gradient(135deg, #1a1a2e 0%, #16213e 50%, #0f3460 100%); border: 1px solid rgba(255,255,255,0.08);">
                <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3" style="border-bottom: 1px solid rgba(255,255,255,0.08);">
                    <div class="d-flex align-items-center gap-3">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(255,111,0,0.2);border:1px solid rgba(255,111,0,0.35);">
                            <i class="fa-solid fa-triangle-exclamation" style="color:#ff6f00;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#fff;letter-spacing:-0.02em;">Benzer Kurum Adları</h5>
                            <p class="mb-0 small" style="color:rgba(255,255,255,0.45);">Otomatik tespit — aynı kurum olabilecek farklı yazımlar</p>
                        </div>
                    </div>
                    <span class="badge rounded-pill px-3 py-2" style="background:rgba(255,111,0,0.18);color:#ff6f00;font-size:0.75rem;font-weight:600;">
                        <?= count($benzer_gruplar); ?> Olası Eşleşme
                    </span>
                </div>

                <div class="p-4" style="max-height:600px;overflow-y:auto;">
                    <?php if (count($benzer_gruplar) > 0): ?>
                        <?php foreach ($benzer_gruplar as $gi => $grup): ?>
                            <div class="rounded-3 p-3 mb-3" style="background:rgba(255,255,255,0.04);border:1px solid rgba(255,255,255,0.08);">
                                <div class="d-flex align-items-center gap-2 mb-3">
                                    <span class="badge rounded-pill px-2 py-1" style="background:rgba(255,111,0,0.2);color:#ff6f00;font-size:0.7rem;">Grup <?= $gi + 1; ?></span>
                                    <span class="small" style="color:rgba(255,255,255,0.35);">Bu kurumlar aynı olabilir</span>
                                </div>
                                <?php foreach ($grup as $gk): ?>
                                    <div class="d-flex align-items-center justify-content-between py-2 px-3 rounded-2 mb-2" style="background:rgba(255,255,255,0.03);border:1px solid rgba(255,255,255,0.06);">
                                        <div class="d-flex align-items-center gap-2">
                                            <i class="fa-solid fa-building small" style="color:rgba(255,255,255,0.25);"></i>
                                            <span class="fw-semibold" style="color:#fff;font-size:0.88rem;"><?= htmlspecialchars($gk['kurum']); ?></span>
                                        </div>
                                        <span class="badge rounded-pill px-2 py-1" style="background:rgba(74,144,217,0.15);color:#4a90d9;font-size:0.75rem;"><?= $gk['adet']; ?> üye</span>
                                    </div>
                                <?php endforeach; ?>
                                <form method="POST" class="mt-3 d-flex gap-2 align-items-end">
                                    <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">
                                    <div class="flex-grow-1">
                                        <label class="form-label small fw-bold mb-1" style="color:rgba(255,255,255,0.5);font-size:0.7rem;">ESKİ (Bu silinecek)</label>
                                        <select name="eski_kurum" class="form-select form-select-sm" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:#fff;font-size:0.85rem;" required>
                                            <?php foreach ($grup as $gk): ?>
                                                <option value="<?= htmlspecialchars($gk['kurum']); ?>"><?= htmlspecialchars($gk['kurum']); ?> (<?= $gk['adet']; ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <div class="px-2" style="color:rgba(255,255,255,0.25);"><i class="fa-solid fa-arrow-right"></i></div>
                                    <div class="flex-grow-1">
                                        <label class="form-label small fw-bold mb-1" style="color:rgba(255,255,255,0.5);font-size:0.7rem;">YENİ (Buna dönüşecek)</label>
                                        <select name="yeni_kurum" class="form-select form-select-sm" style="background:rgba(255,255,255,0.07);border:1px solid rgba(255,255,255,0.12);color:#fff;font-size:0.85rem;" required>
                                            <?php foreach ($grup as $gk): ?>
                                                <option value="<?= htmlspecialchars($gk['kurum']); ?>"><?= htmlspecialchars($gk['kurum']); ?> (<?= $gk['adet']; ?>)</option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                    <button type="submit" name="kurum_birlestir" class="btn btn-sm px-3 py-2 fw-bold flex-shrink-0" style="background:rgba(255,111,0,0.15);color:#ff6f00;border:1px solid rgba(255,111,0,0.3);transition:all 0.2s;" onmouseover="this.style.background='rgba(255,111,0,0.3)'" onmouseout="this.style.background='rgba(255,111,0,0.15)'">
                                        <i class="fa-solid fa-code-merge me-1"></i>Birleştir
                                    </button>
                                </form>
                            </div>
                        <?php endforeach; ?>
                    <?php else: ?>
                        <div class="text-center py-5" style="color:rgba(255,255,255,0.3);">
                            <i class="fa-solid fa-circle-check fa-3x d-block mb-3" style="color:rgba(80,200,120,0.4);"></i>
                            <p class="mb-0 fw-semibold" style="color:rgba(255,255,255,0.5);">Tebrikler! Benzer kurum adı bulunamadı.</p>
                            <p class="mb-0 small" style="color:rgba(255,255,255,0.3);">Tüm kurum isimleri tutarlı görünüyor.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>

        <!-- SAĞ: Manuel Birleştirme + Kurum Listesi -->
        <div class="col-lg-5">
            <div class="d-flex flex-column gap-4">

                <!-- Manuel Birleştirme Formu -->
                <div class="rounded-4 shadow-sm overflow-hidden" style="background:#fff;border:1px solid rgba(0,0,0,0.08);">
                    <div class="d-flex align-items-center gap-3 px-4 pt-4 pb-3" style="border-bottom:1px solid rgba(0,0,0,0.07);">
                        <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(0,131,143,0.12);border:1px solid rgba(0,131,143,0.3);">
                            <i class="fa-solid fa-pen-to-square" style="color:#00838f;font-size:1.1rem;"></i>
                        </div>
                        <div>
                            <h5 class="fw-bold mb-0" style="color:#1a1a2e;">Manuel Birleştirme</h5>
                            <p class="mb-0 small" style="color:rgba(0,0,0,0.45);">Birden fazla kurum seçip tek isme dönüştürün</p>
                        </div>
                    </div>
                    <script>
                    /* Türkçe küçük harf dönüşümü */
                    function trKucukHarf(s) {
                        return s.replace(/İ/g,'i').replace(/I/g,'ı').replace(/Ş/g,'ş').replace(/Ğ/g,'ğ').replace(/Ü/g,'ü').replace(/Ö/g,'ö').replace(/Ç/g,'ç').toLowerCase();
                    }

                    /* Anlık kurum filtreleme */
                    function kurumFiltrele(deger) {
                        var aranan = trKucukHarf(deger.trim());
                        var satirlar = document.querySelectorAll('#kurumCheckboxListesi .kurum-satir-item');
                        for (var i = 0; i < satirlar.length; i++) {
                            var ad = satirlar[i].getAttribute('data-ad') || '';
                            satirlar[i].style.display = (aranan === '' || ad.indexOf(aranan) !== -1) ? '' : 'none';
                        }
                    }

                    /* Seçim sayacı */
                    function kurumSecimGuncelle() {
                        var checkler = document.querySelectorAll('#kurumCheckboxListesi input[type=checkbox]');
                        var secili = 0;
                        for (var i = 0; i < checkler.length; i++) {
                            if (checkler[i].checked) secili++;
                            var satir = checkler[i].closest('.kurum-satir-item');
                            if (satir) satir.style.background = checkler[i].checked ? 'rgba(0,131,143,0.08)' : '';
                        }
                        var sayac = document.getElementById('secimSayaci');
                        if (sayac) sayac.textContent = secili + ' seçili';
                    }
                    </script>
                    <div class="p-4">
                        <form method="POST" id="manuelBirlestirForm">
                            <input type="hidden" name="csrf_token" value="<?= $csrf_token; ?>">

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Eski Kurum Adları <span class="text-danger">*</span></label>
                                <div class="mb-2">
                                    <input type="text" id="kurumAraFiltre" class="form-control form-control-sm" placeholder="🔍 Kurum ara..." autocomplete="off"
                                           oninput="kurumFiltrele(this.value)">
                                </div>
                                <div id="kurumCheckboxListesi" style="max-height:300px;overflow-y:auto;border:1px solid rgba(0,0,0,0.12);border-radius:8px;">
                                    <?php foreach ($tum_kurumlar as $tki => $tk): ?>
                                        <div class="d-flex align-items-center gap-2 px-3 py-2 kurum-satir-item" data-ad="<?= htmlspecialchars(mb_strtolower($tk['kurum'], 'UTF-8')); ?>" style="transition:background 0.15s;<?= ($tki < count($tum_kurumlar) - 1) ? 'border-bottom:1px solid rgba(0,0,0,0.06);' : ''; ?>">
                                            <input class="form-check-input mt-0 flex-shrink-0" type="checkbox" name="eski_kurumlar[]" value="<?= htmlspecialchars($tk['kurum']); ?>" id="kurum_<?= md5($tk['kurum']); ?>" style="width:18px;height:18px;min-width:18px;cursor:pointer;"
                                                   onchange="kurumSecimGuncelle()">
                                            <label class="d-flex justify-content-between align-items-center w-100" for="kurum_<?= md5($tk['kurum']); ?>" style="cursor:pointer;font-size:0.85rem;margin:0;">
                                                <span class="text-truncate me-2"><?= htmlspecialchars($tk['kurum']); ?></span>
                                                <span class="badge rounded-pill flex-shrink-0" style="background:rgba(13,110,253,0.1);color:#0d6efd;font-size:0.75rem;min-width:28px;"><?= $tk['adet']; ?></span>
                                            </label>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                                <div class="d-flex justify-content-between align-items-center mt-2">
                                    <div class="form-text mb-0">Seçilen kurum adları kaldırılacak.</div>
                                    <span class="badge bg-secondary bg-opacity-10 text-secondary" id="secimSayaci" style="font-size:0.75rem;">0 seçili</span>
                                </div>
                            </div>

                            <div class="mb-3">
                                <label class="form-label fw-semibold small">Yeni Kurum Adı <span class="text-danger">*</span></label>
                                <input type="text" name="yeni_kurum" class="form-control" placeholder="Doğru kurum adını yazın" required>
                                <div class="form-text">Tüm seçilen kurumların üyeleri bu isme güncellenecek.</div>
                            </div>

                            <button type="submit" name="kurum_birlestir" class="btn btn-sm w-100 fw-bold py-2" style="background:#00838f;color:#fff;border:none;">
                                <i class="fa-solid fa-code-merge me-1"></i>Seçilenleri Birleştir ve Güncelle
                            </button>
                        </form>
                    </div>
                </div>



                <!-- Tüm Kurumlar Listesi -->
                <div class="rounded-4 shadow-sm overflow-hidden" style="background:#fff;border:1px solid rgba(0,0,0,0.08);">
                    <div class="d-flex align-items-center justify-content-between px-4 pt-4 pb-3" style="border-bottom:1px solid rgba(0,0,0,0.07);">
                        <div class="d-flex align-items-center gap-3">
                            <div class="rounded-3 d-flex align-items-center justify-content-center" style="width:44px;height:44px;background:rgba(13,110,253,0.1);border:1px solid rgba(13,110,253,0.25);">
                                <i class="fa-solid fa-list" style="color:#0d6efd;font-size:1.1rem;"></i>
                            </div>
                            <div>
                                <h5 class="fw-bold mb-0" style="color:#1a1a2e;">Tüm Kurumlar</h5>
                            </div>
                        </div>
                        <span class="badge rounded-pill px-3 py-2" style="background:rgba(13,110,253,0.1);color:#0d6efd;font-size:0.75rem;font-weight:600;">
                            <?= count($tum_kurumlar); ?> Kurum
                        </span>
                    </div>
                    <div class="p-4" style="max-height:400px;overflow-y:auto;">
                        <?php if (count($tum_kurumlar) > 0): ?>
                            <table class="table table-sm table-hover mb-0" style="font-size:0.85rem;">
                                <thead class="table-light">
                                    <tr>
                                        <th class="fw-bold">#</th>
                                        <th class="fw-bold">Kurum Adı</th>
                                        <th class="fw-bold text-end">Üye</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tum_kurumlar as $ti => $tk): ?>
                                        <tr>
                                            <td class="text-muted"><?= $ti + 1; ?></td>
                                            <td><?= htmlspecialchars($tk['kurum']); ?></td>
                                            <td class="text-end">
                                                <span class="badge rounded-pill bg-primary bg-opacity-10 text-primary"><?= $tk['adet']; ?></span>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        <?php else: ?>
                            <p class="text-muted small text-center mb-0">Kurum verisi bulunamadı.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>

    </div>
</div>
