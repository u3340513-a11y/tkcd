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
            <!-- ⚠ DOLDURMA UYARISI -->
            <div class="ub-doldurma-uyarisi" role="note" aria-label="Önemli form uyarıları">
                <div class="ub-doldurma-uyarisi__baslik">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24"
                         fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round"
                         stroke-linejoin="round" aria-hidden="true">
                        <path d="M10.29 3.86L1.82 18a2 2 0 0 0 1.71 3h16.94a2 2 0 0 0 1.71-3L13.71 3.86a2 2 0 0 0-3.42 0z"/>
                        <line x1="12" y1="9" x2="12" y2="13"/>
                        <line x1="12" y1="17" x2="12.01" y2="17"/>
                    </svg>
                    <strong>Formu doldurmadan önce lütfen okuyun!</strong>
                </div>
                <ul class="ub-doldurma-uyarisi__liste">
                    <li>
                        <span class="ub-doldurma-uyarisi__ikon">✗</span>
                        <span><strong>Eksik bilgi bırakmayın</strong> — Tüm zorunlu alanlar (<span style="color:#c0392b;font-weight:700">*</span>) eksiksiz doldurulmalıdır.</span>
                    </li>
                    <li>
                        <span class="ub-doldurma-uyarisi__ikon">✗</span>
                        <span><strong>Kısaltma kullanmayın</strong> — "Meh. Yılmaz", "M. Yılmaz" gibi kısaltmalı isimler <u>kabul edilmez</u>.</span>
                    </li>
                    <li>
                        <span class="ub-doldurma-uyarisi__ikon">✗</span>
                        <span><strong>Sadece adınızı yazmayın</strong> — Ad ve soyadınızın <u>tamamını</u> yazın. Örnek: <em>Mehmet Yılmaz</em></span>
                    </li>
                    <li>
                        <span class="ub-doldurma-uyarisi__ikon">✗</span>
                        <span><strong>Hatalı bilgi girmeyin</strong> — Telefon, e-posta ve kurum bilgilerinizin doğru olduğundan emin olun; onay sürecinde bu bilgiler üzerinden iletişim kurulacaktır.</span>
                    </li>
                </ul>
            </div>
            <style>
            .ub-doldurma-uyarisi {
                background: linear-gradient(135deg, #fff5f5 0%, #fff0f0 100%);
                border: 2px solid #e74c3c;
                border-left: 5px solid #c0392b;
                border-radius: 10px;
                padding: 1rem 1.25rem 1rem 1.1rem;
                margin-bottom: 1.6rem;
                box-shadow: 0 3px 12px rgba(231,76,60,0.12);
            }
            .ub-doldurma-uyarisi__baslik {
                display: flex;
                align-items: center;
                gap: 0.55rem;
                color: #c0392b;
                font-size: 1rem;
                margin-bottom: 0.75rem;
            }
            .ub-doldurma-uyarisi__baslik svg { flex-shrink: 0; }
            .ub-doldurma-uyarisi__liste {
                list-style: none;
                margin: 0;
                padding: 0;
                display: flex;
                flex-direction: column;
                gap: 0.5rem;
            }
            .ub-doldurma-uyarisi__liste li {
                display: flex;
                align-items: flex-start;
                gap: 0.5rem;
                font-size: 0.88rem;
                color: #4a1010;
                line-height: 1.5;
            }
            .ub-doldurma-uyarisi__ikon {
                flex-shrink: 0;
                width: 18px;
                height: 18px;
                background: #c0392b;
                color: #fff;
                border-radius: 50%;
                display: flex;
                align-items: center;
                justify-content: center;
                font-size: 0.68rem;
                font-weight: 900;
                margin-top: 1px;
                line-height: 1;
                text-align: center;
            }
            @media (max-width: 480px) {
                .ub-doldurma-uyarisi { padding: 0.85rem 0.9rem; }
                .ub-doldurma-uyarisi__baslik { font-size: 0.92rem; }
                .ub-doldurma-uyarisi__liste li { font-size: 0.82rem; }
            }
            </style>

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
                    </label>
                </div>

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
