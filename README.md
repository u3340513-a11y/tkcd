# Trabzonlu Kamu Çalışanları Derneği — Kurumsal Web Sitesi

Dernek tanıtım sitesinin ön yüz (Faz 1) kaynak kodu. Composer, Node.js veya derleme
adımı gerektirmeden paylaşımlı cPanel hesabına yüklenebilecek şekilde, saf PHP 8.1
ile katmanlı mimaride geliştirilmiştir. Yönetim paneli (Faz 2) aynı mimari üzerine,
mevcut kodun hiçbir katmanı değiştirilmeden eklenebilir.

---

## 1. Teknoloji tercihleri ve gerekçeleri

| Karar | Gerekçe |
| --- | --- |
| PHP 8.1+ | cPanel'de standart olarak mevcuttur; Faz 2'deki yönetim paneli aynı çalışma zamanını kullanabilir. |
| Composer yok, kendi PSR-4 yükleyicimiz | Paylaşımlı hostingde SSH/Composer erişimi çoğu zaman kısıtlıdır. `app/Core/Autoloader.php` aynı standardı bağımlılıksız uygular. |
| Derleme adımı yok (saf CSS/JS) | Node.js kurulumu gerekmeden dosyaların FTP ile yüklenmesi yeterlidir; dağıtım hatası yüzeyi küçülür. |
| Dosya tabanlı içerik depoları | İçerik `resources/data/*.php` içinde tutulur. Faz 2'de yalnızca repository implementasyonu veritabanı sürümüyle değiştirilir; kontrolcü ve şablon katmanı aynen kalır. |

**Minimum gereksinim:** PHP 8.1, Apache + `mod_rewrite`.

---

## 2. Mimari

Katmanlar arası bağımlılık yönü daima içeriye doğrudur: `Http → Application → Domain`.
`Infrastructure` katmanı `Domain` arayüzlerini uygular; hiçbir üst katman somut bir
implementasyona bağlı değildir.

```
app/
├── Core/                 Çerçeve altyapısı (uygulamaya özgü iş kuralı içermez)
│   ├── Autoloader.php    PSR-4 sınıf yükleyici
│   ├── Container.php     Reflection tabanlı bağımlılık enjeksiyon konteyneri
│   ├── Config.php        Nokta notasyonlu yapılandırma erişimi
│   ├── Env.php           .env okuyucu
│   ├── Routing/          Route, Router
│   ├── Http/             Request, Response, SecurityHeaders
│   ├── View/             PhpViewRenderer, SeoMeta, AssetManager, IconSet
│   ├── Log/              LoggerInterface + FileLogger
│   └── Support/          Html (kaçış/URL), Text (slug, özet, tarih)
│
├── Domain/               Saf iş modeli — çerçeveden bağımsız
│   ├── Content/Entity/   Announcement, Event, Statistic, ActivityArea, District, Milestone
│   ├── Content/Repository/  Depo arayüzleri (sözleşmeler)
│   └── Navigation/       NavigationItem
│
├── Infrastructure/       Sözleşmelerin somut karşılıkları
│   └── Content/          PhpFile*Repository + DataFileLoader
│
├── Application/          Kullanım senaryoları
│   ├── Service/          HomePageService, NavigationProvider, LayoutDataComposer,
│   │                     PageResponder, StructuredDataFactory
│   └── ViewModel/        HomePageViewModel
│
└── Http/Controller/      İnce kontrolcüler (yalnızca yönlendirme ve yanıt üretimi)
```

### İstek yaşam döngüsü

1. `public/index.php` — tek giriş noktası (front controller).
2. `Application::run()` — ortam değişkenlerini, hata yakalayıcıyı ve güvenlik başlıklarını kurar.
3. `Router` — `config/routes.php` içindeki tanımlara göre kontrolcüyü seçer.
4. `Container` — kontrolcüyü ve bağımlılıklarını constructor injection ile üretir.
5. Kontrolcü, ilgili **Service**'ten bir **ViewModel** alır ve `PageResponder` ile yanıtı döndürür.
6. `PhpViewRenderer` şablonu `resources/views/layouts/base.php` yerleşimiyle birleştirir.

---

## 3. Dizin yapısı

```
.
├── .htaccess              Kök dizin koruması + public/ yönlendirmesi
├── .env.example           Ortam değişkenleri şablonu
├── app/                   Uygulama kodu (HTTP erişimine kapalı)
├── config/                app, site, social, navigation, routes
├── public/                Belge kökü — index.php, assets, robots.txt
├── resources/
│   ├── data/              İçerik veri dosyaları (Faz 2'de veritabanına taşınır)
│   └── views/             layouts, partials, components, pages
└── storage/logs/          Hata günlükleri (yazılabilir olmalı)
```

---

## 4. Kurulum

### 4.1 Yerel geliştirme

```bash
cp .env.example .env
# .env içinde APP_ENV=local ve APP_DEBUG=true yapın

php -S 127.0.0.1:8787 -t public public/index.php
```

Tarayıcıdan `http://127.0.0.1:8787` adresini açın.

### 4.2 cPanel dağıtımı

1. Proje dosyalarının tamamını `public_html` dizinine yükleyin.
   Kökteki `.htaccess`, `public/` dizinini belge kökü gibi davranmaya zorlar; `app`,
   `config`, `resources`, `storage` ve `images` dizinlerine HTTP erişimi engellenir.
2. `.env.example` dosyasını `.env` olarak kopyalayın ve alan adına göre doldurun:
   - `APP_ENV=production`, `APP_DEBUG=false`
   - `APP_URL` — canlı alan adı (kanonik etiketler ve sitemap bu değeri kullanır)
3. `storage/logs` dizinine yazma izni verin (`0755`).
4. cPanel → MultiPHP Manager üzerinden alan adını **PHP 8.1 veya üzeri** ile eşleştirin.
5. AutoSSL ile sertifikayı etkinleştirin; site HTTPS varsayımıyla kodlanmıştır.

> Alan adının belge kökünü doğrudan `public/` olarak ayarlayabiliyorsanız bunu
> tercih edin; kökteki `.htaccess` yönlendirmesi bu durumda gereksizdir.

---

## 5. Yapılandırma

Tüm değiştirilebilir değerler `.env` dosyasından okunur; kaynak kodda sabitlenmiş
kurum bilgisi, bağlantı veya anahtar bulunmaz.

| Değişken | Açıklama |
| --- | --- |
| `APP_ENV` | `local` hatayı ekrana yazar, `production` özel hata sayfası gösterir. |
| `APP_URL` | Kanonik URL, Open Graph ve `sitemap.xml` üretimi için temel adres. |
| `SITE_*` | Ad, e-posta, telefon, adres — üst bilgi, alt bilgi ve JSON-LD'de kullanılır. |
| `SOCIAL_*` | Sosyal medya bağlantıları; boş bırakılan kanal arayüzde hiç görünmez. |
| `MEMBERSHIP_FORM_URL` | Üyelik başvuru hedefi (harici form adresi de verilebilir). |
| `SITE_PROMO_VIDEO_ID` | Anasayfadaki tanıtım bölümünün arka planında oynatılan YouTube videosunun kimliği. |
| `ANALYTICS_MEASUREMENT_ID` | Boş bırakılırsa hiçbir izleme kodu yüklenmez. |
| `DB_*` | Faz 2 için ayrılmıştır; Faz 1'de kullanılmaz. |

Menü yapısı `config/navigation.php`, rotalar `config/routes.php` dosyasındadır.

---

## 6. İçerik yönetimi (Faz 1)

İçerik `resources/data/` altındaki PHP dizilerinde tutulur:

| Dosya | İçerik |
| --- | --- |
| `announcements.php` | Duyuru şeridi kayıtları |
| `events.php` | Etkinlik ve haber kartları + detay sayfası gövdesi |
| `statistics.php` | Ana sayfadaki sayaç değerleri |
| `activity-areas.php` | Faaliyet alanı kartları |
| `districts.php` | Trabzon ilçeleri, kısa bilgileri ve etkileşimli haritadaki sınır çizimi (`map_path`) |
| `milestones.php` | Trabzon tarihçesi zaman çizelgesi |

Görseller `public/assets/img/` dizinine konur ve veri dosyasında göreli yolla
belirtilir. Dosya sunucuda yoksa `components/gorsel.php` bileşeni kurumsal desenli
bir alternatif üretir; hiçbir koşulda kırık görsel görünmez.

### İlçeler haritası ve tanıtım videosu

Anasayfadaki "Trabzon İlçeleri" bölümü, `districts.php` içindeki `map_path` SVG
verisiyle çizilen gerçek bir il haritası sunar (kaynak: MIT lisanslı
[`ritzykey/turkey-district-maps`](https://github.com/ritzykey/turkey-district-maps)
projesinin Trabzon sınırları, ortak `viewBox="10 15 411 233"` koordinat sisteminde).
Bir ilçeye tıklandığında (fare, dokunma veya Enter/Boşluk tuşuyla) kısa bilgisi bir
`<dialog>` modalında açılır; haritanın altındaki hızlı seçim listesi aynı etkileşimi
klavye ve dokunmatik cihazlar için tekrarlar.

Tanıtım bölümünün arka planındaki YouTube videosu (`SITE_PROMO_VIDEO_ID`), sayfa
performansını korumak için bölüm görünüm alanına yaklaşana kadar
(`IntersectionObserver`) ve yalnızca kullanıcı "hareketi azalt" tercihini
seçmemişse gömülür; her koşulda `youtube-nocookie.com` gizlilik dostu alan adı
kullanılır.

---

## 7. Tasarım sistemi

Renk paleti logodaki bordo ve mavi tonlarından türetilmiş, her biri 10 basamaklı
iki marka skalası ile bir nötr skalaya genişletilmiştir. Bileşenler ham skala
değerlerini değil, anlamsal tokenları kullanır; böylece palet tek noktadan
değiştirilebilir. Tanımlar `public/assets/css/app.css` başındadır.

| Token | Karşılık | Kullanım |
| --- | --- | --- |
| `--renk-marka` | `--bordo-700` → `#5f2132` | Ana marka rengi, birincil düğmeler |
| `--renk-marka-koyu` | `--bordo-800` → `#4a1927` | Alt bilgi zemini, koyu vurgular |
| `--renk-vurgu` | `--mavi-400` → `#4b9cce` | İkincil marka rengi |
| `--renk-vurgu-koyu` | `--mavi-600` → `#246691` | Bağlantı ve ikon vurguları |
| `--renk-metin` | `--notr-800` → `#262d3d` | Gövde metni |
| `--renk-zemin` / `--renk-yuzey` | `#f8f9fb` / `#ffffff` | Sayfa ve kart zeminleri |
| `--degrade-marka` | bordo → mavi | Kahraman bölümü, üyelik bandı, rozetler |

Diğer ilkeler:

- Ölçekler `clamp()` ile akışkandır; sabit piksel kırılımına bağımlı değildir.
- Sistem font yığını kullanılır — harici font isteği yoktur, LCP korunur.
- İkonlar satır içi SVG olarak (`components/icons.php`) basılır; ek ağ isteği yoktur.
- CSS iki dosyaya ayrılmıştır: `app.css` (tüm sayfalar) ve `home.css` (yalnızca ana sayfa).

---

## 8. SEO

- Sayfa bazlı `title`, `description`, kanonik URL, Open Graph ve Twitter Card etiketleri (`SeoMeta`).
- JSON-LD yapısal veri: `Organization`, `WebSite`, `BreadcrumbList`, etkinlik sayfalarında `Event` (`StructuredDataFactory`).
- Dinamik `sitemap.xml` (`SitemapController`) ve statik `robots.txt`.
- Anlamsal HTML5, tek `h1` kuralı ve tutarlı başlık hiyerarşisi.
- Tüm görsellerde açıklayıcı `alt`, dekoratif öğelerde `aria-hidden`.
- Türkçe `lang` özniteliği ve `Europe/Istanbul` saat dilimiyle biçimlenmiş tarihler.

## 9. Erişilebilirlik

- İçeriğe atlama bağlantısı, görünür odak halkaları, ARIA nitelikleri.
- Mobil çekmecede odak tuzağı ve `Esc` ile kapatma.
- WCAG AA kontrast hedefi; `prefers-reduced-motion` desteğiyle animasyonların ve
  arka plan videosunun otomatik oynatmasının kapatılması.
- JavaScript devre dışıyken tüm içerik okunabilir kalır (sayaçlar nihai değerleriyle
  basılır).
- İlçe haritasındaki her bölge `tabindex`, `role="button"` ve açıklayıcı
  `aria-label` ile klavye ve ekran okuyucu erişimine açıktır; bilgi modalı yerel
  `<dialog>` öğesiyle odak tuzağı ve `Esc` ile kapatmayı tarayıcıdan miras alır.

## 10. Güvenlik

- Çıktı kaçışı tek noktadan (`Html::escape`) yapılır; şablonlarda ham `echo` kullanılmaz.
- Güvenlik başlıkları: `Content-Security-Policy`, `X-Content-Type-Options`,
  `X-Frame-Options`, `Referrer-Policy`, `Permissions-Policy`.
- Yol geçişi (path traversal) koruması: şablon ve varlık adları beyaz listeyle doğrulanır.
- Dış bağlantılarda `rel="noopener noreferrer"`.
- Üretimde hata detayı ekrana basılmaz; yalnızca `storage/logs` içine yazılır.
- Sırlar `.env` dosyasındadır ve sürüm kontrolüne dâhil edilmez.

---

## 11. Rotalar

| Yol | Durum |
| --- | --- |
| `/` | Ana sayfa — tamamlandı |
| `/hakkimizda/dernegimiz` | İçerik bekleniyor |
| `/hakkimizda/anlasmali-kurumlar` | İçerik bekleniyor |
| `/hakkimizda/temsilci-agimiz` | İçerik bekleniyor |
| `/hakkimizda/galeri` | İçerik bekleniyor |
| `/duyurular` | İçerik bekleniyor |
| `/yonetim-kurulu` | İçerik bekleniyor |
| `/iletisim` | İçerik bekleniyor |
| `/uye-ol` | İçerik bekleniyor |
| `/etkinlikler/{slug}` | Etkinlik detayı — tamamlandı |
| `/sitemap.xml` | Otomatik üretilir |

İçeriği bekleyen sayfalar, ortak `pages/section.php` şablonuyla kurumsal bir
"içerik hazırlanıyor" durumu gösterir; yayına alındığında yalnızca ilgili
şablon eklenir.

---

## 12. Doğrulama

```bash
# Sözdizimi denetimi
find app config public resources -name "*.php" -print0 | xargs -0 -n1 php -l

# Yerel sunucu
php -S 127.0.0.1:8787 -t public public/index.php
```

Kontrol listesi: ana sayfa bölümlerinin 390 / 768 / 1440 px genişliklerde
görünümü, mobil menü ve alt menü akordiyonu, `/sitemap.xml` çıktısı ve
bilinmeyen bir adreste 404 durum kodu.

---

## 13. Faz 2 — yönetim paneli

Mevcut mimari aşağıdaki adımlarla veritabanına geçer:

1. `app/Infrastructure/Persistence/` dizini açılıp PDO tabanlı `Pdo*Repository` sınıfları eklenir.
2. Konteynerdeki arayüz eşlemeleri yeni sınıflara yönlendirilir.
3. Kontrolcü, servis ve şablon katmanlarında değişiklik gerekmez.
4. Panel rotaları ayrı bir yetkilendirme ara katmanının arkasında tanımlanır.
