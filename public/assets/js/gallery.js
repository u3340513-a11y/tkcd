/**
 * Galeri Lightbox
 *
 * Neden: Görsellere tıklandığında tam ekran modal ile büyük hallerini gösterir.
 * Destekler: klavye navigasyonu (ESC, ←, →), arka plana tıklayarak kapatma,
 *            odak kapanı (focus trap), önceki/sonraki navigasyon.
 */
(() => {
  'use strict';

  const modal      = /** @type {HTMLDialogElement|null} */ (document.getElementById('gl-lightbox'));
  const gorselEl   = /** @type {HTMLImageElement|null}  */ (document.getElementById('gl-lightbox-gorsel'));
  const kapatBtn   = document.getElementById('gl-lightbox-kapat');
  const oncekiBtn  = document.getElementById('gl-lightbox-onceki');
  const sonrakiBtn = document.getElementById('gl-lightbox-sonraki');
  const arka       = document.getElementById('gl-lightbox-arka');

  if (!modal || !gorselEl || !kapatBtn || !oncekiBtn || !sonrakiBtn || !arka) {
    return;
  }

  /** @type {NodeListOf<HTMLButtonElement>} */
  const kartlar = document.querySelectorAll('[data-lightbox-src]');

  if (kartlar.length === 0) {
    return;
  }

  /** @type {number} */
  let aktifIdx = 0;

  /** @type {HTMLButtonElement|null} Lightbox açılmadan önceki odaklanılan eleman */
  let oncekiOdak = null;

  /**
   * Belirtilen indeksteki görseli lightbox'ta gösterir.
   * @param {number} idx
   */
  const goster = (idx) => {
    const kart = kartlar[idx];
    if (!kart) return;

    aktifIdx = idx;
    const src = kart.dataset.lightboxSrc || '';
    const alt = kart.dataset.lightboxAlt || '';

    // Animasyonu sıfırla
    gorselEl.style.opacity = '0';
    gorselEl.style.transform = 'scale(0.94)';

    gorselEl.src = src;
    gorselEl.alt = alt;

    // Görsel yüklenince animasyon
    gorselEl.onload = () => {
      requestAnimationFrame(() => {
        gorselEl.style.opacity = '';
        gorselEl.style.transform = '';
      });
    };

    // Önceki/sonraki butonlarını gizle/göster
    oncekiBtn.hidden = kartlar.length <= 1;
    sonrakiBtn.hidden = kartlar.length <= 1;
  };

  /**
   * Lightbox'ı açar.
   * @param {number} idx
   * @param {HTMLElement} tetikleyici
   */
  const ac = (idx, tetikleyici) => {
    oncekiOdak = tetikleyici;
    goster(idx);
    modal.showModal();
    document.body.style.overflow = 'hidden';
    kapatBtn.focus();
  };

  /**
   * Lightbox'ı kapatır.
   */
  const kapat = () => {
    modal.close();
    document.body.style.overflow = '';
    gorselEl.src = '';
    gorselEl.alt = '';
    if (oncekiOdak) {
      oncekiOdak.focus();
      oncekiOdak = null;
    }
  };

  /** Önceki görsele geç */
  const onceki = () => {
    const yeniIdx = (aktifIdx - 1 + kartlar.length) % kartlar.length;
    goster(yeniIdx);
  };

  /** Sonraki görsele geç */
  const sonraki = () => {
    const yeniIdx = (aktifIdx + 1) % kartlar.length;
    goster(yeniIdx);
  };

  // Kart tıklama
  kartlar.forEach((kart, idx) => {
    kart.addEventListener('click', () => ac(idx, kart));
  });

  // Kapat butonu
  kapatBtn.addEventListener('click', kapat);

  // Arka plana tıklama
  arka.addEventListener('click', kapat);

  // Önceki / sonraki
  oncekiBtn.addEventListener('click', onceki);
  sonrakiBtn.addEventListener('click', sonraki);

  // Klavye
  modal.addEventListener('keydown', (e) => {
    switch (e.key) {
      case 'Escape':
        // <dialog> ESC'yi varsayılan olarak handle eder; kapat() ile yönetiyoruz
        e.preventDefault();
        kapat();
        break;
      case 'ArrowLeft':
        onceki();
        break;
      case 'ArrowRight':
        sonraki();
        break;
      default:
        break;
    }
  });

  // dialog kapatma olayı (ESC veya close() ile)
  modal.addEventListener('close', () => {
    document.body.style.overflow = '';
    gorselEl.src = '';
    gorselEl.alt = '';
    if (oncekiOdak) {
      oncekiOdak.focus();
      oncekiOdak = null;
    }
  });
})();
