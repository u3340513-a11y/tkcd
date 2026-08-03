/**
 * Temsilci Ağımız — İnteraktif Türkiye haritası.
 *
 * Neden: SVG haritasında il üzerine gelindiğinde ya da klavyeyle
 * odaklanıldığında o ile ait il ve ilçe başkanı bilgisini tooltip'te gösterir.
 *
 * Veri akışı: PHP şablon, temsilci verisini SVG'nin data-temsilciler
 * niteliğine JSON olarak gömer; bu betik sadece DOM okur, fetch yapmaz.
 *
 * Erişilebilirlik:
 *  - Her <path> tabindex="0" ve role="button" taşır (PHP tarafında set edilir)
 *  - focus/blur ile klavye desteği
 *  - tooltip aria-hidden yönetimi
 *  - prefers-reduced-motion: animasyon süresi sıfıra indirilir
 */

(function () {
  'use strict';

  /** @type {HTMLElement|null} */
  const svg = document.getElementById('tm-harita');
  /** @type {HTMLElement|null} */
  const tooltip = document.getElementById('tm-tooltip');

  if (!svg || !tooltip) return;

  /**
   * @typedef {{il_adi: string, temsilci: string, telefon: string, eposta: string,
   *            ilceler: Array<{ilce: string, temsilci: string, telefon: string}>}} TemsilciVeri
   * @type {Record<string, TemsilciVeri>}
   */
  const temsilciler = JSON.parse(svg.dataset.temsilciler || '{}');

  const elIl           = document.getElementById('tm-tooltip-il');
  const elIlBolum      = document.getElementById('tm-tt-il-bolum');
  const elTemsilci     = document.getElementById('tm-tooltip-temsilci');
  const elTelefon      = document.getElementById('tm-tooltip-telefon');
  const elTelefonSatir = document.getElementById('tm-tt-il-telefon-satir');
  const elIlceBolum    = document.getElementById('tm-tt-ilce-bolum');
  const elIlceListe    = document.getElementById('tm-tt-ilceler');
  const elBos          = document.getElementById('tm-tooltip-bos');

  if (!elIl || !elIlBolum || !elTemsilci || !elIlceBolum || !elIlceListe || !elBos) return;

  /**
   * Tooltip içeriğini plateDan gelen veriye göre doldurur.
   *
   * Görüntüleme mantığı:
   *  - İl başkanı varsa "İl Başkanı" bölümü açılır.
   *  - İlçe başkanları varsa "İlçe Başkanları" bölümü açılır.
   *  - İkisi de yoksa "Atama Bekleniyor" rozeti gösterilir.
   *
   * @param {string} plate - İl plaka kodu
   * @param {string} ilAdi - İl adı (harita path'inden gelir)
   */
  function doldur(plate, ilAdi) {
    const veri = temsilciler[plate];

    elIl.textContent = ilAdi;

    const ilceler = (veri && Array.isArray(veri.ilceler)) ? veri.ilceler : [];
    const varIl   = veri && veri.temsilci;
    const varIlce = ilceler.length > 0;

    /* İl Başkanı bölümü */
    if (varIl) {
      elTemsilci.textContent        = veri.temsilci;
      elTelefon.textContent         = veri.telefon || '';
      elTelefonSatir.hidden         = !veri.telefon;
      elIlBolum.hidden              = false;
    } else {
      elIlBolum.hidden = true;
    }

    /* İlçe Başkanları bölümü */
    if (varIlce) {
      elIlceListe.innerHTML = '';
      ilceler.forEach(function (ilce) {
        const li = document.createElement('li');
        li.className = 'tm-tooltip__ilce-item';

        const ilceAdi = document.createElement('span');
        ilceAdi.className = 'tm-tooltip__ilce-adi';
        ilceAdi.textContent = ilce.ilce;

        const temsilciAdi = document.createElement('span');
        temsilciAdi.className = 'tm-tooltip__ilce-temsilci';
        temsilciAdi.textContent = ilce.temsilci;

        li.appendChild(ilceAdi);
        li.appendChild(temsilciAdi);

        if (ilce.telefon) {
          const tel = document.createElement('span');
          tel.className = 'tm-tooltip__ilce-tel';
          tel.textContent = ilce.telefon;
          li.appendChild(tel);
        }

        elIlceListe.appendChild(li);
      });
      elIlceBolum.hidden = false;
    } else {
      elIlceBolum.hidden = true;
    }

    /* Atama Bekleniyor — yalnızca her ikisi de yoksa */
    elBos.hidden = varIl || varIlce;
  }

  /**
   * Tooltip'i gösterir ve konumlandırır.
   * Fare/odak noktasından sağ-alta doğru kaydırılır;
   * container sınırları aşılırsa sola ya da yukarıya geçer.
   *
   * @param {number} x - Container-relative x (px)
   * @param {number} y - Container-relative y (px)
   */
  function goster(x, y) {
    tooltip.hidden = false;
    tooltip.setAttribute('aria-hidden', 'false');

    const container = svg.closest('.tm-harita-kapsayici');
    if (!container) return;

    const cRect  = container.getBoundingClientRect();
    const tW     = tooltip.offsetWidth  || 230;
    const tH     = tooltip.offsetHeight || 120;
    const OFFSET = 14;

    let left = x + OFFSET;
    let top  = y + OFFSET;

    if (left + tW > cRect.width  - 8) left = x - tW - OFFSET;
    if (top  + tH > cRect.height - 8) top  = y - tH - OFFSET;

    tooltip.style.left = Math.max(4, left) + 'px';
    tooltip.style.top  = Math.max(4, top)  + 'px';
  }

  /** Tooltip'i gizler. */
  function gizle() {
    tooltip.setAttribute('aria-hidden', 'true');
    tooltip.hidden = true;
  }

  /* ---- Fare olayları ---- */

  svg.addEventListener('mousemove', function (e) {
    const path = e.target.closest('.tm-il');
    if (!path) return;

    const container = svg.closest('.tm-harita-kapsayici');
    if (!container) return;

    const rect = container.getBoundingClientRect();
    doldur(path.dataset.plate || '', path.dataset.il || '');
    goster(e.clientX - rect.left, e.clientY - rect.top);
  });

  svg.addEventListener('mouseleave', gizle);

  svg.addEventListener('mouseout', function (e) {
    if (!e.relatedTarget || !svg.contains(/** @type {Node} */ (e.relatedTarget))) {
      gizle();
    }
  });

  /* ---- Klavye desteği ---- */

  svg.addEventListener('focusin', function (e) {
    const path = e.target.closest('.tm-il');
    if (!path) return;

    doldur(path.dataset.plate || '', path.dataset.il || '');

    const container = svg.closest('.tm-harita-kapsayici');
    if (!container) return;

    const pRect = path.getBoundingClientRect();
    const cRect = container.getBoundingClientRect();
    goster(
      pRect.left - cRect.left + pRect.width  / 2,
      pRect.top  - cRect.top  + pRect.height / 2
    );
  });

  svg.addEventListener('focusout', function (e) {
    if (!svg.contains(/** @type {Node} */ (e.relatedTarget))) {
      gizle();
    }
  });

  svg.addEventListener('keydown', function (e) {
    if (e.key !== 'Enter' && e.key !== ' ') return;
    const path = e.target.closest('.tm-il');
    if (!path) return;
    e.preventDefault();
    path.dispatchEvent(new FocusEvent('focusin', { bubbles: true }));
  });

  /* ---- prefers-reduced-motion ---- */

  if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) {
    tooltip.style.transition = 'none';
  }
})();
