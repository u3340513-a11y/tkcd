<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Üyelik Başvurusu sayfası.
 *
 * Form alanları:
 *   - Adı Soyadı, Telefon, E-Posta (zorunlu)
 *   - Kan Grubu, Doğum Tarihi
 *   - İkamet Edilen İl (81 il — Türkiye), Trabzon İlçesi (nüfusa kayıtlı)
 *   - Çalıştığı Kurum, Görev / Ünvan, Çalışma Şekli
 *   - KVKK Onayı (zorunlu), reCAPTCHA, Gönder
 *
 * Veri doğrulama ve e-posta gönderimi ikinci fazda eklenecek;
 * şu aşamada form HTML olarak sunulmaktadır.
 *
 * @var PhpViewRenderer      $view
 * @var SeoMeta              $seo
 * @var array<string, mixed> $site
 * @var string|null          $durum   'basarili' | 'hata' | null
 * @var string               $recaptchaSiteKey
 */

/** @var list<string> $iller */
$iller = [
    'Adana', 'Adıyaman', 'Afyonkarahisar', 'Ağrı', 'Amasya',
    'Ankara', 'Antalya', 'Artvin', 'Aydın', 'Balıkesir',
    'Bilecik', 'Bingöl', 'Bitlis', 'Bolu', 'Burdur',
    'Bursa', 'Çanakkale', 'Çankırı', 'Çorum', 'Denizli',
    'Diyarbakır', 'Edirne', 'Elazığ', 'Erzincan', 'Erzurum',
    'Eskişehir', 'Gaziantep', 'Giresun', 'Gümüşhane', 'Hakkari',
    'Hatay', 'Isparta', 'Mersin', 'İstanbul', 'İzmir',
    'Kars', 'Kastamonu', 'Kayseri', 'Kırklareli', 'Kırşehir',
    'Kocaeli', 'Konya', 'Kütahya', 'Malatya', 'Manisa',
    'Kahramanmaraş', 'Mardin', 'Muğla', 'Muş', 'Nevşehir',
    'Niğde', 'Ordu', 'Rize', 'Sakarya', 'Samsun',
    'Siirt', 'Sinop', 'Sivas', 'Tekirdağ', 'Tokat',
    'Trabzon', 'Tunceli', 'Şanlıurfa', 'Uşak', 'Van',
    'Yozgat', 'Zonguldak', 'Aksaray', 'Bayburt', 'Karaman',
    'Kırıkkale', 'Batman', 'Şırnak', 'Bartın', 'Ardahan',
    'Iğdır', 'Yalova', 'Karabük', 'Kilis', 'Osmaniye', 'Düzce',
];
sort($iller, SORT_LOCALE_STRING);

/** @var list<string> $trabzonIlceleri */
$trabzonIlceleri = [
    'Akçaabat', 'Araklı', 'Arsin', 'Beşikdüzü', 'Çarşıbaşı',
    'Çaykara', 'Dernekpazarı', 'Düzköy', 'Hayrat', 'Köprübaşı',
    'Maçka', 'Of', 'Ortahisar', 'Sürmene', 'Şalpazarı',
    'Tonya', 'Vakfıkebir', 'Yomra',
];

/** @var list<string> $kanGruplari */
$kanGruplari = ['A Rh+', 'A Rh-', 'B Rh+', 'B Rh-', 'AB Rh+', 'AB Rh-', '0 Rh+', '0 Rh-'];

/** @var list<string> $calismaSekilleri */
$calismaSekilleri = ['Tam Zamanlı', 'Yarı Zamanlı', 'Sözleşmeli', 'Emekli Kamu Çalışanı'];

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  1. HERO                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="ub-hero" aria-labelledby="ub-hero-baslik">
    <div class="kapsayici">
        <h1 class="ub-hero__baslik" id="ub-hero-baslik">
            Üyelik <span>Başvurusu</span>
        </h1>
        <span class="ub-hero__ayrac" aria-hidden="true"></span>
        <p class="ub-hero__aciklama">
            Trabzonlu kamu çalışanlarını aynı çatı altında buluşturan derneğimize üye olarak
            sosyal ve kültürel faaliyetlerimize katılabilir, dayanışma ruhunun bir parçası olabilirsiniz.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  2. SÜREÇ ADIMLARI                                  ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div class="ub-surec" aria-label="Üyelik süreci adımları">
    <span class="ub-surec__adim">
        <?= $view->icon('file-text') ?>
        Üyelik formunu doldurun
    </span>
    <span class="ub-surec__ayrac" aria-hidden="true">—</span>
    <span class="ub-surec__adim">
        <?= $view->icon('search') ?>
        Başvurunuz yönetim kurulu tarafından incelenir
    </span>
    <span class="ub-surec__ayrac" aria-hidden="true">—</span>
    <span class="ub-surec__adim">
        <?= $view->icon('phone') ?>
        Onay sonrası sizinle iletişime geçilir
    </span>
</div>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  3. FORM                                             ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="ub-alan" aria-labelledby="ub-form-baslik">
    <div class="kapsayici">
        <div class="ub-kutu">

            <?php if ($durum === 'basarili'): ?>
            <div class="ub-bildiri ub-bildiri--basarili" role="alert">
                <?= $view->icon('check-circle') ?>
                <div>
                    <strong>Başvurunuz Alındı</strong>
                    <p>Yönetim kurulumuz değerlendirmesinin ardından sizinle iletişime geçecektir.</p>
                </div>
            </div>
            <?php elseif ($durum === 'hata'): ?>
            <div class="ub-bildiri ub-bildiri--hata" role="alert">
                <?= $view->icon('alert-circle') ?>
                <div>
                    <strong>Gönderim Başarısız</strong>
                    <p>Lütfen tüm zorunlu alanları doğru doldurup tekrar deneyin.</p>
                </div>
            </div>
            <?php endif; ?>

            <form
                class="ub-form"
                id="uyelik-basvuru-formu"
                method="POST"
                action="/uye-ol"
                novalidate
                aria-label="Üyelik başvuru formu"
            >
                <!-- Adı Soyadı -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-ad-soyad">
                        Adı Soyadı <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="text"
                        id="ub-ad-soyad"
                        name="ad_soyad"
                        placeholder="Ad Soyad"
                        autocomplete="name"
                        minlength="3"
                        maxlength="120"
                        pattern="[A-Za-z\u00c7\u00e7\u011e\u011f\u0130\u0131\u00d6\u00f6\u015e\u015f\u00dc\u00fc\s]+"
                        required
                        aria-required="true"
                        aria-describedby="ub-ad-soyad-ipucu"
                        spellcheck="false"
                    >
                    <span id="ub-ad-soyad-ipucu" class="gorsel-gizli">
                        Yalnızca harf ve boşluk kullanabilirsiniz; rakam ve özel karakter kabul edilmez.
                    </span>
                </div>

                <!-- Telefon Numarası -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-telefon">
                        Telefon Numarası <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <div class="ub-form__telefon-grup">
                        <span class="ub-form__telefon-prefix" aria-hidden="true">05</span>
                        <input
                            class="ub-form__girdi"
                            type="tel"
                            id="ub-telefon"
                            name="telefon"
                            placeholder="XX XXX XX XX"
                            autocomplete="tel-national"
                            minlength="9"
                            maxlength="9"
                            pattern="[0-9]{9}"
                            inputmode="numeric"
                            required
                            aria-required="true"
                            aria-describedby="ub-telefon-ipucu"
                            spellcheck="false"
                            autocorrect="off"
                        >
                    </div>
                    <span id="ub-telefon-ipucu" class="gorsel-gizli">
                        Başında 05 olmadan 9 rakam giriniz (örn: 532 123 45 67 → 532123456).
                    </span>
                </div>

                <!-- E-Posta -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-eposta">
                        E-Posta Adresi <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="email"
                        id="ub-eposta"
                        name="eposta"
                        placeholder="ornek@kurum.gov.tr"
                        autocomplete="email"
                        maxlength="254"
                        required
                        aria-required="true"
                        aria-describedby="ub-eposta-ipucu"
                        spellcheck="false"
                        autocorrect="off"
                        autocapitalize="off"
                    >
                    <span id="ub-eposta-ipucu" class="gorsel-gizli">
                        Geçerli bir e-posta adresi giriniz.
                    </span>
                </div>

                <!-- Kan Grubu -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-kan-grubu">
                        Kan Grubu (İsteğe Bağlı)
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-kan-grubu"
                        name="kan_grubu"
                    >
                        <option value="">-- Kan Grubu (İsteğe Bağlı) --</option>
                        <?php foreach ($kanGruplari as $kan): ?>
                        <option value="<?= $view->e($kan) ?>"><?= $view->e($kan) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Doğum Tarihi -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-dogum-tarihi">
                        Doğum Tarihi <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="date"
                        id="ub-dogum-tarihi"
                        name="dogum_tarihi"
                        autocomplete="bday"
                        required
                        aria-required="true"
                        max="<?= date('Y-m-d', strtotime('-18 years')) ?>"
                        min="1930-01-01"
                    >
                </div>

                <!-- İkamet Edilen İl -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-ikamet-il">
                        İkamet Edilen İl <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-ikamet-il"
                        name="ikamet_il"
                        required
                        aria-required="true"
                    >
                        <option value="">-- İl Seçiniz --</option>
                        <?php foreach ($iller as $il): ?>
                        <option value="<?= $view->e($il) ?>"><?= $view->e($il) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Trabzon İlçesi (Nüfusa Kayıtlı) -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-trabzon-ilce">
                        Trabzon İlçesi (Nüfusa Kayıtlı)
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-trabzon-ilce"
                        name="trabzon_ilce"
                    >
                        <option value="">-- İlçe Seçiniz --</option>
                        <?php foreach ($trabzonIlceleri as $ilce): ?>
                        <option value="<?= $view->e($ilce) ?>"><?= $view->e($ilce) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- Çalıştığı Kurum -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-kurum">
                        Çalıştığı Kurum
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="text"
                        id="ub-kurum"
                        name="kurum"
                        placeholder="Örn: Maliye Bakanlığı"
                        maxlength="200"
                        autocomplete="organization"
                    >
                </div>

                <!-- Görev / Ünvan -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-gorev">
                        Görev / Ünvan
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="text"
                        id="ub-gorev"
                        name="gorev"
                        placeholder="Örn: Mühendis"
                        maxlength="120"
                        autocomplete="organization-title"
                    >
                </div>

                <!-- Çalışma Şekli -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-calisma-sekli">
                        Çalışma Şekli
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-calisma-sekli"
                        name="calisma_sekli"
                    >
                        <option value="">-- Seçiniz --</option>
                        <?php foreach ($calismaSekilleri as $sekil): ?>
                        <option value="<?= $view->e($sekil) ?>"><?= $view->e($sekil) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- KVKK Onayı -->
                <div class="ub-kvkk">
                    <input
                        class="ub-kvkk__kutu"
                        type="checkbox"
                        id="ub-kvkk"
                        name="kvkk"
                        value="1"
                        required
                        aria-required="true"
                    >
                    <label class="ub-kvkk__etiket" for="ub-kvkk">
                        <strong>KVKK Onayı</strong> <span style="color:var(--bordo-500)">*</span><br>
                        Kişisel verilerimin işlenmesini kabul ediyorum.
                        <a
                            class="ub-kvkk__link"
                            href="/kvkk-aydinlatma-metni"
                            target="_blank"
                            rel="noopener noreferrer"
                        >Kvkk Aydınlatma Metni</a>
                    </label>
                </div>

                <!-- reCAPTCHA -->
                <?php if (!empty($recaptchaSiteKey)): ?>
                <div class="ub-captcha">
                    <div
                        class="g-recaptcha"
                        data-sitekey="<?= $view->e($recaptchaSiteKey) ?>"
                    ></div>
                </div>
                <?php endif; ?>

                <!-- Gönder -->
                <div>
                    <button class="ub-gonder" type="submit" id="ub-basvuru-gonder">
                        Başvuruyu Tamamla
                    </button>
                </div>

                <!-- Gizlilik notu -->
                <p class="ub-bilgi-notu">
                    <?= $view->icon('check') ?>
                    Başvurularınız gizlilikle değerlendirilir ve yalnızca dernek faaliyetleri kapsamında kullanılır.
                </p>

            </form>
        </div>
    </div>
</section>
