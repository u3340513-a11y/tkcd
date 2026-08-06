/**
 * Üyelik Başvuru Formu — İstemci Tarafı Doğrulama
 *
 * Neden: novalidate ile tarayıcı yerleşik baloncukları devre dışı bırakıldı;
 * bu betik özel, Türkçe hata mesajları ve gerçek zamanlı karakter
 * filtrelemesiyle tutarlı bir UX sağlar.
 *
 * Katmanlar:
 *   1. Gerçek zamanlı filtre — yanlış karakterler hiç girilemiyor
 *   2. Blur doğrulaması    — alandan çıkınca hata gösterilir
 *   3. Submit doğrulaması  — tüm alanlar son kez kontrol edilir
 *
 * Güvenlik notu: Bu doğrulama yalnızca UX içindir. Sunucu tarafı
 * doğrulama (PHP) birincil güvenlik katmanıdır.
 */

(function () {
  'use strict';

  // -----------------------------------------------------------------------
  // Yardımcı: Türkçe dahil harfler + boşluk için regex
  // -----------------------------------------------------------------------
  const HARF_REGEX   = /^[A-Za-zÇçĞğİıÖöŞşÜü\s]+$/;
  const RAKAM_REGEX  = /^\d+$/;
  const EPOSTA_REGEX = /^[^\s@]+@[^\s@]+\.[^\s@]{2,}$/;

  // -----------------------------------------------------------------------
  // Hata mesajları
  // -----------------------------------------------------------------------
  const HATALAR = {
    adSoyad: {
      bos:      'Adı Soyadı alanı zorunludur.',
      kisaMin:  'En az 3 karakter giriniz.',
      gecersiz: 'Yalnızca harf ve boşluk kullanılabilir (rakam ve özel karakter kabul edilmez).',
    },
    telefon: {
      bos:      'Telefon numarası zorunludur.',
      rakam:    'Yalnızca rakam giriniz.',
      uzunluk:  'Telefon numarası tam 9 rakam olmalıdır (05 hariç).',
    },
    eposta: {
      bos:      'E-Posta adresi zorunludur.',
      gecersiz: 'Geçerli bir e-posta adresi giriniz (örn: ad@kurum.gov.tr).',
    },
    dogumTarihi: {
      bos:      'Doğum tarihi zorunludur.',
      yasMin:   'Başvuru için 18 yaşını doldurmuş olmanız gerekmektedir.',
      yasMax:   'Lütfen geçerli bir doğum tarihi giriniz.',
    },
    ikametIl: {
      bos: 'İkamet edilen ili seçiniz.',
    },
    trabzonIlce: {
      bos: 'Trabzon ilçesini seçiniz.',
    },
    captcha: {
      bos:      'Güvenlik sorusunu yanıtlayınız.',
      gecersiz: 'Yanlış cevap, lütfen tekrar deneyin.',
    },
    kvkk: {
      onay: 'KVKK metnini okuyup onaylamanız gerekmektedir.',
    },
  };

  // -----------------------------------------------------------------------
  // DOM referansları
  // -----------------------------------------------------------------------
  const form        = document.getElementById('uyelik-basvuru-formu');
  if (!form) return;

  const elAdSoyad     = document.getElementById('ub-ad-soyad');
  const elTelefon     = document.getElementById('ub-telefon');
  const elEposta      = document.getElementById('ub-eposta');
  const elDogum       = document.getElementById('ub-dogum-tarihi');
  const elIl          = document.getElementById('ub-ikamet-il');
  const elTrabzonIlce = document.getElementById('ub-trabzon-ilce');
  const elKvkk        = document.getElementById('ub-kvkk');
  const elCaptcha     = document.getElementById('ub-captcha-answer');
  const elCaptchaA    = /** @type {HTMLInputElement|null} */ (form.querySelector('[name="captcha_a"]'));
  const elCaptchaB    = /** @type {HTMLInputElement|null} */ (form.querySelector('[name="captcha_b"]'));

  // -----------------------------------------------------------------------
  // Hata göster / temizle
  // -----------------------------------------------------------------------

  /**
   * Alana bağlı hata mesajını ekrana getirir ve ARIA ilişkilendirir.
   *
   * @param {HTMLElement} el  - Form elemanı
   * @param {string}      msg - Gösterilecek mesaj
   */
  function hataGoster(el, msg) {
    const hataId  = el.id + '-hata';
    let   hataEl  = document.getElementById(hataId);

    if (!hataEl) {
      hataEl = document.createElement('span');
      hataEl.id        = hataId;
      hataEl.className = 'ub-form__hata';
      hataEl.setAttribute('role', 'alert');
      el.closest('.ub-form__alan, .ub-kvkk')?.appendChild(hataEl);
    }

    hataEl.textContent = msg;
    el.classList.add('ub-form__girdi--hatali');
    el.classList.remove('ub-form__girdi--gecerli');
    el.setAttribute('aria-describedby', hataId);
    el.setAttribute('aria-invalid', 'true');
  }

  /**
   * Alanın hata durumunu temizler.
   *
   * @param {HTMLElement} el - Form elemanı
   */
  function hataSil(el) {
    const hataId = el.id + '-hata';
    const hataEl = document.getElementById(hataId);

    if (hataEl) hataEl.textContent = '';
    el.classList.remove('ub-form__girdi--hatali');
    el.classList.add('ub-form__girdi--gecerli');
    el.removeAttribute('aria-invalid');
  }

  // -----------------------------------------------------------------------
  // Doğrulama fonksiyonları — her biri true döndürürse alan geçerlidir
  // -----------------------------------------------------------------------

  function dogrulaAdSoyad() {
    const deger = elAdSoyad.value.trim();

    if (deger === '') {
      hataGoster(elAdSoyad, HATALAR.adSoyad.bos);
      return false;
    }
    if (deger.length < 3) {
      hataGoster(elAdSoyad, HATALAR.adSoyad.kisaMin);
      return false;
    }
    if (!HARF_REGEX.test(deger)) {
      hataGoster(elAdSoyad, HATALAR.adSoyad.gecersiz);
      return false;
    }
    hataSil(elAdSoyad);
    return true;
  }

  function dogrulaTelefon() {
    const deger = elTelefon.value.trim();

    if (deger === '') {
      hataGoster(elTelefon, HATALAR.telefon.bos);
      return false;
    }
    if (!RAKAM_REGEX.test(deger)) {
      hataGoster(elTelefon, HATALAR.telefon.rakam);
      return false;
    }
    if (deger.length !== 9) {
      hataGoster(elTelefon, HATALAR.telefon.uzunluk);
      return false;
    }
    hataSil(elTelefon);
    return true;
  }

  function dogrulaEposta() {
    const deger = elEposta.value.trim();

    if (deger === '') {
      hataGoster(elEposta, HATALAR.eposta.bos);
      return false;
    }
    if (!EPOSTA_REGEX.test(deger)) {
      hataGoster(elEposta, HATALAR.eposta.gecersiz);
      return false;
    }
    hataSil(elEposta);
    return true;
  }

  function dogrulaDogumTarihi() {
    const deger = (elDogum.value || '').trim();

    if (deger === '') {
      hataGoster(elDogum, HATALAR.dogumTarihi.bos);
      return false;
    }

    const parcalar = deger.split('-');
    if (parcalar.length !== 3) {
      hataGoster(elDogum, HATALAR.dogumTarihi.bos);
      return false;
    }

    const dogum    = new Date(Date.UTC(
      parseInt(parcalar[0], 10),
      parseInt(parcalar[1], 10) - 1,
      parseInt(parcalar[2], 10)
    ));
    const bugun  = new Date();
    const yasMin = new Date(Date.UTC(bugun.getFullYear() - 18, bugun.getMonth(), bugun.getDate()));
    const yasMax = new Date(Date.UTC(1930, 0, 1));

    if (dogum > yasMin) {
      hataGoster(elDogum, HATALAR.dogumTarihi.yasMin);
      return false;
    }
    if (dogum < yasMax) {
      hataGoster(elDogum, HATALAR.dogumTarihi.yasMax);
      return false;
    }

    hataSil(elDogum);
    return true;
  }

  function dogrulaIkametIl() {
    if (!elIl.value || elIl.value === '') {
      hataGoster(elIl, HATALAR.ikametIl.bos);
      return false;
    }
    hataSil(elIl);
    return true;
  }

  function dogrulaTrabzonIlce() {
    // Element DOM'da yoksa bu alan isteğe bağlıdır — doğrulamayı geç
    if (!elTrabzonIlce) return true;

    const seciliDeger = (elTrabzonIlce.value || '').trim();
    if (seciliDeger === '') {
      hataGoster(elTrabzonIlce, HATALAR.trabzonIlce.bos);
      return false;
    }
    hataSil(elTrabzonIlce);
    return true;
  }

  function dogrulaKvkk() {
    if (!elKvkk.checked) {
      const alan = elKvkk.closest('.ub-kvkk');
      let   hataEl = document.getElementById('ub-kvkk-hata');

      if (!hataEl) {
        hataEl = document.createElement('span');
        hataEl.id        = 'ub-kvkk-hata';
        hataEl.className = 'ub-form__hata';
        hataEl.setAttribute('role', 'alert');
        alan?.appendChild(hataEl);
      }
      hataEl.textContent = HATALAR.kvkk.onay;
      elKvkk.setAttribute('aria-invalid', 'true');
      return false;
    }

    const hataEl = document.getElementById('ub-kvkk-hata');
    if (hataEl) hataEl.textContent = '';
    elKvkk.removeAttribute('aria-invalid');
    return true;
  }

  // -----------------------------------------------------------------------
  // Gerçek zamanlı karakter filtreleme
  // -----------------------------------------------------------------------

  /**
   * Ad Soyad — rakam ve özel karakterleri anlık olarak engeller.
   * Türkçe karakterler (Ç,ğ,İ,ı,Ö,Ş,Ü vb.) geçerlidir.
   */
  if (elAdSoyad) {
    elAdSoyad.addEventListener('input', function () {
      const onceki = this.selectionStart ?? this.value.length;
      const temiz  = this.value.replace(/[^A-Za-zÇçĞğİıÖöŞşÜü\s]/g, '');

      if (this.value !== temiz) {
        this.value = temiz;
        // İmleç konumunu koru
        const konum = Math.max(0, onceki - (this.value.length - temiz.length + 1));
        this.setSelectionRange(konum, konum);
      }
    });

    elAdSoyad.addEventListener('blur', dogrulaAdSoyad);
  }

  /**
   * Telefon — rakam dışı her karakter anlık olarak silinir.
   */
  if (elTelefon) {
    elTelefon.addEventListener('input', function () {
      const temiz = this.value.replace(/\D/g, '').slice(0, 9);
      this.value  = temiz;
    });

    elTelefon.addEventListener('keydown', function (e) {
      // Kontrol tuşlarına izin ver (Backspace, Delete, Tab, Arrows vb.)
      const izinliTuslar = [
        'Backspace', 'Delete', 'Tab', 'Escape', 'Enter',
        'ArrowLeft', 'ArrowRight', 'ArrowUp', 'ArrowDown',
        'Home', 'End',
      ];
      if (izinliTuslar.includes(e.key)) return;

      // Ctrl/Cmd + A/C/V/X izni
      if ((e.ctrlKey || e.metaKey) && ['a', 'c', 'v', 'x'].includes(e.key.toLowerCase())) return;

      // Yalnızca 0-9 arası tuşlar
      if (!/^\d$/.test(e.key)) {
        e.preventDefault();
      }
    });

    elTelefon.addEventListener('paste', function (e) {
      e.preventDefault();
      const yapisTirilanMetin = (e.clipboardData || window.clipboardData).getData('text');
      const sadeceSayilar     = yapisTirilanMetin.replace(/\D/g, '').slice(0, 9);
      this.value = sadeceSayilar;
    });

    elTelefon.addEventListener('blur', dogrulaTelefon);
  }

  /**
   * E-Posta — blur'da doğrulama.
   */
  if (elEposta) {
    elEposta.addEventListener('blur', dogrulaEposta);
  }

  /**
   * Doğum Tarihi — blur'da yaş doğrulama.
   */
  if (elDogum) {
    elDogum.addEventListener('change', dogrulaDogumTarihi);
  }

  /**
   * İkamet İl — change'de doğrulama.
   */
  if (elIl) {
    elIl.addEventListener('change', dogrulaIkametIl);
  }

  /**
   * Trabzon İlçesi — change'de doğrulama.
   */
  if (elTrabzonIlce) {
    elTrabzonIlce.addEventListener('change', dogrulaTrabzonIlce);
  }

  /**
   * KVKK — change'de doğrulama.
   */
  if (elKvkk) {
    elKvkk.addEventListener('change', dogrulaKvkk);
  }

  // -----------------------------------------------------------------------
  // Matematik doğrulama
  // -----------------------------------------------------------------------

  function dogrulaCaptcha() {
    if (!elCaptcha) return true; // Alan yoksa geç

    const deger = elCaptcha.value.trim();
    const hataEl = document.getElementById('ub-captcha-hata');

    function captchaHata(mesaj) {
      elCaptcha.setAttribute('aria-invalid', 'true');
      if (hataEl) hataEl.textContent = mesaj;
      return false;
    }

    if (deger === '') {
      return captchaHata(HATALAR.captcha.bos);
    }

    const a = parseInt((elCaptchaA && elCaptchaA.value) || '0', 10);
    const b = parseInt((elCaptchaB && elCaptchaB.value) || '0', 10);

    if (parseInt(deger, 10) !== (a + b)) {
      return captchaHata(HATALAR.captcha.gecersiz);
    }

    elCaptcha.removeAttribute('aria-invalid');
    if (hataEl) hataEl.textContent = '';
    return true;
  }

  if (elCaptcha) {
    elCaptcha.addEventListener('blur', dogrulaCaptcha);
  }

  // -----------------------------------------------------------------------
  // Form submit — tüm zorunlu alanları doğrula, hata varsa durdur
  // -----------------------------------------------------------------------

  form.addEventListener('submit', function (e) {
    const sonuclar = [
      dogrulaAdSoyad(),
      dogrulaTelefon(),
      dogrulaEposta(),
      dogrulaDogumTarihi(),
      dogrulaIkametIl(),
      dogrulaTrabzonIlce(),
      dogrulaKvkk(),
      dogrulaCaptcha(),
    ];

    const basarisiz = sonuclar.some(function (s) { return s === false; });

    if (basarisiz) {
      e.preventDefault();

      const ilkHatali = /** @type {HTMLElement|null} */ (
        form.querySelector('[aria-invalid="true"]')
      );
      if (ilkHatali) {
        ilkHatali.focus();
        ilkHatali.scrollIntoView({ behavior: 'smooth', block: 'center' });
      }
    }
  });

})();

