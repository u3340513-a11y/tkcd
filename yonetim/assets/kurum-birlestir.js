/**
 * Kurum Birleştirme Aracı — Filtreleme ve Seçim JavaScript Modülü
 *
 * Türkçe karakter uyumlu anlık filtreleme ve checkbox seçim sayacı.
 * Bu dosya kurum-birlestir.php tarafından harici olarak yüklenir.
 */

/* Türkçe küçük harf dönüşümü */
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

/* Anlık kurum filtreleme — oninput="kurumFiltrele(this.value)" ile çağrılır */
function kurumFiltrele(deger) {
    var aranan = trKucukHarf(deger.trim());
    var satirlar = document.querySelectorAll('#kurumCheckboxListesi .kurum-satir-item');
    for (var i = 0; i < satirlar.length; i++) {
        var ad = satirlar[i].getAttribute('data-ad') || '';
        satirlar[i].style.display = (aranan === '' || ad.indexOf(aranan) !== -1) ? '' : 'none';
    }
}

/* Seçim sayacı + satır vurgusu — onchange="kurumSecimGuncelle()" ile çağrılır */
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
