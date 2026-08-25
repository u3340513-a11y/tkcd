<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * Üye Bilgi Güncelleme sayfası.
 *
 * İki adımlı akış:
 *   1. Telefon doğrulama formu (adim = 'telefon')
 *   2. Bilgi güncelleme formu  (adim = 'guncelleme')
 *
 * @var PhpViewRenderer      $view
 * @var SeoMeta              $seo
 * @var array<string, mixed> $site
 * @var string               $adim         'telefon' | 'guncelleme'
 * @var string|null          $durum        'basarili' | 'bulunamadi' | 'hata' | null
 * @var int                  $captchaA
 * @var int                  $captchaB
 * @var string               $captchaToken
 * @var array|null           $uye          Üye bilgileri (adim=guncelleme'de)
 * @var string|null          $dogumGosterim
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

?>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  HERO                                                ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="bg-hero" aria-labelledby="bg-hero-baslik">
    <div class="kapsayici">
        <h1 class="bg-hero__baslik" id="bg-hero-baslik">
            Bilgi <span>Güncelleme</span>
        </h1>
        <span class="bg-hero__ayrac" aria-hidden="true"></span>
        <p class="bg-hero__aciklama">
            Değerli üyemiz, aşağıdaki formu kullanarak doğum tarihi ve ikamet bilgilerinizi
            güncelleyebilirsiniz. Bu bilgiler derneğimizin sizlere daha iyi hizmet verebilmesi
            için kullanılacaktır.
        </p>
    </div>
</section>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  ADIM GÖSTERGESİ                                    ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<div class="bg-surec" aria-label="Güncelleme süreci adımları">
    <span class="bg-surec__adim <?= $adim === 'telefon' ? 'bg-surec__adim--aktif' : '' ?>">
        <?= $view->icon('phone') ?>
        Cep telefonunuzu doğrulayın
    </span>
    <span class="bg-surec__ayrac" aria-hidden="true">—</span>
    <span class="bg-surec__adim <?= ($adim ?? '') === 'guncelleme' ? 'bg-surec__adim--aktif' : '' ?>">
        <?= $view->icon('edit') ?>
        Bilgilerinizi güncelleyin
    </span>
</div>

<!-- ╔══════════════════════════════════════════════════════╗ -->
<!-- ║  DURUM BİLDİRİLERİ                                  ║ -->
<!-- ╚══════════════════════════════════════════════════════╝ -->
<section class="bg-alan" aria-labelledby="bg-form-baslik">
    <div class="kapsayici">
        <div class="bg-kutu">

            <?php if (($durum ?? null) === 'basarili'): ?>
            <div class="bg-bildiri bg-bildiri--basarili" role="alert">
                <?= $view->icon('check-circle') ?>
                <div>
                    <strong>Bilgileriniz Güncellendi</strong>
                    <p>Doğum tarihi ve ikamet bilgileriniz başarıyla kaydedilmiştir. Teşekkür ederiz.</p>
                </div>
            </div>
            <?php elseif (($durum ?? null) === 'bulunamadi'): ?>
            <div class="bg-bildiri bg-bildiri--uyari" role="alert">
                <?= $view->icon('alert-triangle') ?>
                <div>
                    <strong>Telefon Numarası Bulunamadı</strong>
                    <p>Girdiğiniz telefon numarası sistemimizde onaylı bir üye kaydında bulunamadı. Lütfen üyelik kaydınızdaki telefon numarasını girdiğinizden emin olun.</p>
                </div>
            </div>
            <?php elseif (($durum ?? null) === 'hata'): ?>
            <div class="bg-bildiri bg-bildiri--hata" role="alert">
                <?= $view->icon('alert-circle') ?>
                <div>
                    <strong>Bir Hata Oluştu</strong>
                    <p>Lütfen bilgilerinizi kontrol edip tekrar deneyin. Sorun devam ederse bizimle iletişime geçin.</p>
                </div>
            </div>
            <?php endif; ?>

            <?php if (($adim ?? 'telefon') === 'telefon' && ($durum ?? null) !== 'basarili'): ?>
            <!-- ╔══════════════════════════════════════════════════════╗ -->
            <!-- ║  ADIM 1: TELEFON DOĞRULAMA FORMU                    ║ -->
            <!-- ╚══════════════════════════════════════════════════════╝ -->
            <h2 class="bg-form-baslik" id="bg-form-baslik">
                <?= $view->icon('phone') ?>
                Telefon Doğrulama
            </h2>
            <p class="bg-form-aciklama">
                Üyelik kaydınızda kullandığınız cep telefonu numaranızı girin. Sisteme kayıtlı
                numaranız doğrulandıktan sonra bilgilerinizi güncelleyebilirsiniz.
            </p>

            <form action="/bilgi-guncelleme/dogrula" method="POST" class="bg-form" id="bgTelefonForm" autocomplete="off">
                <div class="bg-alan-grubu">
                    <label for="telefon" class="bg-etiket">
                        <?= $view->icon('phone') ?>
                        Cep Telefonu <span class="bg-zorunlu">*</span>
                    </label>
                    <input
                        type="tel"
                        id="telefon"
                        name="telefon"
                        class="bg-girdi"
                        placeholder="05XX XXX XX XX"
                        maxlength="14"
                        required
                        autocomplete="tel"
                    >
                </div>

                <!-- Matematik doğrulama -->
                <div class="bg-captcha">
                    <label class="bg-etiket">
                        <?= $view->icon('shield') ?>
                        Güvenlik Doğrulaması <span class="bg-zorunlu">*</span>
                    </label>
                    <div class="bg-captcha__icerik">
                        <span class="bg-captcha__soru">
                            <strong><?= $captchaA ?></strong> + <strong><?= $captchaB ?></strong> = ?
                        </span>
                        <input
                            type="text"
                            name="captcha_answer"
                            class="bg-girdi bg-captcha__girdi"
                            inputmode="numeric"
                            maxlength="3"
                            required
                            autocomplete="off"
                            placeholder="?"
                        >
                    </div>
                    <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
                    <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
                    <input type="hidden" name="captcha_token" value="<?= $view->e($captchaToken) ?>">
                </div>

                <button type="submit" class="bg-gonder" id="bgTelefonGonder">
                    <?= $view->icon('arrow-right') ?>
                    Devam Et
                </button>
            </form>

            <?php elseif (($adim ?? '') === 'guncelleme'): ?>
            <!-- ╔══════════════════════════════════════════════════════╗ -->
            <!-- ║  ADIM 2: BİLGİ GÜNCELLEME FORMU                    ║ -->
            <!-- ╚══════════════════════════════════════════════════════╝ -->
            <?php
                $uyeAdi      = $view->e($uye['adi_soyadi'] ?? '');
                $uyeId       = (int) ($uye['id'] ?? 0);
                $uyeTelefon  = $uye['telefon'] ?? '';
                $mevcutIl    = $uye['ikamet_ili'] ?? '';
                $mevcutIlce  = $uye['ikamet_ilcesi'] ?? '';

                // HMAC hash: üye kimlik doğrulaması
                $secret      = \App\Core\Env::string('RECAPTCHA_SECRET_KEY');
                if ($secret === '') { $secret = \App\Core\Env::string('DB_PASSWORD'); }
                if ($secret === '') { $secret = 'tkcd-fallback-secret-2024'; }
                $telefonHash = hash_hmac('sha256', $uyeId . ':' . $uyeTelefon, $secret);
            ?>

            <div class="bg-karsilama">
                <div class="bg-karsilama__ikon">
                    <?= $view->icon('user-check') ?>
                </div>
                <div>
                    <h2 class="bg-karsilama__baslik">Hoş geldiniz, <?= $uyeAdi ?></h2>
                    <p class="bg-karsilama__aciklama">Aşağıdaki bilgilerinizi güncelleyebilirsiniz.</p>
                </div>
            </div>

            <form action="/bilgi-guncelleme" method="POST" class="bg-form" id="bgGuncellemeForm" autocomplete="off">
                <input type="hidden" name="uye_id" value="<?= $uyeId ?>">
                <input type="hidden" name="telefon_hash" value="<?= $view->e($telefonHash) ?>">

                <!-- Doğum Tarihi -->
                <div class="bg-alan-grubu">
                    <label for="dogum_tarihi" class="bg-etiket">
                        <?= $view->icon('calendar') ?>
                        Doğum Tarihi <span class="bg-zorunlu">*</span>
                    </label>
                    <input
                        type="text"
                        id="dogum_tarihi"
                        name="dogum_tarihi"
                        class="bg-girdi"
                        placeholder="GG/AA/YYYY"
                        maxlength="10"
                        required
                        autocomplete="bday"
                        value="<?= $view->e($dogumGosterim ?? '') ?>"
                    >
                    <span class="bg-ipucu">Örnek: 25/08/1995</span>
                </div>

                <!-- İkamet İli -->
                <div class="bg-alan-grubu">
                    <label for="ikamet_ili" class="bg-etiket">
                        <?= $view->icon('map-pin') ?>
                        İkamet Ettiğiniz İl <span class="bg-zorunlu">*</span>
                    </label>
                    <select id="ikamet_ili" name="ikamet_ili" class="bg-girdi" required>
                        <option value="">Seçiniz</option>
                        <?php foreach ($iller as $il): ?>
                            <option value="<?= $view->e($il) ?>" <?= ($mevcutIl === $il) ? 'selected' : '' ?>>
                                <?= $view->e($il) ?>
                            </option>
                        <?php endforeach; ?>
                    </select>
                </div>

                <!-- İkamet İlçesi -->
                <div class="bg-alan-grubu">
                    <label for="ikamet_ilcesi" class="bg-etiket">
                        <?= $view->icon('map') ?>
                        İkamet Ettiğiniz İlçe <span class="bg-zorunlu">*</span>
                    </label>
                    <select id="ikamet_ilcesi" name="ikamet_ilcesi" class="bg-girdi" required>
                        <option value="">Önce il seçiniz</option>
                    </select>
                    <?php if (!empty($mevcutIlce)): ?>
                        <input type="hidden" id="mevcut_ilce" value="<?= $view->e($mevcutIlce) ?>">
                    <?php endif; ?>
                </div>

                <!-- Matematik doğrulama -->
                <div class="bg-captcha">
                    <label class="bg-etiket">
                        <?= $view->icon('shield') ?>
                        Güvenlik Doğrulaması <span class="bg-zorunlu">*</span>
                    </label>
                    <div class="bg-captcha__icerik">
                        <span class="bg-captcha__soru">
                            <strong><?= $captchaA ?></strong> + <strong><?= $captchaB ?></strong> = ?
                        </span>
                        <input
                            type="text"
                            name="captcha_answer"
                            class="bg-girdi bg-captcha__girdi"
                            inputmode="numeric"
                            maxlength="3"
                            required
                            autocomplete="off"
                            placeholder="?"
                        >
                    </div>
                    <input type="hidden" name="captcha_a" value="<?= $captchaA ?>">
                    <input type="hidden" name="captcha_b" value="<?= $captchaB ?>">
                    <input type="hidden" name="captcha_token" value="<?= $view->e($captchaToken) ?>">
                </div>

                <button type="submit" class="bg-gonder" id="bgGuncellemeGonder">
                    <?= $view->icon('check') ?>
                    Güncelle
                </button>
            </form>
            <?php endif; ?>

        </div>
    </div>
</section>
