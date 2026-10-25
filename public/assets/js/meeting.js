/**
 * Toplantı sunumu gezinmesi.
 *
 * Neden var: Slaytlar arasında klavye (sunum kumandası dahil), ekran düğmeleri ve
 * tam ekran ile gezinmeyi sağlar. JavaScript kapalıysa tüm slaytlar alt alta görünür.
 *
 * Kısayollar: → ↓ Boşluk PageDown (sonraki) · ← ↑ PageUp (önceki) · Home/End · F (tam ekran)
 */
(function () {
  'use strict';

  var GIZLEME_SURESI_MS = 3000;
  var SONRAKI_TUSLAR = ['ArrowRight', 'ArrowDown', 'PageDown', ' '];
  var ONCEKI_TUSLAR = ['ArrowLeft', 'ArrowUp', 'PageUp'];

  var govde = document.body;
  var slaytlar = Array.prototype.slice.call(document.querySelectorAll('[data-slayt]'));
  var kontrol = document.querySelector('[data-sunum-kontrol]');
  var sayac = document.querySelector('[data-sayac]');
  var gizlemeZamanlayici = null;
  var aktif = 0;

  if (slaytlar.length === 0) {
    return;
  }

  govde.classList.add('js-sunum');

  function sinirla(indeks) {
    return Math.min(Math.max(indeks, 0), slaytlar.length - 1);
  }

  function goster(indeks) {
    aktif = sinirla(indeks);
    slaytlar.forEach(function (slayt, i) {
      slayt.classList.toggle('slayt--aktif', i === aktif);
      slayt.setAttribute('aria-hidden', i === aktif ? 'false' : 'true');
    });
    if (sayac) {
      sayac.textContent = String(aktif + 1);
    }
    history.replaceState(null, '', '#' + (aktif + 1));
  }

  function tamEkranDegistir() {
    if (document.fullscreenElement) {
      document.exitFullscreen();
    } else if (document.documentElement.requestFullscreen) {
      document.documentElement.requestFullscreen();
    }
  }

  function kontrolGoster() {
    if (!kontrol) {
      return;
    }
    kontrol.classList.remove('sunum__kontrol--gizli');
    window.clearTimeout(gizlemeZamanlayici);
    gizlemeZamanlayici = window.setTimeout(function () {
      kontrol.classList.add('sunum__kontrol--gizli');
    }, GIZLEME_SURESI_MS);
  }

  document.addEventListener('keydown', function (olay) {
    if (SONRAKI_TUSLAR.indexOf(olay.key) !== -1) {
      olay.preventDefault();
      goster(aktif + 1);
    } else if (ONCEKI_TUSLAR.indexOf(olay.key) !== -1) {
      olay.preventDefault();
      goster(aktif - 1);
    } else if (olay.key === 'Home') {
      goster(0);
    } else if (olay.key === 'End') {
      goster(slaytlar.length - 1);
    } else if (olay.key === 'f' || olay.key === 'F') {
      tamEkranDegistir();
    }
    kontrolGoster();
  });

  document.addEventListener('mousemove', kontrolGoster);

  [['[data-onceki]', -1], ['[data-sonraki]', 1]].forEach(function (cift) {
    var dugme = document.querySelector(cift[0]);
    if (dugme) {
      dugme.addEventListener('click', function () {
        goster(aktif + cift[1]);
      });
    }
  });

  var tamEkranDugmesi = document.querySelector('[data-tam-ekran]');
  if (tamEkranDugmesi) {
    tamEkranDugmesi.addEventListener('click', tamEkranDegistir);
  }

  var baslangic = parseInt(window.location.hash.replace('#', ''), 10);
  goster(Number.isNaN(baslangic) ? 0 : baslangic - 1);
  kontrolGoster();
})();
