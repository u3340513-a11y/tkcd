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
 * @var string               $captchaA      Matematik sorusu: ilk sayı
 * @var string               $captchaB      Matematik sorusu: ikinci sayı
 * @var string               $captchaToken  HMAC imzalı doğrulama token'ı
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
$calismaSekilleri = ['Kadrolu', 'Yarı Zamanlı', 'Sözleşmeli', 'Emekli Kamu Çalışanı'];

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
                    <strong>Teşekkürler! Başvurunuz Başarıyla Alındı 🎉</strong>
                    <p>Üyelik başvurunuz sistemimize kaydedildi. Yönetim kurulumuz başvurunuzu inceleyecek ve en kısa sürede sizinle iletişime geçecektir.</p>
                    <p style="margin-top:0.4rem;font-size:0.9rem">Başvurunuz ile ilgili sorularınız için <a href="/iletisim">iletişim sayfamızdan</a> bize ulaşabilirsiniz.</p>
                </div>
            </div>
            <?php elseif ($durum === 'telefon_kayitli'): ?>
            <div class="ub-bildiri ub-bildiri--uyari" role="alert">
                <?= $view->icon('alert-triangle') ?>
                <div>
                    <strong>Bu Telefon Numarası Zaten Kayıtlı</strong>
                    <p>Girdiğiniz telefon numarası sistemimizde kayıtlı bulunmaktadır. Daha önce başvuru yaptıysanız tekrar göndermenize gerek yoktur.</p>
                    <p style="margin-top:0.4rem;font-size:0.9rem">Hata olduğunu düşünüyorsanız <a href="mailto:<?= $view->e($site['email'] ?? 'info@trabzonlukamucalisanlaridernegi.com') ?>">bizimle iletişime geçin</a>.</p>
                </div>
            </div>
            <?php elseif ($durum === 'kisi_kayitli'): ?>
            <div class="ub-bildiri ub-bildiri--uyari" role="alert">
                <?= $view->icon('alert-triangle') ?>
                <div>
                    <strong>Bu Kişi Zaten Kayıtlı</strong>
                    <p>Girdiğiniz ad-soyad ve doğum tarihi ile eşleşen bir kayıt sistemimizde zaten bulunmaktadır. Daha önce başvuru yaptıysanız tekrar göndermenize gerek yoktur.</p>
                    <p style="margin-top:0.4rem;font-size:0.9rem">Hata olduğunu düşünüyorsanız <a href="mailto:<?= $view->e($site['email'] ?? 'info@trabzonlukamucalisanlaridernegi.com') ?>">bizimle iletişime geçin</a>.</p>
                </div>
            </div>
            <?php elseif ($durum === 'eposta_kayitli'): ?>
            <div class="ub-bildiri ub-bildiri--uyari" role="alert">
                <?= $view->icon('alert-triangle') ?>
                <div>
                    <strong>Bu E-posta Adresi Zaten Kayıtlı</strong>
                    <p>Girdiğiniz e-posta adresi ile daha önce başvuru yapılmıştır. Tekrar göndermenize gerek yoktur.</p>
                    <p style="margin-top:0.4rem;font-size:0.9rem">Hata olduğunu düşünüyorsanız <a href="mailto:<?= $view->e($site['email'] ?? 'info@trabzonlukamucalisanlaridernegi.com') ?>">bizimle iletişime geçin</a>.</p>
                </div>
            </div>
            <?php elseif ($durum === 'hata'): ?>
            <div class="ub-bildiri ub-bildiri--hata" role="alert">
                <?= $view->icon('alert-circle') ?>
                <div>
                    <strong>Gönderim Başarısız</strong>
                    <p>Başvurunuz gönderilemedi. Lütfen tüm zorunlu alanları doğru doldurup tekrar deneyin.</p>
                    <?php
                    $hataMesaji = trim((string) ($_GET['hata_mesaji'] ?? ''));
                    if ($hataMesaji !== ''):
                    ?>
                    <p style="font-size:0.85rem;color:#666;margin-top:0.5rem">Detay: <?= htmlspecialchars($hataMesaji, ENT_QUOTES, 'UTF-8') ?></p>
                    <?php endif; ?>
                </div>
            </div>
            <?php endif; ?>

            <?php if ($durum !== 'basarili'): ?>


            <form
                class="ub-form"
                id="uyelik-basvuru-formu"
                method="POST"
                action="/uye-ol"
                novalidate
                aria-label="Üyelik başvuru formu"
            >
                <!-- Adı / Soyadı -->
                <div class="ub-form__ad-soyad-grup">
                    <div class="ub-form__alan">
                        <label class="ub-form__etiket" for="ub-ad">
                            Adı <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                        </label>
                        <input
                            class="ub-form__girdi"
                            type="text"
                            id="ub-ad"
                            name="ad"
                            placeholder="Adınız"
                            autocomplete="given-name"
                            minlength="2"
                            maxlength="60"
                            pattern="[A-Za-z\u00c7\u00e7\u011e\u011f\u0130\u0131\u00d6\u00f6\u015e\u015f\u00dc\u00fc\s]+"
                            required
                            aria-required="true"
                            aria-describedby="ub-ad-ipucu"
                            spellcheck="false"
                        >
                        <span id="ub-ad-ipucu" class="gorsel-gizli">
                            Yalnızca harf kullanabilirsiniz; rakam ve özel karakter kabul edilmez.
                        </span>
                    </div>
                    <div class="ub-form__alan">
                        <label class="ub-form__etiket" for="ub-soyad">
                            Soyadı <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                        </label>
                        <input
                            class="ub-form__girdi"
                            type="text"
                            id="ub-soyad"
                            name="soyad"
                            placeholder="Soyadınız"
                            autocomplete="family-name"
                            minlength="2"
                            maxlength="60"
                            pattern="[A-Za-z\u00c7\u00e7\u011e\u011f\u0130\u0131\u00d6\u00f6\u015e\u015f\u00dc\u00fc\s]+"
                            required
                            aria-required="true"
                            aria-describedby="ub-soyad-ipucu"
                            spellcheck="false"
                        >
                        <span id="ub-soyad-ipucu" class="gorsel-gizli">
                            Yalnızca harf kullanabilirsiniz; rakam ve özel karakter kabul edilmez.
                        </span>
                    </div>
                </div>

                <!-- Telefon Numarası -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-telefon">
                        Telefon Numarası <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <div class="ub-form__telefon-grup">
                        <span class="ub-form__telefon-prefix" aria-hidden="true">0</span>
                        <input
                            class="ub-form__girdi"
                            type="tel"
                            id="ub-telefon"
                            name="telefon"
                            placeholder="5XX XXX XX XX"
                            autocomplete="tel-national"
                            minlength="10"
                            maxlength="10"
                            pattern="[0-9]{10}"
                            inputmode="numeric"
                            required
                            aria-required="true"
                            aria-describedby="ub-telefon-ipucu"
                            spellcheck="false"
                            autocorrect="off"
                        >
                    </div>
                    <span id="ub-telefon-ipucu" class="gorsel-gizli">
                        Başında 0 olmadan 10 rakam giriniz (örn: 0532 123 45 67 → 5321234567).
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

                <!-- İkamet Edilen İlçe -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-ikamet-ilce">
                        İkamet Edilen İlçe <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-ikamet-ilce"
                        name="ikamet_ilcesi"
                        required
                        aria-required="true"
                    >
                        <option value="">-- Önce İl Seçiniz --</option>
                    </select>
                </div>

                <!-- Trabzon İlçesi (Nüfusa Kayıtlı) -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-trabzon-ilce">
                        Trabzon İlçesi (Nüfusa Kayıtlı) <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-trabzon-ilce"
                        name="trabzon_ilce"
                        required
                        aria-required="true"
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
                        Çalıştığı Kurum <span style="color:var(--bordo-500)">*</span>
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="text"
                        id="ub-kurum"
                        name="kurum"
                        placeholder="Örn: Maliye Bakanlığı"
                        maxlength="200"
                        autocomplete="organization"
                        required
                        aria-required="true"
                    >
                </div>

                <!-- Görev / Ünvan -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-gorev">
                        Görev / Ünvan <span style="color:var(--bordo-500)">*</span>
                    </label>
                    <input
                        class="ub-form__girdi"
                        type="text"
                        id="ub-gorev"
                        name="gorev"
                        placeholder="Örn: Mühendis"
                        maxlength="120"
                        autocomplete="organization-title"
                        required
                        aria-required="true"
                    >
                </div>

                <!-- Cinsiyet -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-cinsiyet">
                        Cinsiyet <span style="color:var(--bordo-500)">*</span>
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-cinsiyet"
                        name="cinsiyet"
                        required
                        aria-required="true"
                    >
                        <option value="">-- Seçiniz --</option>
                        <option value="Erkek">Erkek</option>
                        <option value="Kadın">Kadın</option>
                    </select>
                </div>

                <!-- Çalışma Şekli -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-calisma-sekli">
                        Çalışma Şekli <span style="color:var(--bordo-500)">*</span>
                    </label>
                    <select
                        class="ub-form__girdi ub-form__secim"
                        id="ub-calisma-sekli"
                        name="calisma_sekli"
                        required
                        aria-required="true"
                    >
                        <option value="">-- Seçiniz --</option>
                        <?php foreach ($calismaSekilleri as $sekil): ?>
                        <option value="<?= $view->e($sekil) ?>"><?= $view->e($sekil) ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- KVKK Onayları -->
                <div class="ub-kvkk-grup">

                    <!-- 1. Aydınlatma (zorunlu) -->
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
                            <strong>KVKK Aydınlatma Metni</strong> <span style="color:var(--bordo-500)">*</span><br>
                            <button type="button" class="ub-kvkk__link" id="ub-kvkk-modal-ac" aria-haspopup="dialog">
                                KVKK Aydınlatma Metni
                            </button>'ni okudum ve kişisel verilerimin işlenmesi hakkında bilgilendirildim.
                        </label>
                    </div>

                    <!-- 2. Açık rıza (isteğe bağlı) -->
                    <div class="ub-kvkk">
                        <input
                            class="ub-kvkk__kutu"
                            type="checkbox"
                            id="ub-acik-riza"
                            name="acik_riza"
                            value="1"
                        >
                        <label class="ub-kvkk__etiket" for="ub-acik-riza">
                            Kişisel verilerimin tarafıma sunulan Açık Rıza Metni'nde belirtilen amaçlarla işlenmesine açık rıza veriyorum.
                        </label>
                    </div>

                    <!-- 3. E-posta bilgilendirme (isteğe bağlı) -->
                    <div class="ub-kvkk">
                        <input
                            class="ub-kvkk__kutu"
                            type="checkbox"
                            id="ub-eposta-riza"
                            name="eposta_riza"
                            value="1"
                        >
                        <label class="ub-kvkk__etiket" for="ub-eposta-riza">
                            Dernek tarafından gerçekleştirilecek etkinlik, eğitim ve faaliyetler hakkında <strong>e-posta yoluyla bilgilendirme</strong> yapılmasına açık rıza veriyorum.
                        </label>
                    </div>

                    <!-- 4. SMS bilgilendirme (isteğe bağlı) -->
                    <div class="ub-kvkk">
                        <input
                            class="ub-kvkk__kutu"
                            type="checkbox"
                            id="ub-sms-riza"
                            name="sms_riza"
                            value="1"
                        >
                        <label class="ub-kvkk__etiket" for="ub-sms-riza">
                            Dernek tarafından gerçekleştirilecek etkinlik, eğitim ve faaliyetler hakkında <strong>SMS yoluyla bilgilendirme</strong> yapılmasına açık rıza veriyorum.
                        </label>
                    </div>
                </div>

                <!-- KVKK Modal -->
                <dialog class="ub-kvkk-modal" id="ub-kvkk-modal" aria-labelledby="ub-kvkk-modal-baslik" aria-modal="true">
                    <div class="ub-kvkk-modal__ic">
                        <div class="ub-kvkk-modal__ust">
                            <h2 class="ub-kvkk-modal__baslik" id="ub-kvkk-modal-baslik">KVKK Aydınlatma Metni</h2>
                            <button type="button" class="ub-kvkk-modal__kapat" id="ub-kvkk-modal-kapat" aria-label="Kapat">✕</button>
                        </div>
                        <div class="ub-kvkk-modal__icerik" tabindex="0">
                            <p class="ub-kvkk-modal__ust-baslik"><strong>TRABZONLU KAMU ÇALIŞANLARI DERNEĞİ — KİŞİSEL VERİLERİN KORUNMASI KANUNU KAPSAMINDA AYDINLATMA METNİ</strong></p>

                            <h3>1. Veri Sorumlusu</h3>
                            <p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verileriniz, veri sorumlusu sıfatıyla <strong>Trabzonlu Kamu Çalışanları Derneği</strong> tarafından işlenmektedir.</p>

                            <h3>2. İşlenen Kişisel Veriler</h3>
                            <p>İnternet sitemizi ziyaret etmeniz, iletişim formu aracılığıyla bizimle iletişime geçmeniz, etkinlik veya faaliyetlere başvurmanız ya da Dernek ile herhangi bir şekilde iletişim kurmanız halinde; ad ve soyad, telefon numarası, e-posta adresi, meslek ve görev bilgileri, dernek üyeliğine ilişkin bilgiler, başvuru ve iletişim içerikleri, IP adresi, log kayıtları ve çerezler aracılığıyla elde edilen bilgiler işlenebilecektir.</p>

                            <h3>3. Kişisel Verilerin İşlenme Amaçları</h3>
                            <p>Kişisel verileriniz; dernek faaliyetlerinin yürütülmesi, üyelik ve başvuru süreçlerinin yürütülmesi, iletişim faaliyetlerinin yürütülmesi, etkinlik ve organizasyonların gerçekleştirilmesi, internet sitesinin güvenliğinin sağlanması ve hukuki yükümlülüklerin yerine getirilmesi amaçlarıyla işlenebilecektir.</p>

                            <h3>4. Hukuki Sebepler</h3>
                            <p>Kişisel verileriniz; kanunlarda açıkça öngörülmesi, hukuki yükümlülüğün yerine getirilmesi, sözleşmenin kurulması veya ifasıyla doğrudan ilgili olması, meşru menfaat ve/veya açık rıza hukuki sebeplerine dayanılarak işlenebilecektir.</p>

                            <h3>5. Toplanma Yöntemi</h3>
                            <p>Kişisel verileriniz; internet sitesi formları, e-posta, telefon, elektronik ve fiziki başvurular, üyelik işlemleri ve çerezler aracılığıyla otomatik veya otomatik olmayan yöntemlerle toplanabilecektir.</p>

                            <h3>6. Aktarılması</h3>
                            <p>Kişisel verileriniz; yetkili kamu kurum ve kuruluşlarına, hukuki/mali/teknik hizmet alınan kuruluşlara ve internet sitesi altyapı hizmet sağlayıcılarına, KVKK'nın 8. ve 9. maddelerindeki şartlar çerçevesinde aktarılabilecektir.</p>

                            <h3>7. Haklarınız (KVKK Md. 11)</h3>
                            <p>Kişisel verilerinizin işlenip işlenmediğini öğrenme, işlenmişse bilgi talep etme, aktarıldığı üçüncü kişileri öğrenme, düzeltilmesini ve silinmesini isteme, otomatik sistemler vasıtasıyla aleyhinize sonuç çıkmasına itiraz etme ve zarara uğramanız halinde tazminat talep etme haklarına sahipsiniz.</p>

                            <h3>8. Başvuru</h3>
                            <p>Haklarınıza ilişkin taleplerinizi yazılı başvuru, e-posta veya KEP aracılığıyla Derneğimize iletebilirsiniz.</p>
                        </div>
                        <div class="ub-kvkk-modal__alt">
                            <a href="/kvkk-aydinlatma-metni" target="_blank" class="ub-kvkk-modal__tam-metin">Tam Metni Görüntüle →</a>
                            <button type="button" class="ub-kvkk-modal__onayla" id="ub-kvkk-modal-onayla">Okudum, Onaylıyorum</button>
                        </div>
                    </div>
                </dialog>

                <!-- Matematik Doğrulama -->
                <div class="ub-form__alan">
                    <label class="ub-form__etiket" for="ub-captcha-answer">
                        Güvenlik Sorusu <span class="ub-form__zorunlu" aria-label="zorunlu">*</span>
                    </label>
                    <div class="ub-math-captcha">
                        <span class="ub-math-captcha__soru">
                            <?= (int)$captchaA ?> + <?= (int)$captchaB ?> = ?
                        </span>
                        <input
                            class="ub-form__girdi ub-math-captcha__girdi"
                            type="number"
                            id="ub-captcha-answer"
                            name="captcha_answer"
                            inputmode="numeric"
                            min="2"
                            max="24"
                            autocomplete="off"
                            placeholder="Cevabınız"
                            aria-required="true"
                            aria-describedby="ub-captcha-hata"
                        >
                    </div>
                    <span class="ub-form__hata" id="ub-captcha-hata" role="alert"></span>
                    <input type="hidden" name="captcha_a"     value="<?= (int)$captchaA ?>">
                    <input type="hidden" name="captcha_b"     value="<?= (int)$captchaB ?>">
                    <input type="hidden" name="captcha_token" value="<?= $view->e($captchaToken) ?>">
                </div>

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
            <?php endif; /* durum !== basarili */ ?>
        </div>
    </div>
</section>

<?php if ($durum !== null): ?>
<script>
/**
 * Durum bildiri mesajına otomatik scroll.
 * PRG sonrası sayfa yüklenince bildiri kutusunu görünür alana taşır.
 */
(function () {
    'use strict';
    var bildiri = document.querySelector('.ub-bildiri');
    if (bildiri) {
        bildiri.scrollIntoView({ behavior: 'smooth', block: 'center' });
    }
})();
</script>
<?php endif; ?>
