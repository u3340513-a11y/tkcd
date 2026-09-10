<?php
declare(strict_types=1);

/**
 * Cinsiyet Toplu Atama Sayfası — Sadece Geliştirici Rolüne Açıktır.
 *
 * Türkçe isim veritabanı kullanarak mevcut üyelerin cinsiyetini
 * otomatik tahmin eder. Geliştirici onay adımı sonrası toplu kaydeder.
 */

if (!$is_gelistirici) {
    echo '<div class="container py-5"><div class="alert alert-danger text-center fw-bold">
          <i class="fa-solid fa-lock me-2"></i>Erişim Engellendi.</div></div>';
    return;
}

// ─── CSRF ────────────────────────────────────────────────────────────────
$csrf = csrf_token_al();

// ─── TOPLU KAYDET İŞLEMİ ─────────────────────────────────────────────────
$kayit_mesaj = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['toplu_kaydet'])) {
    if (!csrf_token_dogrula($_POST['csrf_token'] ?? '')) {
        $kayit_mesaj = '<div class="alert alert-danger">Güvenlik hatası. Sayfayı yenileyip tekrar deneyin.</div>';
    } else {
        $guncellenenler = 0;
        $atlanlar       = 0;

        if (!empty($_POST['cinsiyet_data']) && is_array($_POST['cinsiyet_data'])) {
            try {
                $stmt = $db_baglanti->prepare(
                    "UPDATE dernek_uyeler SET cinsiyet = ? WHERE id = ? AND (cinsiyet IS NULL OR cinsiyet = '')"
                );
                foreach ($_POST['cinsiyet_data'] as $id => $cinsiyet) {
                    $id       = (int) $id;
                    $cinsiyet = in_array($cinsiyet, ['Erkek', 'Kadın'], true) ? $cinsiyet : null;
                    if ($id > 0 && $cinsiyet !== null) {
                        $stmt->execute([$cinsiyet, $id]);
                        if ($stmt->rowCount() > 0) {
                            $guncellenenler++;
                        } else {
                            $atlanlar++;
                        }
                    }
                }
                log_kaydet($db_baglanti, 'cinsiyet_toplu_guncelle',
                    "Cinsiyet toplu atama: {$guncellenenler} güncellendi, {$atlanlar} atlandı.");
                $kayit_mesaj = "<div class='alert alert-success fw-bold'>
                    <i class='fa-solid fa-check-circle me-2'></i>
                    <strong>{$guncellenenler}</strong> üyenin cinsiyeti başarıyla kaydedildi.
                    " . ($atlanlar > 0 ? " ({$atlanlar} zaten dolu veya değiştirilmedi.)" : '') . "
                </div>";
            } catch (\PDOException $e) {
                error_log('Cinsiyet toplu kayıt hatası: ' . $e->getMessage());
                $kayit_mesaj = '<div class="alert alert-danger">Kayıt sırasında bir hata oluştu.</div>';
            }
        }
    }
}

// ─── TÜRKÇE İSİM → CİNSİYET VERİTABANI ──────────────────────────────────
/**
 * Türkiye'de yaygın kullanılan isimlerden oluşan eşleştirme listesi.
 * Anahtar: lowercase isim, Değer: 'E' (Erkek) | 'K' (Kadın)
 *
 * Neden küçük harf: str_contains() ile harf duyarsız karşılaştırma için.
 */
$isim_db = [
    // ─ ERKEK ─────────────────────────────────────────────────────────────
    'ahmet'=>'E','mehmet'=>'E','mustafa'=>'E','ali'=>'E','hüseyin'=>'E',
    'ibrahim'=>'E','hasan'=>'E','ismail'=>'E','ömer'=>'E','murat'=>'E',
    'osman'=>'E','mahmut'=>'E','ramazan'=>'E','yusuf'=>'E','erdal'=>'E',
    'ercan'=>'E','erkan'=>'E','ergün'=>'E','erman'=>'E','sercan'=>'E',
    'serhat'=>'E','burak'=>'E','emre'=>'E','enes'=>'E','furkan'=>'E',
    'kemal'=>'E','kadir'=>'E','halil'=>'E','hamza'=>'E','bilal'=>'E',
    'salih'=>'E','selim'=>'E','selman'=>'E','sinan'=>'E','suat'=>'E',
    'onur'=>'E','orhan'=>'E','oktay'=>'E','nuri'=>'E','nihat'=>'E',
    'necdet'=>'E','nazım'=>'E','nevzat'=>'E','mücahit'=>'E','muhammet'=>'E',
    'muhammed'=>'E','mikail'=>'E','mevlüt'=>'E','mesut'=>'E','metin'=>'E',
    'müslüm'=>'E','münir'=>'E','muharrem'=>'E','necati'=>'E','niyazi'=>'E',
    'levent'=>'E','lütfi'=>'E','lütfü'=>'E','kürşat'=>'E','kürsad'=>'E',
    'koray'=>'E','köksal'=>'E','hikmet'=>'E','hayri'=>'E','haydar'=>'E',
    'harun'=>'E','hakan'=>'E','gökhan'=>'E','gökay'=>'E','gökalp'=>'E',
    'ferruh'=>'E','fikri'=>'E','fethi'=>'E','ferhat'=>'E','fuat'=>'E',
    'fahri'=>'E','faruk'=>'E','eyüp'=>'E','evren'=>'E','erol'=>'E',
    'emrah'=>'E','erman'=>'E','ekrem'=>'E','ekmel'=>'E','edip'=>'E',
    'dursun'=>'E','davut'=>'E','cüneyt'=>'E','cumhur'=>'E','cengiz'=>'E',
    'celal'=>'E','cemal'=>'E','cem'=>'E','bayram'=>'E','bülent'=>'E',
    'bora'=>'E','beytullah'=>'E','bekir'=>'E','batuhan'=>'E','barış'=>'E',
    'bahadır'=>'E','atilla'=>'E','atila'=>'E','asım'=>'E','arif'=>'E',
    'adem'=>'E','adnan'=>'E','tahsin'=>'E','tahir'=>'E','talat'=>'E',
    'tansel'=>'E','tayfun'=>'E','tayfur'=>'E','tekin'=>'E','tuncay'=>'E',
    'tunç'=>'E','turan'=>'E','ulaş'=>'E','umut'=>'E','uğur'=>'E',
    'ünal'=>'E','ümit'=>'E','volkan'=>'E','veli'=>'E','vedat'=>'E',
    'yaşar'=>'E','yıldıray'=>'E','yiğit'=>'E','zafer'=>'E','zeki'=>'E',
    'şükrü'=>'E','şevket'=>'E','şeref'=>'E','şenol'=>'E','şaban'=>'E',
    'resul'=>'E','rafet'=>'E','recep'=>'E','rıdvan'=>'E','rıfat'=>'E',
    'ömer'=>'E','özcan'=>'E','özgür'=>'E','özhan'=>'E','özkan'=>'E',
    'poyraz'=>'E','polat'=>'E','pulat'=>'E','remzi'=>'E','rıza'=>'E',
    'sabri'=>'E','samet'=>'E','sami'=>'E','sedat'=>'E','semih'=>'E',
    'talha'=>'E','tayyip'=>'E','taner'=>'E','tarık'=>'E','ufuk'=>'E',
    'yasin'=>'E','yavuz'=>'E','yener'=>'E','yücel'=>'E','yüksel'=>'E',
    'mehdi'=>'E','memduh'=>'E','melih'=>'E','mücahid'=>'E','muammer'=>'E',
    // ─ KADIN ─────────────────────────────────────────────────────────────
    'fatma'=>'K','ayşe'=>'K','emine'=>'K','hatice'=>'K','zeynep'=>'K',
    'büyük'=>'K','elif'=>'K','meryem'=>'K','hacer'=>'K','halime'=>'K',
    'hanife'=>'K','havva'=>'K','hayriye'=>'K','hediye'=>'K','huriye'=>'K',
    'nazlı'=>'K','naciye'=>'K','nazan'=>'K','naime'=>'K','neriman'=>'K',
    'nergis'=>'K','nesrin'=>'K','nihal'=>'K','nilüfer'=>'K','nişan'=>'K',
    'nuray'=>'K','nurten'=>'K','nuriye'=>'K','nuran'=>'K','nural'=>'K',
    'özlem'=>'K','oya'=>'K','olcay'=>'K','perihan'=>'K','pembe'=>'K',
    'raziye'=>'K','rukiye'=>'K','sabiha'=>'K','safiye'=>'K','saime'=>'K',
    'saliha'=>'K','saniye'=>'K','selma'=>'K','sema'=>'K','semra'=>'K',
    'serap'=>'K','seval'=>'K','sevil'=>'K','sevim'=>'K','sevgi'=>'K',
    'sibel'=>'K','sinem'=>'K','songül'=>'K','sultan'=>'K','tuğba'=>'K',
    'türkan'=>'K','tülin'=>'K','ümran'=>'K','ümiye'=>'K','ümmü'=>'K',
    'yasemin'=>'K','yıldız'=>'K','zeliha'=>'K','zübeyde'=>'K','zühal'=>'K',
    'zühre'=>'K','melek'=>'K','meltem'=>'K','merve'=>'K','mine'=>'K',
    'müberra'=>'K','münevver'=>'K','müzeyyen'=>'K','leyla'=>'K','latife'=>'K',
    'lamia'=>'K','lale'=>'K','kübra'=>'K','gülsüm'=>'K','gülten'=>'K',
    'gülseren'=>'K','gülay'=>'K','gülnar'=>'K','gülnihal'=>'K','gül'=>'K',
    'güler'=>'K','gönül'=>'K','filiz'=>'K','feraye'=>'K','feriha'=>'K',
    'fadime'=>'K','eser'=>'K','esra'=>'K','esin'=>'K','ercan'=>'K',
    'elmas'=>'K','ebru'=>'K','duriye'=>'K','derya'=>'K','deniz'=>'K',
    'dilek'=>'K','duygu'=>'K','cemile'=>'K','canan'=>'K','cavidan'=>'K',
    'beyhan'=>'K','berrin'=>'K','berrak'=>'K','belkıs'=>'K','belgin'=>'K',
    'berna'=>'K','binnur'=>'K','birgül'=>'K','arzu'=>'K','aslı'=>'K',
    'aslıhan'=>'K','asuman'=>'K','aydan'=>'K','ayla'=>'K','aylin'=>'K',
    'aysel'=>'K','ayten'=>'K','azize'=>'K','bahar'=>'K','başak'=>'K',
    'betül'=>'K','binnaz'=>'K','birsen'=>'K','cennet'=>'K','ceylan'=>'K',
    'çiğdem'=>'K','dilara'=>'K','dilan'=>'K','döndü'=>'K','elifnur'=>'K',
    'ece'=>'K','feride'=>'K','feride'=>'K','firdevs'=>'K','fulya'=>'K',
    'gamze'=>'K','gizem'=>'K','gonca'=>'K','gözde'=>'K','güzin'=>'K',
    'hülya'=>'K','hüsniye'=>'K','irem'=>'K','işıl'=>'K','kader'=>'K',
    'kadriye'=>'K','kamelya'=>'K','kezban'=>'K','levent'=>'K','lütfiye'=>'K',
    'mahinur'=>'K','mahire'=>'K','makbule'=>'K','mediha'=>'K','mehtap'=>'K',
    'menekşe'=>'K','müge'=>'K','nalan'=>'K','naz'=>'K','nurseli'=>'K',
    'nurhayat'=>'K','nurcihan'=>'K','pınar'=>'K','rahime'=>'K','reyhan'=>'K',
    'şafak'=>'K','şahide'=>'K','şengül'=>'K','şerife'=>'K','şükran'=>'K',
    'tuba'=>'K','tuğçe'=>'K','türkân'=>'K','uzay'=>'K','vildan'=>'K',
    'zehra'=>'K','zekiye'=>'K',
];

/**
 * Ad'dan cinsiyet tahmin et.
 * İsmin ilk kelimesini kullanır. Bilinmiyorsa null döndürür.
 *
 * @param  string  $adi_soyadi  Tam ad-soyad
 * @return string|null          'Erkek' | 'Kadın' | null
 */
function cinsiyet_tahmin(string $adi_soyadi, array $isim_db): ?string
{
    $kelimeler = preg_split('/\s+/', mb_strtolower(trim($adi_soyadi)));
    if (empty($kelimeler)) {
        return null;
    }
    $ad = $kelimeler[0];

    if (isset($isim_db[$ad])) {
        return $isim_db[$ad] === 'E' ? 'Erkek' : 'Kadın';
    }

    // İsmin ilk 5 harfiyle kısmi eşleştirme
    $ad_kismi = mb_substr($ad, 0, 5);
    foreach ($isim_db as $isim => $cinsiyet) {
        if (mb_substr($isim, 0, 5) === $ad_kismi) {
            return $cinsiyet === 'E' ? 'Erkek' : 'Kadın';
        }
    }

    return null;
}

// ─── ÜYELERİ ÇEK ─────────────────────────────────────────────────────────
// Sadece cinsiyeti boş olan onaylı üyeler
try {
    $sorgu = $db_baglanti->query(
        "SELECT id, adi_soyadi
           FROM dernek_uyeler
          WHERE onay_durumu = 'onayli'
            AND (cinsiyet IS NULL OR cinsiyet = '')
          ORDER BY adi_soyadi ASC"
    );
    $bos_uyeler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('Cinsiyet ata hata: ' . $e->getMessage());
    $bos_uyeler = [];
}

// Tahminleri üret
$tahminler = [];
$tahmin_erkek = 0;
$tahmin_kadin = 0;
$tahmin_bilinmiyor = 0;

foreach ($bos_uyeler as $uye) {
    $tahmin = cinsiyet_tahmin($uye['adi_soyadi'], $isim_db);
    $tahminler[$uye['id']] = $tahmin;
    if ($tahmin === 'Erkek') $tahmin_erkek++;
    elseif ($tahmin === 'Kadın') $tahmin_kadin++;
    else $tahmin_bilinmiyor++;
}

$toplam_bos = count($bos_uyeler);
?>

<div class="container-fluid py-4 px-md-4">

    <!-- Başlık -->
    <div class="d-flex align-items-center gap-3 mb-4">
        <div class="rounded-3 d-flex align-items-center justify-content-center"
             style="width:48px;height:48px;background:rgba(214,51,132,0.1);border:1px solid rgba(214,51,132,0.3);">
            <i class="fa-solid fa-venus-mars" style="color:#d63384;font-size:1.3rem;"></i>
        </div>
        <div>
            <h2 class="fw-bold text-dark mb-0">Cinsiyet Toplu Atama</h2>
            <p class="text-muted mb-0 small">Türkçe isim analizi ile otomatik tahmin — onaylayıp kaydet.</p>
        </div>
    </div>

    <?= $kayit_mesaj; ?>

    <?php if ($toplam_bos === 0): ?>
    <div class="alert alert-success rounded-4 d-flex align-items-center gap-3">
        <i class="fa-solid fa-circle-check fa-2x text-success"></i>
        <div>
            <div class="fw-bold">Tüm onaylı üyelerin cinsiyeti atanmış!</div>
            <div class="text-muted small">Cinsiyeti boş kalan üye bulunmuyor.</div>
        </div>
    </div>
    <?php else: ?>

    <!-- İstatistik Özeti -->
    <div class="row g-3 mb-4">
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 text-center py-3">
                <div class="fw-bold" style="font-size:2rem;color:#6c757d;"><?= $toplam_bos; ?></div>
                <div class="text-muted small">Cinsiyeti Boş</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 text-center py-3">
                <div class="fw-bold" style="font-size:2rem;color:#0d6efd;"><?= $tahmin_erkek; ?></div>
                <div class="text-muted small"><i class="fa-solid fa-mars text-primary me-1"></i>Erkek Tahmin</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 text-center py-3">
                <div class="fw-bold" style="font-size:2rem;color:#d63384;"><?= $tahmin_kadin; ?></div>
                <div class="text-muted small"><i class="fa-solid fa-venus text-danger me-1"></i>Kadın Tahmin</div>
            </div>
        </div>
        <div class="col-6 col-md-3">
            <div class="card border-0 rounded-4 shadow-sm h-100 text-center py-3">
                <div class="fw-bold" style="font-size:2rem;color:#fd7e14;"><?= $tahmin_bilinmiyor; ?></div>
                <div class="text-muted small"><i class="fa-solid fa-question text-warning me-1"></i>Belirlenemedi</div>
            </div>
        </div>
    </div>

    <!-- Onay Formu -->
    <form method="POST" action="index.php?sayfa=cinsiyet-ata">
        <input type="hidden" name="csrf_token" value="<?= htmlspecialchars($csrf); ?>">
        <input type="hidden" name="toplu_kaydet" value="1">

        <div class="card border-0 shadow-sm rounded-4">
            <div class="card-header bg-white border-0 px-4 pt-4 pb-3"
                 style="border-bottom:1px solid rgba(0,0,0,0.07) !important;">
                <div class="d-flex align-items-center justify-content-between flex-wrap gap-3">
                    <div>
                        <h5 class="fw-bold mb-0">Tahmin Listesi</h5>
                        <p class="text-muted small mb-0">Yanlış tahminleri değiştirip <strong>Toplu Kaydet</strong>'e basın. "Belirlenemedi" olanları Manuel seçin.</p>
                    </div>
                    <button type="submit" class="btn btn-success fw-bold px-4 shadow-sm">
                        <i class="fa-solid fa-floppy-disk me-2"></i>Toplu Kaydet
                    </button>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover align-middle mb-0" style="font-size:0.875rem;">
                        <thead style="background:linear-gradient(135deg,#1a1a2e,#16213e);color:#fff;">
                            <tr>
                                <th class="px-4 py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">#</th>
                                <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Adı Soyadı</th>
                                <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Tahmin</th>
                                <th class="py-3 text-white" style="font-size:0.72rem;text-transform:uppercase;">Cinsiyet Seçimi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php foreach ($bos_uyeler as $i => $uye):
                                $tahmin = $tahminler[$uye['id']];
                            ?>
                            <tr style="border-bottom:1px solid rgba(0,0,0,0.05);">
                                <td class="px-4 text-muted" style="font-size:0.8rem;"><?= $i + 1; ?></td>
                                <td>
                                    <a href="index.php?sayfa=uye-detay&id=<?= $uye['id']; ?>"
                                       class="fw-semibold text-decoration-none text-dark" target="_blank">
                                        <?= htmlspecialchars($uye['adi_soyadi']); ?>
                                    </a>
                                </td>
                                <td>
                                    <?php if ($tahmin === 'Erkek'): ?>
                                    <span class="badge" style="background:rgba(13,110,253,0.12);color:#0d6efd;">
                                        <i class="fa-solid fa-mars me-1"></i>Erkek (Otomatik)
                                    </span>
                                    <?php elseif ($tahmin === 'Kadın'): ?>
                                    <span class="badge" style="background:rgba(214,51,132,0.12);color:#d63384;">
                                        <i class="fa-solid fa-venus me-1"></i>Kadın (Otomatik)
                                    </span>
                                    <?php else: ?>
                                    <span class="badge bg-warning text-dark">
                                        <i class="fa-solid fa-question me-1"></i>Belirlenemedi
                                    </span>
                                    <?php endif; ?>
                                </td>
                                <td>
                                    <div class="d-flex gap-2">
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio"
                                                   name="cinsiyet_data[<?= $uye['id']; ?>]"
                                                   id="e_<?= $uye['id']; ?>"
                                                   value="Erkek"
                                                   <?= $tahmin === 'Erkek' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold text-primary"
                                                   for="e_<?= $uye['id']; ?>">
                                                <i class="fa-solid fa-mars me-1"></i>Erkek
                                            </label>
                                        </div>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio"
                                                   name="cinsiyet_data[<?= $uye['id']; ?>]"
                                                   id="k_<?= $uye['id']; ?>"
                                                   value="Kadın"
                                                   <?= $tahmin === 'Kadın' ? 'checked' : ''; ?>>
                                            <label class="form-check-label fw-semibold text-danger"
                                                   for="k_<?= $uye['id']; ?>">
                                                <i class="fa-solid fa-venus me-1"></i>Kadın
                                            </label>
                                        </div>
                                        <?php if ($tahmin === null): ?>
                                        <div class="form-check form-check-inline mb-0">
                                            <input class="form-check-input" type="radio"
                                                   name="cinsiyet_data[<?= $uye['id']; ?>]"
                                                   id="s_<?= $uye['id']; ?>"
                                                   value=""
                                                   checked>
                                            <label class="form-check-label text-muted"
                                                   for="s_<?= $uye['id']; ?>">Atla</label>
                                        </div>
                                        <?php endif; ?>
                                    </div>
                                </td>
                            </tr>
                            <?php endforeach; ?>
                        </tbody>
                    </table>
                </div>
            </div>
            <div class="card-footer bg-white border-0 px-4 py-3 text-end">
                <button type="submit" class="btn btn-success fw-bold px-5 shadow-sm">
                    <i class="fa-solid fa-floppy-disk me-2"></i>Toplu Kaydet
                </button>
            </div>
        </div>
    </form>
    <?php endif; ?>
</div>
