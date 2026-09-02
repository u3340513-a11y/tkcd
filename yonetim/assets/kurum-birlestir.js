/**
 * Kurum Birleştirme Aracı — Filtreleme ve Seçim JavaScript Modülü
 *
 * CSP uyumlu: Inline event handler kullanmaz, addEventListener ile bağlanır.
 * Türkçe karakter uyumlu anlık filtreleme ve checkbox seçim sayacı.
 */

(function() {
    'use strict';

    /** Türkçe küçük harf dönüşümü */
    function trKucukHarf(s) {
        return s
            .replace(/İ/g, 'i')
            .replace(/I/g, 'ı')
            .replace(/Ş/g, 'ş')
            .replace(/Ğ/g, 'ğ')
            .replace(/Ü/g, 'ü')
            .replace(/Ö/g, 'ö')
            .replace(/Ç/g, 'ç')
            .toLowerCase();
    }

    /** Anlık kurum filtreleme */
    function kurumFiltrele() {
        var filtre = document.getElementById('kurumAraFiltre');
        if (!filtre) return;

        var aranan = trKucukHarf(filtre.value.trim());
        var satirlar = document.querySelectorAll('#kurumCheckboxListesi .kurum-satir-item');

        for (var i = 0; i < satirlar.length; i++) {
            var ad = satirlar[i].getAttribute('data-ad') || '';
            satirlar[i].style.display = (aranan === '' || ad.indexOf(aranan) !== -1) ? '' : 'none';
        }
    }

    /** Seçim sayacı + satır vurgusu */
    function kurumSecimGuncelle() {
        var checkler = document.querySelectorAll('#kurumCheckboxListesi input[type=checkbox]');
        var secili = 0;

        for (var i = 0; i < checkler.length; i++) {
            if (checkler[i].checked) secili++;
            var satir = checkler[i].closest('.kurum-satir-item');
            if (satir) satir.style.background = checkler[i].checked ? 'rgba(0,131,143,0.08)' : '';
        }

        var sayac = document.getElementById('secimSayaci');
        if (sayac) sayac.textContent = secili + ' seçili';
    }

    /** Event binding — DOM hazır olduğunda bağla */
    function baglantilariKur() {
        var filtre = document.getElementById('kurumAraFiltre');
        var konteyner = document.getElementById('kurumCheckboxListesi');

        if (!filtre || !konteyner) {
            /* DOM henüz hazır değilse 100ms sonra tekrar dene */
            setTimeout(baglantilariKur, 100);
            return;
        }

        /* Arama input'una event bağla */
        filtre.addEventListener('input', kurumFiltrele);
        filtre.addEventListener('keyup', kurumFiltrele);
        filtre.addEventListener('change', kurumFiltrele);

        /* Checkbox'lara event bağla */
        var checkler = konteyner.querySelectorAll('input[type=checkbox]');
        for (var i = 0; i < checkler.length; i++) {
            checkler[i].addEventListener('change', kurumSecimGuncelle);
        }
    }

    /* Başlat */
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', baglantilariKur);
    } else {
        baglantilariKur();
    }
})();
