/**
 * Bilgi Güncelleme Sayfası — Form yardımcı betikleri
 *
 * İşlevler:
 *   1. Telefon formatı (05XX XXX XX XX)
 *   2. Doğum tarihi formatı (GG/AA/YYYY)
 *   3. İl/İlçe bağlantılı dropdown
 *   4. Form gönderim kilidi (çift tıklama engeli)
 */

document.addEventListener("DOMContentLoaded", function () {
    "use strict";

    // ─── 1. TELEFON FORMATI ────────────────────────────────────────
    var telefonInput = document.getElementById("telefon");
    if (telefonInput) {
        telefonInput.addEventListener("input", function () {
            var digits = this.value.replace(/\D/g, "");

            // Maksimum 11 hane (05XX XXX XX XX)
            if (digits.length > 11) {
                digits = digits.substring(0, 11);
            }

            // Format: 05XX XXX XX XX
            var formatted = "";
            for (var i = 0; i < digits.length; i++) {
                if (i === 4 || i === 7 || i === 9) {
                    formatted += " ";
                }
                formatted += digits[i];
            }
            this.value = formatted;
        });
    }

    // ─── 2. DOĞUM TARİHİ FORMATI ──────────────────────────────────
    var dogumInput = document.getElementById("dogum_tarihi");
    if (dogumInput) {
        dogumInput.addEventListener("input", function () {
            var digits = this.value.replace(/\D/g, "");
            if (digits.length > 8) {
                digits = digits.substring(0, 8);
            }

            var formatted = "";
            for (var i = 0; i < digits.length; i++) {
                if (i === 2 || i === 4) {
                    formatted += "/";
                }
                formatted += digits[i];
            }
            this.value = formatted;
        });
    }

    // ─── 3. İL/İLÇE BAĞLANTILI DROPDOWN ───────────────────────────
    var ilSelect   = document.getElementById("ikamet_ili");
    var ilceSelect = document.getElementById("ikamet_ilcesi");

    if (ilSelect && ilceSelect) {
        /**
         * Seçilen ile göre ilçe dropdown'ını doldurur.
         *
         * @param {string} ilAdi     - Seçilen il adı
         * @param {string} secilecek - Önceden seçili ilçe (varsa)
         */
        function ilceleriDoldur(ilAdi, secilecek) {
            // Mevcut seçenekleri temizle
            ilceSelect.innerHTML = "";

            if (!ilAdi || typeof TURKIYE_ILCELER === "undefined" || !TURKIYE_ILCELER[ilAdi]) {
                var bos = document.createElement("option");
                bos.value = "";
                bos.textContent = "Önce il seçiniz";
                ilceSelect.appendChild(bos);
                return;
            }

            var varsayilan = document.createElement("option");
            varsayilan.value = "";
            varsayilan.textContent = "İlçe seçiniz";
            ilceSelect.appendChild(varsayilan);

            var ilceler = TURKIYE_ILCELER[ilAdi];
            for (var i = 0; i < ilceler.length; i++) {
                var opt = document.createElement("option");
                opt.value = ilceler[i];
                opt.textContent = ilceler[i];
                if (secilecek && ilceler[i] === secilecek) {
                    opt.selected = true;
                }
                ilceSelect.appendChild(opt);
            }
        }

        // İl değiştiğinde ilçeleri güncelle
        ilSelect.addEventListener("change", function () {
            ilceleriDoldur(this.value, "");
        });

        // Sayfa yüklendiğinde mevcut il seçili ise ilçeleri doldur
        if (ilSelect.value) {
            var mevcutIlceInput = document.getElementById("mevcut_ilce");
            var mevcutIlce = mevcutIlceInput ? mevcutIlceInput.value : "";
            ilceleriDoldur(ilSelect.value, mevcutIlce);
        }
    }

    // ─── 4. FORM GÖNDERİM KİLİDİ ─────────────────────────────────
    var forms = document.querySelectorAll(".bg-form");
    for (var f = 0; f < forms.length; f++) {
        forms[f].addEventListener("submit", function () {
            var btn = this.querySelector("button[type='submit']");
            if (btn) {
                btn.disabled = true;
                btn.style.opacity = "0.7";
                btn.style.cursor = "wait";
            }
        });
    }
});
