/**
 * Trabzonlu Kamu Çalışanları Derneği — arayüz etkileşimleri.
 *
 * Tasarım ilkeleri:
 *  • Bağımlılık yok; tarayıcı API'leri doğrudan kullanılır.
 *  • Her davranış bağımsız bir başlatıcıdır ve ilgili öğe sayfada yoksa
 *    sessizce devre dışı kalır (progressive enhancement).
 *  • Hareket azaltma tercihi olan kullanıcılarda animasyonlar çalıştırılmaz.
 */
(() => {
  'use strict';

  const HAREKET_AZALT = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  const secAll = (secici, kok = document) => Array.from(kok.querySelectorAll(secici));

  /* ------------------------------------------------------------------ */
  /* Başlık: sayfa kaydırıldığında gölge ve kenarlık kazanır.            */
  /* ------------------------------------------------------------------ */
  const baslikDavranisi = () => {
    const baslik = document.querySelector('[data-site-basligi]');

    if (!baslik) {
      return;
    }

    const guncelle = () => baslik.classList.toggle('kaydirildi', window.scrollY > 8);

    guncelle();
    window.addEventListener('scroll', guncelle, { passive: true });
  };

  /* ------------------------------------------------------------------ */
  /* Mobil menü: açma/kapama, odak tuzağı ve Esc ile kapatma.            */
  /* ------------------------------------------------------------------ */
  const mobilMenu = () => {
    const cekmece = document.querySelector('[data-cekmece]');
    const acDugmesi = document.querySelector('[data-menu-ac]');

    if (!cekmece || !acDugmesi) {
      return;
    }

    const panel = cekmece.querySelector('.cekmece__panel');
    const odaklanabilir = 'a[href], button:not([disabled]), input, select, textarea, [tabindex]:not([tabindex="-1"])';

    const ac = () => {
      cekmece.dataset.acik = 'true';
      cekmece.setAttribute('aria-hidden', 'false');
      acDugmesi.setAttribute('aria-expanded', 'true');
      document.body.classList.add('menu-acik');

      const ilk = panel && panel.querySelector(odaklanabilir);

      if (ilk) {
        ilk.focus();
      }
    };

    const kapat = () => {
      cekmece.dataset.acik = 'false';
      cekmece.setAttribute('aria-hidden', 'true');
      acDugmesi.setAttribute('aria-expanded', 'false');
      document.body.classList.remove('menu-acik');
      acDugmesi.focus();
    };

    const acikMi = () => cekmece.dataset.acik === 'true';

    acDugmesi.addEventListener('click', () => (acikMi() ? kapat() : ac()));

    secAll('[data-cekmece-kapat]', cekmece).forEach((dugme) => {
      dugme.addEventListener('click', kapat);
    });

    // Menü içindeki bir bağlantıya tıklandığında çekmece kapanır.
    secAll('a[href]', cekmece).forEach((baglanti) => {
      baglanti.addEventListener('click', kapat);
    });

    document.addEventListener('keydown', (olay) => {
      if (!acikMi()) {
        return;
      }

      if (olay.key === 'Escape') {
        kapat();

        return;
      }

      if (olay.key !== 'Tab' || !panel) {
        return;
      }

      const ogeler = secAll(odaklanabilir, panel).filter((oge) => oge.offsetParent !== null);

      if (ogeler.length === 0) {
        return;
      }

      const ilk = ogeler[0];
      const son = ogeler[ogeler.length - 1];

      if (olay.shiftKey && document.activeElement === ilk) {
        olay.preventDefault();
        son.focus();
      } else if (!olay.shiftKey && document.activeElement === son) {
        olay.preventDefault();
        ilk.focus();
      }
    });
  };

  /* ------------------------------------------------------------------ */
  /* Mobil menüdeki alt kırılımların açılır/kapanır davranışı.           */
  /* ------------------------------------------------------------------ */
  const altMenuAkordiyonu = () => {
    secAll('[data-alt-menu-ac]').forEach((dugme) => {
      const hedef = document.getElementById(dugme.getAttribute('aria-controls') || '');

      if (!hedef) {
        return;
      }

      dugme.addEventListener('click', () => {
        const acik = dugme.getAttribute('aria-expanded') === 'true';

        dugme.setAttribute('aria-expanded', String(!acik));
        hedef.dataset.acik = String(!acik);
      });
    });
  };

  /* ------------------------------------------------------------------ */
  /* Tanıtım bölümü: arka planda sessiz/döngüsel YouTube videosu.        */
  /*                                                                      */
  /* Video, ilk sayfa yükleme performansını korumak için bölüm görünüm   */
  /* alanına yaklaşana kadar gömülmez ve "hareketi azalt" tercih edildi- */
  /* ğinde hiç yüklenmez; bu durumlarda bölüm markanın degrade zemini    */
  /* üzerinde durmaya devam eder.                                        */
  /* ------------------------------------------------------------------ */
  const tanitimVideosu = () => {
    const bolum = document.querySelector('[data-medya-video-id]');
    const alan = bolum && bolum.querySelector('[data-medya-arkaplan]');

    if (!bolum || !alan) {
      return;
    }

    const videoId = bolum.dataset.medyaVideoId || '';

    if (videoId === '') {
      return;
    }

    const videoyuYukle = () => {
      // Çift yüklemeyi önle
      if (alan.querySelector('iframe')) {
        return;
      }

      const iframe = document.createElement('iframe');
      const parametreler = new URLSearchParams({
        autoplay: '1',
        mute: '1',
        loop: '1',
        playlist: videoId,
        controls: '0',
        showinfo: '0',
        rel: '0',
        modestbranding: '1',
        playsinline: '1',
        disablekb: '1',
        iv_load_policy: '3',
        enablejsapi: '0',
      });

      iframe.src = `https://www.youtube-nocookie.com/embed/${encodeURIComponent(videoId)}?${parametreler.toString()}`;
      iframe.title = 'Tanıtım videosu (arka plan)';
      iframe.setAttribute('tabindex', '-1');
      iframe.setAttribute('aria-hidden', 'true');
      iframe.allow = 'autoplay; encrypted-media; picture-in-picture';
      alan.appendChild(iframe);
    };

    // IntersectionObserver yoksa direkt yükle
    if (!('IntersectionObserver' in window)) {
      videoyuYukle();
      return;
    }

    const gozlemci = new IntersectionObserver(
      (girisler) => {
        girisler.forEach((giris) => {
          if (!giris.isIntersecting) {
            return;
          }
          videoyuYukle();
          gozlemci.unobserve(giris.target);
        });
      },
      // rootMargin: Hero section zaten görünür alanda olduğundan
      // negatif margin kaldırıldı — sıfır tolerans ile anında tetiklenir
      { rootMargin: '0px 0px', threshold: 0 },
    );

    gozlemci.observe(bolum);

    // Güvence: sayfa yüklendiğinde section zaten görünür alanındaysa
    // IntersectionObserver bazen tetiklemiyor — setTimeout ile kontrol et
    setTimeout(() => {
      const kutu = bolum.getBoundingClientRect();
      if (kutu.top < window.innerHeight && kutu.bottom > 0) {
        videoyuYukle();
      }
    }, 300);
  };

  /* ------------------------------------------------------------------ */
  /* Görünüm alanına giren bölümlerin yumuşak belirmesi.                 */
  /* ------------------------------------------------------------------ */
  const belirmeAnimasyonu = () => {
    const ogeler = secAll('.belirme');

    if (ogeler.length === 0) {
      return;
    }

    if (HAREKET_AZALT || !('IntersectionObserver' in window)) {
      ogeler.forEach((oge) => oge.classList.add('gorunur'));

      return;
    }

    const gozlemci = new IntersectionObserver(
      (girisler) => {
        girisler.forEach((giris) => {
          if (!giris.isIntersecting) {
            return;
          }

          giris.target.classList.add('gorunur');
          gozlemci.unobserve(giris.target);
        });
      },
      { rootMargin: '0px 0px -12% 0px', threshold: 0.12 },
    );

    ogeler.forEach((oge, sira) => {
      oge.style.transitionDelay = `${Math.min(sira % 4, 3) * 90}ms`;
      gozlemci.observe(oge);
    });
  };

  /* ------------------------------------------------------------------ */
  /* Sayaçların sıfırdan hedef değere animasyonlu ilerlemesi.            */
  /* ------------------------------------------------------------------ */
  const sayacAnimasyonu = () => {
    const sayaclar = secAll('[data-sayac]');

    if (sayaclar.length === 0 || HAREKET_AZALT || !('IntersectionObserver' in window)) {
      return;
    }

    const SURE = 1600;

    const calistir = (oge) => {
      const hedef = Number.parseInt(oge.dataset.sayac || '0', 10);
      const sonEk = oge.dataset.sayacSonEk || '';

      if (!Number.isFinite(hedef) || hedef <= 0) {
        return;
      }

      const baslangic = performance.now();

      const adim = (zaman) => {
        const oran = Math.min((zaman - baslangic) / SURE, 1);
        const yumusatilmis = 1 - Math.pow(1 - oran, 3);

        oge.textContent = `${Math.round(hedef * yumusatilmis)}${sonEk}`;

        if (oran < 1) {
          window.requestAnimationFrame(adim);
        }
      };

      window.requestAnimationFrame(adim);
    };

    const gozlemci = new IntersectionObserver(
      (girisler) => {
        girisler.forEach((giris) => {
          if (!giris.isIntersecting) {
            return;
          }

          calistir(giris.target);
          gozlemci.unobserve(giris.target);
        });
      },
      { threshold: 0.4 },
    );

    sayaclar.forEach((sayac) => {
      // Sunucu tarafında basılan nihai değer JavaScript kapalıyken görünür
      // kalır; JavaScript etkinse animasyon sıfırdan başlatılır.
      sayac.textContent = `0${sayac.dataset.sayacSonEk || ''}`;
      gozlemci.observe(sayac);
    });
  };

  /* ------------------------------------------------------------------ */
  /* Trabzon ilçeleri: etkileşimli harita + hızlı seçim listesi.         */
  /*                                                                      */
  /* Harita üzerindeki bir ilçeye veya listedeki etikete tıklandığında   */
  /* (ya da Enter/Boşluk ile etkinleştirildiğinde) o ilçenin kısa bilgisi */
  /* bir modal pencerede gösterilir. Üzerine gelindiğinde/odaklanıldığında*/
  /* ilçe adı harita üzerinde küçük bir etiket olarak belirir.            */
  /* ------------------------------------------------------------------ */
  const ilceHaritasi = () => {
    const harita = document.querySelector('[data-ilce-harita]');
    const modal = document.querySelector('[data-ilce-modal]');

    if (!harita || !modal) {
      return;
    }

    const etiket = harita.querySelector('[data-ilce-etiket]');
    const yollar = secAll('.trabzon-harita__ilce', harita);
    const dugmeler = secAll('.ilce-dugme');
    const secilebilirler = [...yollar, ...dugmeler];

    const kapatDugmesi = modal.querySelector('[data-ilce-modal-kapat]');
    const modalEtiket = modal.querySelector('[data-ilce-modal-etiket]');
    const modalBaslik = modal.querySelector('[data-ilce-modal-baslik]');
    const modalAciklama = modal.querySelector('[data-ilce-modal-aciklama]');

    const seciliIsaretle = (slug) => {
      yollar.forEach((yol) => yol.classList.toggle('trabzon-harita__ilce--aktif', yol.dataset.ilceSlug === slug));
      dugmeler.forEach((dugme) => dugme.classList.toggle('ilce-dugme--aktif', dugme.dataset.ilceSlug === slug));
    };

    const etiketiGoster = (yol) => {
      if (!etiket) {
        return;
      }

      const haritaKutu = harita.getBoundingClientRect();
      const yolKutu = yol.getBoundingClientRect();

      etiket.textContent = yol.dataset.ilceAdi || '';
      etiket.style.left = `${yolKutu.left - haritaKutu.left + yolKutu.width / 2}px`;
      etiket.style.top = `${yolKutu.top - haritaKutu.top}px`;
      etiket.hidden = false;
    };

    const etiketiGizle = () => {
      if (etiket) {
        etiket.hidden = true;
      }
    };

    const modalAc = (oge) => {
      const merkez = oge.dataset.ilceMerkez === '1';

      modalEtiket.textContent = merkez ? 'Merkez İlçe' : 'Trabzon İlçesi';
      modalBaslik.textContent = oge.dataset.ilceAdi || '';
      modalAciklama.textContent = oge.dataset.ilceBilgi || '';
      seciliIsaretle(oge.dataset.ilceSlug || null);

      if (typeof modal.showModal === 'function') {
        modal.showModal();
      } else {
        modal.setAttribute('open', '');
      }

      window.requestAnimationFrame(() => modal.classList.add('ilce-modal--gorunur'));
    };

    const modalKapat = () => {
      modal.classList.remove('ilce-modal--gorunur');

      window.setTimeout(() => {
        if (typeof modal.close === 'function') {
          if (modal.open) {
            modal.close();
          }
        } else {
          modal.removeAttribute('open');
        }
      }, HAREKET_AZALT ? 0 : 200);
    };

    modal.addEventListener('close', () => {
      modal.classList.remove('ilce-modal--gorunur');
      seciliIsaretle(null);
    });

    modal.addEventListener('click', (olay) => {
      if (olay.target === modal) {
        modalKapat();
      }
    });

    if (kapatDugmesi) {
      kapatDugmesi.addEventListener('click', modalKapat);
    }

    secilebilirler.forEach((oge) => {
      oge.addEventListener('click', () => modalAc(oge));

      oge.addEventListener('keydown', (olay) => {
        if (olay.key === 'Enter' || olay.key === ' ') {
          olay.preventDefault();
          modalAc(oge);
        }
      });
    });

    yollar.forEach((yol) => {
      yol.addEventListener('pointerenter', () => etiketiGoster(yol));
      yol.addEventListener('pointerleave', etiketiGizle);
      yol.addEventListener('focus', () => etiketiGoster(yol));
      yol.addEventListener('blur', etiketiGizle);
    });
  };

  /* ------------------------------------------------------------------ */
  /* Sayfa başına dön düğmesi.                                           */
  /* ------------------------------------------------------------------ */
  const yukariCik = () => {
    const dugme = document.querySelector('[data-yukari-cik]');

    if (!dugme) {
      return;
    }

    const esik = 420;
    const guncelle = () => dugme.classList.toggle('gorunur', window.scrollY > esik);

    guncelle();
    window.addEventListener('scroll', guncelle, { passive: true });

    dugme.addEventListener('click', () => {
      window.scrollTo({ top: 0, behavior: HAREKET_AZALT ? 'auto' : 'smooth' });
    });
  };

  [
    baslikDavranisi,
    mobilMenu,
    altMenuAkordiyonu,
    tanitimVideosu,
    belirmeAnimasyonu,
    sayacAnimasyonu,
    ilceHaritasi,
    yukariCik,
  ].forEach((baslat) => baslat());
})();
