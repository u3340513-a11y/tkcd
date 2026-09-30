<?php

declare(strict_types=1);

/**
 * Bekleyen Üyelik Başvuruları
 *
 * Onay/red işlemleri, mükerrer kontrol ve rol bazlı yetki mantığı
 * korunmuştur. Yalnızca görsel katman yeniden tasarlandı.
 */

$mesaj    = '';
$mesajTuru = '';

$kullaniciRolu       = $_SESSION['rol']          ?? 'admin';
$oturumKullaniciAdi  = $_SESSION['kullanici_adi'] ?? '';

$isIlBaskani      = ($kullaniciRolu === 'il_baskani');
$isIlceBaskani    = ($kullaniciRolu === 'ilce_baskani');
$isKurumTemsilci  = ($kullaniciRolu === 'kurum_temsilcisi');
$isKadinKollari   = ($kullaniciRolu === 'kadin_kollari_baskani');
$isKisitliRol     = ($isIlBaskani || $isIlceBaskani || $isKurumTemsilci || $isKadinKollari);

$yalnızcaSayimKullanicilari = ['yonetim_ukk','yonetim_mh','yonetim_mb','yonetim_he','yonetim_hk','kk_by'];
$isYalnızcaSayim = in_array($oturumKullaniciAdi, $yalnızcaSayimKullanicilari, true);

$onayYetkiliKullanicilari = ['yonetim_ukk','yonetim_mh','yonetim_hk','yonetim_mb','kk_by','admin61'];
$isOnayYetkili = ($kullaniciRolu === 'gelistirici')
    || in_array($oturumKullaniciAdi, $onayYetkiliKullanicilari, true);

// ─── ONAYLAMA ────────────────────────────────────────────────────────────
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'basvuru_onayla' && isset($_GET['id'])) {
    if (!$isOnayYetkili) {
        die('Erişim Engellendi: Bu hesap ile başvuru onaylama işlemi yapılamaz!');
    }
    $uyeId = (int) $_GET['id'];
    try {
        $uyeBul = $db_baglanti->prepare('SELECT adi_soyadi, telefon FROM dernek_uyeler WHERE id = ?');
        $uyeBul->execute([$uyeId]);
        $mevcut = $uyeBul->fetch(PDO::FETCH_ASSOC);

        if ($mevcut) {
            $telefon = trim($mevcut['telefon'] ?? '');
            if ($telefon !== '') {
                $kontrol = $db_baglanti->prepare(
                    "SELECT COUNT(*) FROM dernek_uyeler WHERE telefon = ? AND onay_durumu = 'onayli' AND id != ?"
                );
                $kontrol->execute([$telefon, $uyeId]);
                if ((int) $kontrol->fetchColumn() > 0) {
                    echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=mukerrer_hata&hata_tel=" . urlencode($telefon) . "';</script>";
                    exit;
                }
            }
            $db_baglanti->prepare(
                "UPDATE dernek_uyeler SET onay_durumu='onayli',
                 uyelik_tarihi = IF(uyelik_tarihi IS NULL OR uyelik_tarihi='0000-00-00', CURDATE(), uyelik_tarihi)
                 WHERE id = ?"
            )->execute([$uyeId]);

            $onayAdi = $mevcut['adi_soyadi'] ?? ('Bilinmeyen #' . $uyeId);
            log_kaydet($db_baglanti, 'uye_onayla', $onayAdi . ' adlı başvuru onaylandı.', 'dernek_uyeler', $uyeId);
            echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=onaylandi';</script>";
            exit;
        }
    } catch (\PDOException $e) {
        $mesaj     = 'Onaylama Hatası: ' . $e->getMessage();
        $mesajTuru = 'danger';
    }
}

// ─── REDDETME ────────────────────────────────────────────────────────────
if (isset($_GET['aksiyon']) && $_GET['aksiyon'] === 'basvuru_reddet' && isset($_GET['id'])) {
    if (!$isOnayYetkili) {
        die('Erişim Engellendi: Bu hesap ile başvuru reddetme işlemi yapılamaz!');
    }
    $uyeId = (int) $_GET['id'];
    try {
        $redAdSorgu = $db_baglanti->prepare('SELECT adi_soyadi FROM dernek_uyeler WHERE id = ?');
        $redAdSorgu->execute([$uyeId]);
        $redAdi = $redAdSorgu->fetchColumn() ?: ('Bilinmeyen #' . $uyeId);

        $db_baglanti->prepare(
            "DELETE FROM dernek_uyeler WHERE id = ? AND onay_durumu = 'bekleyen'"
        )->execute([$uyeId]);

        log_kaydet($db_baglanti, 'uye_reddet', $redAdi . ' adlı başvuru reddedildi.', 'dernek_uyeler', $uyeId);
        echo "<script>window.location.href='index.php?sayfa=bekleyen-uyeler&mesaj_durum=reddedildi';</script>";
        exit;
    } catch (\PDOException $e) {
        $mesaj     = 'Reddetme Hatası: ' . $e->getMessage();
        $mesajTuru = 'danger';
    }
}

// ─── BİLDİRİM MESAJLARI ──────────────────────────────────────────────────
if (isset($_GET['mesaj_durum'])) {
    match ($_GET['mesaj_durum']) {
        'onaylandi'   => [$mesaj = 'Başvuru başarıyla onaylandı ve aktif üye listesine eklendi!', $mesajTuru = 'success'],
        'reddedildi'  => [$mesaj = 'Başvuru reddedildi ve sistemden silindi.', $mesajTuru = 'warning'],
        'mukerrer_hata' => [
            $mesaj = 'HATA: [' . htmlspecialchars($_GET['hata_tel'] ?? '') . '] numarası ile zaten aktif bir üye kayıtlı!',
            $mesajTuru = 'danger',
        ],
        default => null,
    };
}

// ─── VERİ ÇEK ────────────────────────────────────────────────────────────
try {
    $sorgu = $db_baglanti->query(
        "SELECT * FROM dernek_uyeler WHERE onay_durumu = 'bekleyen' ORDER BY id DESC"
    );
    $bekleyenler = $sorgu->fetchAll(PDO::FETCH_ASSOC);
} catch (\PDOException $e) {
    error_log('bekleyen-uyeler hata: ' . $e->getMessage());
    die('Veriler yüklenirken hata oluştu.');
}

$toplamBekleyen = count($bekleyenler);

// ─── YARDIMCI: Cinsiyet Tespiti ──────────────────────────────────────────
function cinsiyetTespit(array $b): bool /* true = kadın */
{
    $c = mb_strtolower(trim($b['cinsiyet'] ?? ''), 'UTF-8');
    if (in_array($c, ['kadın', 'kadin', 'female', 'k'], true)) return true;
    if (in_array($c, ['erkek', 'male', 'e'], true)) return false;
    // fallback
    $ilkIsim = mb_strtoupper(explode(' ', trim($b['adi_soyadi']))[0], 'UTF-8');
    $kadinIsimleri = ['SEMRA','AYŞEGÜL','BEGÜM','HATİCE','FATMA','AYŞE','EMİNE','ZEYNEP',
        'MERYEM','ELİF','HÜLYA','GAMZE','MERVE','BÜŞRA','ESRA','SEDA','DERYA','KÜBRA',
        'ASLI','PELİN','TUĞBA','DEMET','ÖZLEM','SİNEM','GÜL','NUR','MELİS','DİLAN',
        'BURCU','CANAN','SULTAN','MELİKE','YASEMİN','EDA','BERNA','SELEN','PINAR',
        'BANU','YEŞİM','EBRU','FADİME','NURAN','SELMA','DİLEK','FİLİZ','ARZU','LEYLA',
        'SİBEL','HALE','JALE','GONCA','MÜGE','NESLİHAN','NAZLI','MİNE','SELİN','ESMA',
        'FAZİLET','NESRİN','REYHAN','AHSEN','İPEK','ÖZGE','GÜLAY','SÜREYYA','DİDEM',
        'HANDAN','NURTEN','ŞERİFE','SABİHA','ZEHRA','ÜMMÜHAN','RABİA','GÜLSÜM','ŞEYMA',
        'BETÜL','SÜMEYYE','KADRİYE','HAVVA','SONGÜL','DÖNDÜ','NURAY','FİRDEVS','AYTEN',
        'AYSEL','GÜLER','NURSEL','NURCAN','MELEK','NURHAN','PERİHAN','SUZAN','SUNA',
        'ŞENNUR','İLKAY','GÜLDEN','GÜLŞAH','SEVAL','SEVİL','SEVİM','NİHAL','NİLÜFER',
        'NİLAY','MELTEM','ÜLKÜ','DUYGU','NURŞEN'];
    return in_array($ilkIsim, $kadinIsimleri, true);
}

function formatDogum(array $b): string
{
    $raw = $b['dogum_tarihi'] ?? '';
    if (empty($raw) || $raw === '0000-00-00') {
        return $b['dogum_yili'] ?? '-';
    }
    if (preg_match('/^(\d{4})-(\d{2})-(\d{2})$/', $raw, $m)) {
        return $m[3] . '.' . $m[2] . '.' . $m[1];
    }
    return htmlspecialchars(str_replace('/', '.', $raw));
}
?>

<style>
/* ── Bekleyen başvurular — modern kart tabanlı tasarım ──────────────────── */
.bub-header {
    display: flex;
    align-items: center;
    gap: 1.2rem;
    flex-wrap: wrap;
    margin-bottom: 2rem;
}
.bub-header__ikon {
    width: 56px; height: 56px;
    background: linear-gradient(135deg, #ff4757, #c0392b);
    border-radius: 16px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    box-shadow: 0 8px 20px rgba(255,71,87,.35);
}
.bub-header__ikon i { color: #fff; font-size: 1.4rem; }
.bub-header__baslik { font-size: 1.5rem; font-weight: 800; color: #1a1a2e; margin: 0; }
.bub-header__aciklama { color: #6c757d; font-size: 0.88rem; margin: 0.1rem 0 0; }
.bub-sayac {
    margin-left: auto;
    background: linear-gradient(135deg, #ff4757, #c0392b);
    color: #fff;
    border-radius: 50px;
    padding: 0.4rem 1.2rem;
    font-weight: 700;
    font-size: 0.9rem;
    white-space: nowrap;
    box-shadow: 0 4px 12px rgba(255,71,87,.3);
}

/* Kart grid */
.bub-grid {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(360px, 1fr));
    gap: 1.25rem;
}

/* Tekil başvuru kartı */
.bub-kart {
    background: #fff;
    border-radius: 18px;
    border: 1px solid #f0f0f5;
    box-shadow: 0 2px 16px rgba(0,0,0,.06);
    overflow: hidden;
    transition: transform .2s ease, box-shadow .2s ease;
    position: relative;
}
.bub-kart:hover {
    transform: translateY(-3px);
    box-shadow: 0 8px 32px rgba(0,0,0,.11);
}
.bub-kart__renk-serit {
    height: 4px;
    background: linear-gradient(90deg, #ff4757, #ff6b81);
}
.bub-kart__renk-serit--kadin {
    background: linear-gradient(90deg, #e83e8c, #fd79a8);
}
.bub-kart__ust {
    padding: 1.1rem 1.25rem 0.75rem;
    display: flex;
    align-items: flex-start;
    gap: 0.9rem;
}
.bub-kart__avatar {
    width: 46px; height: 46px;
    border-radius: 14px;
    display: flex; align-items: center; justify-content: center;
    flex-shrink: 0;
    font-size: 1.15rem;
}
.bub-kart__avatar--erkek {
    background: rgba(0,123,255,.1);
    color: #007bff;
}
.bub-kart__avatar--kadin {
    background: rgba(232,62,140,.1);
    color: #e83e8c;
}
.bub-kart__isim { font-weight: 700; font-size: 1rem; color: #1a1a2e; line-height: 1.3; }
.bub-kart__kan {
    display: inline-block;
    font-size: 0.7rem; font-weight: 700;
    background: #ff4757; color: #fff;
    border-radius: 6px; padding: 2px 8px;
    margin-top: 4px;
}
.bub-kart__kan--bos {
    background: #e9ecef; color: #6c757d;
}

.bub-kart__bilgiler {
    padding: 0 1.25rem 0.9rem;
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 0.5rem 1rem;
}
.bub-kart__bilgi { display: flex; flex-direction: column; gap: 1px; }
.bub-kart__bilgi-etiket {
    font-size: 0.68rem;
    font-weight: 700;
    color: #adb5bd;
    text-transform: uppercase;
    letter-spacing: .05em;
}
.bub-kart__bilgi-deger {
    font-size: 0.83rem;
    color: #343a40;
    font-weight: 500;
    word-break: break-word;
}
.bub-kart__bilgi--tam { grid-column: 1 / -1; }

.bub-kart__iletisim {
    margin: 0 1.25rem 0.75rem;
    padding: 0.65rem 0.85rem;
    background: #f8f9fa;
    border-radius: 10px;
    display: flex; flex-direction: column; gap: 0.3rem;
}
.bub-kart__iletisim-satir {
    display: flex; align-items: center; gap: 0.5rem;
    font-size: 0.82rem; color: #495057;
}
.bub-kart__iletisim-satir i {
    width: 16px; text-align: center;
    color: #adb5bd; font-size: 0.8rem;
}

.bub-kart__aksiyonlar {
    padding: 0.75rem 1.25rem 1.1rem;
    display: flex; gap: 0.65rem;
    border-top: 1px solid #f0f0f5;
}
.bub-kart__btn {
    flex: 1;
    border: none; border-radius: 10px;
    padding: 0.6rem 0;
    font-weight: 700; font-size: 0.82rem;
    cursor: pointer;
    transition: filter .15s, transform .15s;
    display: flex; align-items: center; justify-content: center; gap: 0.4rem;
    text-decoration: none;
}
.bub-kart__btn:hover { filter: brightness(1.08); transform: scale(1.02); }
.bub-kart__btn--onayla {
    background: linear-gradient(135deg, #00b894, #00cec9);
    color: #fff;
    box-shadow: 0 4px 12px rgba(0,184,148,.3);
}
.bub-kart__btn--reddet {
    background: #fff;
    color: #e74c3c;
    border: 1.5px solid #e74c3c;
}
.bub-kart__btn--reddet:hover {
    background: #e74c3c;
    color: #fff;
}
.bub-kart__btn--yetkisiz {
    background: #f8f9fa; color: #adb5bd;
    cursor: not-allowed; flex: 1;
    border-radius: 10px; padding: 0.6rem;
    font-size: 0.82rem; text-align: center;
}

/* Boş durum */
.bub-bos {
    grid-column: 1 / -1;
    text-align: center;
    padding: 4rem 2rem;
    color: #adb5bd;
}
.bub-bos i { font-size: 3rem; margin-bottom: 1rem; display: block; }
.bub-bos p { font-size: 1rem; margin: 0; }

/* Bildirim */
.bub-bildirim {
    border-radius: 12px;
    padding: 0.9rem 1.1rem;
    margin-bottom: 1.25rem;
    display: flex; align-items: center; gap: 0.75rem;
    font-weight: 600; font-size: 0.9rem;
}
.bub-bildirim--success { background: #d1e7dd; color: #0f5132; }
.bub-bildirim--warning { background: #fff3cd; color: #664d03; }
.bub-bildirim--danger  { background: #f8d7da; color: #842029; }
</style>

<div class="container-fluid py-4 px-md-4">

    <!-- Başlık -->
    <div class="bub-header">
        <div class="bub-header__ikon">
            <i class="fa-solid fa-user-clock"></i>
        </div>
        <div>
            <h2 class="bub-header__baslik">Bekleyen Üyelik Başvuruları</h2>
            <p class="bub-header__aciklama">Web sitesinden başvuru yapmış, henüz onaylanmamış adaylar</p>
        </div>
        <span class="bub-sayac">
            <i class="fa-solid fa-hourglass-half me-1"></i>
            <?= $toplamBekleyen ?> Bekleyen
        </span>
    </div>

    <!-- Bildirimler -->
    <?php if ($mesaj !== ''): ?>
    <div class="bub-bildirim bub-bildirim--<?= $mesajTuru ?>">
        <i class="fa-solid fa-<?= $mesajTuru === 'success' ? 'circle-check' : ($mesajTuru === 'danger' ? 'circle-exclamation' : 'triangle-exclamation') ?>"></i>
        <?= htmlspecialchars($mesaj) ?>
    </div>
    <?php endif; ?>

    <!-- Kartlar -->
    <div class="bub-grid">
        <?php if ($toplamBekleyen > 0): ?>
        <?php foreach ($bekleyenler as $b):
            $isKadin   = cinsiyetTespit($b);
            $cinsiyet  = $isKadin ? 'kadin' : 'erkek';
            $ikonSekil = $isKadin ? 'fa-user-nurse' : 'fa-user';
            $kan       = !empty($b['kan_grubu']) ? $b['kan_grubu'] : null;
            $dogum     = formatDogum($b);
            $il        = htmlspecialchars($b['ikamet_ili']    ?: '-');
            $ilce      = htmlspecialchars($b['ikamet_ilcesi'] ?: ($b['trabzon_ilcesi'] ?: '-'));
        ?>
        <div class="bub-kart">
            <div class="bub-kart__renk-serit <?= $isKadin ? 'bub-kart__renk-serit--kadin' : '' ?>"></div>

            <!-- Üst: Avatar + İsim -->
            <div class="bub-kart__ust">
                <div class="bub-kart__avatar bub-kart__avatar--<?= $cinsiyet ?>">
                    <i class="fa-solid <?= $ikonSekil ?>"></i>
                </div>
                <div style="flex:1;min-width:0;">
                    <div class="bub-kart__isim"><?= htmlspecialchars($b['adi_soyadi']) ?></div>
                    <?php if ($kan): ?>
                    <span class="bub-kart__kan"><?= htmlspecialchars($kan) ?></span>
                    <?php else: ?>
                    <span class="bub-kart__kan bub-kart__kan--bos">Kan Grubu Yok</span>
                    <?php endif; ?>
                </div>
                <div style="text-align:right;flex-shrink:0;">
                    <small style="color:#adb5bd;font-size:0.72rem;">#<?= (int)$b['id'] ?></small>
                </div>
            </div>

            <!-- İletişim -->
            <div class="bub-kart__iletisim">
                <div class="bub-kart__iletisim-satir">
                    <i class="fa-solid fa-phone"></i>
                    <span><?= $isYalnızcaSayim ? htmlspecialchars($b['telefon'] ?: '-') : gizli_alan(htmlspecialchars($b['telefon'] ?: '')) ?></span>
                </div>
                <div class="bub-kart__iletisim-satir">
                    <i class="fa-solid fa-envelope"></i>
                    <span style="font-size:0.78rem;"><?= gizli_alan(htmlspecialchars($b['eposta'] ?: '')) ?></span>
                </div>
            </div>

            <!-- Bilgiler grid -->
            <div class="bub-kart__bilgiler">
                <div class="bub-kart__bilgi">
                    <span class="bub-kart__bilgi-etiket"><i class="fa-solid fa-cake-candles me-1"></i>Doğum</span>
                    <span class="bub-kart__bilgi-deger"><?= $dogum ?></span>
                </div>
                <div class="bub-kart__bilgi">
                    <span class="bub-kart__bilgi-etiket"><i class="fa-solid fa-map-pin me-1"></i>İl / İlçe</span>
                    <span class="bub-kart__bilgi-deger"><?= $il ?> <span style="color:#adb5bd;">/ <?= $ilce ?></span></span>
                </div>
                <div class="bub-kart__bilgi">
                    <span class="bub-kart__bilgi-etiket"><i class="fa-solid fa-building me-1"></i>Kurum</span>
                    <span class="bub-kart__bilgi-deger"><?= htmlspecialchars($b['kurum'] ?: '-') ?></span>
                </div>
                <div class="bub-kart__bilgi">
                    <span class="bub-kart__bilgi-etiket"><i class="fa-solid fa-briefcase me-1"></i>Ünvan</span>
                    <span class="bub-kart__bilgi-deger"><?= htmlspecialchars($b['gorev_unvan'] ?: '-') ?></span>
                </div>
                <div class="bub-kart__bilgi bub-kart__bilgi--tam">
                    <span class="bub-kart__bilgi-etiket"><i class="fa-solid fa-id-badge me-1"></i>Çalışma Şekli</span>
                    <span class="bub-kart__bilgi-deger"><?= htmlspecialchars($b['calisma_sekli'] ?: '-') ?></span>
                </div>
            </div>

            <!-- Aksiyon butonları -->
            <div class="bub-kart__aksiyonlar">
                <?php if (!$isOnayYetkili): ?>
                    <div class="bub-kart__btn--yetkisiz">
                        <i class="fa-solid fa-lock me-1"></i>İşlem Yetkiniz Yok
                    </div>
                <?php else: ?>
                    <a href="index.php?sayfa=bekleyen-uyeler&aksiyon=basvuru_onayla&id=<?= (int)$b['id'] ?>"
                       class="bub-kart__btn bub-kart__btn--onayla"
                       onclick="return confirm('<?= htmlspecialchars($b['adi_soyadi']) ?> isimli adayı üye olarak onaylıyor musunuz?')">
                        <i class="fa-solid fa-user-check"></i> Onayla
                    </a>
                    <a href="index.php?sayfa=bekleyen-uyeler&aksiyon=basvuru_reddet&id=<?= (int)$b['id'] ?>"
                       class="bub-kart__btn bub-kart__btn--reddet"
                       onclick="return confirm('<?= htmlspecialchars($b['adi_soyadi']) ?> başvurusunu reddetmek istediğinize emin misiniz?')">
                        <i class="fa-solid fa-user-xmark"></i> Reddet
                    </a>
                <?php endif; ?>
            </div>
        </div>
        <?php endforeach; ?>
        <?php else: ?>
        <div class="bub-bos">
            <i class="fa-solid fa-envelope-open-text" style="color:#dee2e6;"></i>
            <p>Şu anda onay bekleyen üyelik başvurusu bulunmuyor.</p>
        </div>
        <?php endif; ?>
    </div>

</div>