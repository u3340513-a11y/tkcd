<?php

declare(strict_types=1);

use App\Core\View\PhpViewRenderer;
use App\Core\View\SeoMeta;

/**
 * KVKK Aydınlatma Metni sayfası.
 *
 * @var PhpViewRenderer $view
 * @var SeoMeta         $seo
 */

?>

<!-- HERO -->
<section class="kvkk-hero" aria-labelledby="kvkk-hero-baslik">
    <div class="kapsayici kvkk-hero__ic">
        <?php if (!empty($seo->breadcrumbs)): ?>
        <nav class="da-hero__breadcrumb" aria-label="Konum">
            <ol class="da-hero__breadcrumb-list">
                <li><a href="/">Ana Sayfa</a></li>
                <?php foreach ($seo->breadcrumbs as $bc): ?>
                <li>
                    <span aria-hidden="true">›</span>
                    <a href="<?= $view->e($bc['path']) ?>"><?= $view->e($bc['label']) ?></a>
                </li>
                <?php endforeach; ?>
            </ol>
        </nav>
        <?php endif; ?>
        <h1 class="kvkk-hero__baslik" id="kvkk-hero-baslik">KVKK Aydınlatma Metni</h1>
        <p class="kvkk-hero__alt">Kişisel verilerinizin korunması konusundaki haklarınız ve bilgilendirme metnimiz.</p>
    </div>
</section>

<!-- İÇERİK -->
<section class="kvkk-bolum" aria-label="KVKK Metni">
    <div class="kapsayici kvkk-icerik">

        <nav class="kvkk-icindekiler" aria-label="İçindekiler">
            <p class="kvkk-icindekiler__baslik">İçindekiler</p>
            <ol class="kvkk-icindekiler__liste">
                <li><a href="#veri-sorumlusu">1. Veri Sorumlusu</a></li>
                <li><a href="#islenen-veriler">2. İşlenen Kişisel Veriler</a></li>
                <li><a href="#isleme-amac">3. İşlenme Amaçları</a></li>
                <li><a href="#hukuki-sebep">4. Hukuki Sebepler</a></li>
                <li><a href="#toplama-yontemi">5. Toplanma Yöntemi</a></li>
                <li><a href="#aktarim">6. Aktarılması</a></li>
                <li><a href="#saklama">7. Saklama Süresi</a></li>
                <li><a href="#haklar">8. İlgili Kişinin Hakları</a></li>
                <li><a href="#basvuru">9. Başvuru Yöntemi</a></li>
                <li><a href="#yururluk">10. Yürürlük</a></li>
                <li><a href="#acik-riza">Açık Rıza Metni</a></li>
                <li><a href="#cerez">Çerez Politikası</a></li>
            </ol>
        </nav>

        <article class="kvkk-makale">

            <div class="kvkk-baslik-kutu">
                <h2>TRABZONLU KAMU ÇALIŞANLARI DERNEĞİ</h2>
                <h3>KİŞİSEL VERİLERİN KORUNMASI KANUNU KAPSAMINDA AYDINLATMA METNİ</h3>
            </div>

            <!-- 1 -->
            <section class="kvkk-bolum-ic" id="veri-sorumlusu">
                <h2 class="kvkk-bolum-ic__baslik">1. Veri Sorumlusu</h2>
                <p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında kişisel verileriniz, veri sorumlusu sıfatıyla <strong>Trabzonlu Kamu Çalışanları Derneği</strong> tarafından işlenmektedir.</p>
                <div class="kvkk-tablo">
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Unvan</span><span>Trabzonlu Kamu Çalışanları Derneği</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Adres</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Telefon</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">E-posta</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">KEP Adresi</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">İnternet Sitesi</span><span>—</span></div>
                </div>
            </section>

            <!-- 2 -->
            <section class="kvkk-bolum-ic" id="islenen-veriler">
                <h2 class="kvkk-bolum-ic__baslik">2. İşlenen Kişisel Veriler</h2>
                <p>İnternet sitemizi ziyaret etmeniz, iletişim formu aracılığıyla bizimle iletişime geçmeniz, etkinlik veya faaliyetlere başvurmanız ya da Dernek ile herhangi bir şekilde iletişim kurmanız halinde, işlemin niteliğine göre aşağıdaki kişisel verileriniz işlenebilecektir:</p>
                <ul class="kvkk-liste">
                    <li>Ad ve soyad</li>
                    <li>Telefon numarası</li>
                    <li>E-posta adresi</li>
                    <li>Meslek ve görev bilgileri</li>
                    <li>Dernek üyeliğine ilişkin bilgiler</li>
                    <li>Başvuru ve iletişim içerikleri</li>
                    <li>İnternet sitesi kullanımına ilişkin bilgiler</li>
                    <li>IP adresi</li>
                    <li>İşlem ve erişim tarih/saat bilgileri</li>
                    <li>Log kayıtları</li>
                    <li>Çerezler aracılığıyla elde edilen bilgiler</li>
                </ul>
                <p>Tarafınızca açıkça paylaşılmadığı sürece özel nitelikli kişisel verileriniz internet sitesi üzerinden talep edilmemektedir.</p>
            </section>

            <!-- 3 -->
            <section class="kvkk-bolum-ic" id="isleme-amac">
                <h2 class="kvkk-bolum-ic__baslik">3. Kişisel Verilerin İşlenme Amaçları</h2>
                <p>Kişisel verileriniz;</p>
                <ul class="kvkk-liste">
                    <li>Dernek faaliyetlerinin yürütülmesi</li>
                    <li>Dernek üyelik ve başvuru süreçlerinin yürütülmesi</li>
                    <li>Tarafınızdan iletilen soru, talep, öneri ve şikâyetlerin değerlendirilmesi</li>
                    <li>İletişim faaliyetlerinin yürütülmesi</li>
                    <li>Etkinlik, toplantı, eğitim ve organizasyonların gerçekleştirilmesi</li>
                    <li>Dernek faaliyetleri hakkında gerekli bilgilendirmelerin yapılması</li>
                    <li>İnternet sitesinin güvenliğinin ve işlevselliğinin sağlanması</li>
                    <li>Bilgi güvenliği süreçlerinin yürütülmesi</li>
                    <li>Hukuki yükümlülüklerin yerine getirilmesi</li>
                    <li>Yetkili kişi, kurum ve kuruluşlardan gelen hukuken geçerli taleplerin karşılanması</li>
                    <li>Hukuki iş ve işlemlerin yürütülmesi</li>
                    <li>Uyuşmazlıkların takibi ve sonuçlandırılması</li>
                </ul>
                <p>amaçlarıyla işlenebilecektir.</p>
            </section>

            <!-- 4 -->
            <section class="kvkk-bolum-ic" id="hukuki-sebep">
                <h2 class="kvkk-bolum-ic__baslik">4. Kişisel Verilerin İşlenmesinin Hukuki Sebepleri</h2>
                <p>Kişisel verileriniz, somut veri işleme faaliyetinin niteliğine göre KVKK'nın 5. maddesinde düzenlenen;</p>
                <ul class="kvkk-liste">
                    <li>Kanunlarda açıkça öngörülmesi</li>
                    <li>Hukuki yükümlülüğün yerine getirilmesi</li>
                    <li>Bir sözleşmenin kurulması veya ifasıyla doğrudan doğruya ilgili olması</li>
                    <li>Bir hakkın tesisi, kullanılması veya korunması</li>
                    <li>Veri sorumlusunun meşru menfaatinin bulunması</li>
                    <li>İlgili kişinin açık rızasının bulunması</li>
                </ul>
                <p>hukuki sebeplerinden bir veya birkaçına dayanılarak işlenebilecektir. Özel nitelikli kişisel veriler bakımından KVKK'nın 6. maddesinde düzenlenen şartlar ayrıca dikkate alınacaktır.</p>
            </section>

            <!-- 5 -->
            <section class="kvkk-bolum-ic" id="toplama-yontemi">
                <h2 class="kvkk-bolum-ic__baslik">5. Kişisel Verilerin Toplanma Yöntemi</h2>
                <p>Kişisel verileriniz;</p>
                <ul class="kvkk-liste">
                    <li>İnternet sitesi üzerindeki iletişim ve başvuru formları</li>
                    <li>E-posta</li>
                    <li>Telefon görüşmeleri</li>
                    <li>Elektronik ve fiziki başvurular</li>
                    <li>Dernek üyelik işlemleri</li>
                    <li>Etkinlik ve organizasyon başvuruları</li>
                    <li>İnternet sitesindeki teknik kayıtlar</li>
                    <li>Çerezler</li>
                </ul>
                <p>aracılığıyla otomatik veya otomatik olmayan yöntemlerle toplanabilecektir.</p>
            </section>

            <!-- 6 -->
            <section class="kvkk-bolum-ic" id="aktarim">
                <h2 class="kvkk-bolum-ic__baslik">6. Kişisel Verilerin Aktarılması</h2>
                <p>Kişisel verileriniz, işleme amacıyla sınırlı ve ölçülü olmak kaydıyla ve KVKK'nın 8. ve 9. maddelerinde öngörülen şartlara uygun olarak;</p>
                <ul class="kvkk-liste">
                    <li>Yetkili kamu kurum ve kuruluşlarına</li>
                    <li>Yetkili kişi veya kuruluşlara</li>
                    <li>Hukuki, mali ve teknik hizmet alınan kişi veya kuruluşlara</li>
                    <li>İnternet sitesi ve bilişim altyapısı hizmet sağlayıcılarına</li>
                </ul>
                <p>aktarılabilecektir. Yurt dışına veri aktarımı söz konusu olması halinde KVKK'nın 9. maddesinde öngörülen şartlar ayrıca uygulanacaktır.</p>
            </section>

            <!-- 7 -->
            <section class="kvkk-bolum-ic" id="saklama">
                <h2 class="kvkk-bolum-ic__baslik">7. Kişisel Verilerin Saklanma Süresi</h2>
                <p>Kişisel verileriniz, ilgili işleme amaçlarının gerektirdiği süre boyunca ve ilgili mevzuatta öngörülen saklama süreleri dikkate alınarak muhafaza edilir.</p>
                <p>İşleme amaçlarının ortadan kalkması ve mevzuattan kaynaklanan herhangi bir saklama yükümlülüğünün bulunmaması halinde kişisel verileriniz KVKK'ya uygun olarak silinir, yok edilir veya anonim hâle getirilir.</p>
            </section>

            <!-- 8 -->
            <section class="kvkk-bolum-ic" id="haklar">
                <h2 class="kvkk-bolum-ic__baslik">8. İlgili Kişinin Hakları</h2>
                <p>KVKK'nın 11. maddesi kapsamında;</p>
                <ul class="kvkk-liste">
                    <li>Kişisel verilerinizin işlenip işlenmediğini öğrenme</li>
                    <li>İşlenmişse buna ilişkin bilgi talep etme</li>
                    <li>İşlenme amacını ve amacına uygun kullanılıp kullanılmadığını öğrenme</li>
                    <li>Yurt içinde veya yurt dışında kişisel verilerinizin aktarıldığı üçüncü kişileri bilme</li>
                    <li>Eksik veya yanlış işlenmiş kişisel verilerinizin düzeltilmesini isteme</li>
                    <li>KVKK'da öngörülen şartlar çerçevesinde kişisel verilerinizin silinmesini veya yok edilmesini isteme</li>
                    <li>Yapılan düzeltme, silme veya yok etme işlemlerinin aktarılan üçüncü kişilere bildirilmesini isteme</li>
                    <li>Münhasıran otomatik sistemler vasıtasıyla analiz edilmesi suretiyle aleyhinize bir sonucun ortaya çıkmasına itiraz etme</li>
                    <li>Kanuna aykırı olarak işlenmesi nedeniyle zarara uğramanız hâlinde zararın giderilmesini talep etme</li>
                </ul>
                <p>haklarına sahipsiniz.</p>
            </section>

            <!-- 9 -->
            <section class="kvkk-bolum-ic" id="basvuru">
                <h2 class="kvkk-bolum-ic__baslik">9. Başvuru Yöntemi</h2>
                <p>KVKK kapsamındaki haklarınıza ilişkin taleplerinizi, KVKK ve ilgili mevzuatta öngörülen usul ve esaslara uygun olarak aşağıdaki kanallar üzerinden Derneğimize iletebilirsiniz:</p>
                <div class="kvkk-tablo">
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Yazılı olarak</span><span>[DERNEK ADRESİ]</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">E-posta yoluyla</span><span>[E-POSTA ADRESİ]</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">KEP yoluyla</span><span>[KEP ADRESİ]</span></div>
                </div>
                <p>Başvurularınız, KVKK'da öngörülen süre içerisinde değerlendirilerek sonuçlandırılacaktır.</p>
            </section>

            <!-- 10 -->
            <section class="kvkk-bolum-ic" id="yururluk">
                <h2 class="kvkk-bolum-ic__baslik">10. Yürürlük</h2>
                <p>İşbu Aydınlatma Metni yürürlüğe girmiştir. Trabzonlu Kamu Çalışanları Derneği, mevzuat değişiklikleri ve kişisel veri işleme faaliyetlerindeki değişiklikler doğrultusunda işbu Aydınlatma Metni'ni güncelleme hakkını saklı tutar.</p>
            </section>

            <hr class="kvkk-ayrac">

            <!-- Açık Rıza Metni -->
            <section class="kvkk-bolum-ic kvkk-bolum-ic--vurgulu" id="acik-riza">
                <h2 class="kvkk-bolum-ic__baslik">TRABZONLU KAMU ÇALIŞANLARI DERNEĞİ — AÇIK RIZA METNİ</h2>
                <p>6698 sayılı Kişisel Verilerin Korunması Kanunu kapsamında tarafıma sunulan Aydınlatma Metni'ni okuduğumu ve kişisel verilerimin hangi amaçlarla işleneceği, kimlere ve hangi amaçlarla aktarılabileceği, toplanma yöntemi ve hukuki sebepleri ile KVKK kapsamındaki haklarım hakkında bilgilendirildiğimi kabul ve beyan ederim.</p>
                <p>Aşağıda belirtilen kişisel veri işleme faaliyetleri bakımından tercihimi, her bir faaliyet için ayrı ayrı ve özgür irademle belirtebilirim:</p>
                <ul class="kvkk-liste">
                    <li>Trabzonlu Kamu Çalışanları Derneği tarafından düzenlenen etkinlik, eğitim, toplantı ve benzeri faaliyetler hakkında tarafıma <strong>e-posta yoluyla bilgilendirme gönderilmesine</strong> açık rıza veriyorum.</li>
                    <li>Trabzonlu Kamu Çalışanları Derneği tarafından düzenlenen etkinlik, eğitim, toplantı ve benzeri faaliyetler hakkında tarafıma <strong>SMS/telefon yoluyla bilgilendirme gönderilmesine</strong> açık rıza veriyorum.</li>
                    <li>Kişisel verilerimin işlenmesine açık rıza veriyorum.</li>
                </ul>
                <p>Açık rızamı herhangi bir zamanda geri çekebileceğimi bildiğimi kabul ederim. Açık rızanın geri çekilmesi, geri çekilmeden önce bu rızaya dayanılarak gerçekleştirilmiş veri işleme faaliyetlerinin hukuka uygunluğunu etkilemez.</p>
            </section>

            <hr class="kvkk-ayrac">

            <!-- Çerez Politikası -->
            <section class="kvkk-bolum-ic" id="cerez">
                <h2 class="kvkk-bolum-ic__baslik">TRABZONLU KAMU ÇALIŞANLARI DERNEĞİ — ÇEREZ POLİTİKASI</h2>

                <h3 class="kvkk-alt-baslik">1. Amaç</h3>
                <p>Trabzonlu Kamu Çalışanları Derneği olarak internet sitemizin güvenli, düzgün ve etkin şekilde çalışmasını sağlamak amacıyla çerezlerden yararlanabilmekteyiz. Bu Çerez Politikası, internet sitemizi ziyaret eden kullanıcıların çerezler aracılığıyla elde edilen verilerinin nasıl işlendiği konusunda bilgilendirme amacı taşımaktadır.</p>

                <h3 class="kvkk-alt-baslik">2. Çerez Nedir?</h3>
                <p>Çerezler, internet sitelerinin kullanıcıların cihazlarına yerleştirdiği küçük metin dosyalarıdır. Çerezler; internet sitesinin düzgün çalışması, kullanıcı tercihlerinin hatırlanması, güvenliğin sağlanması, site performansının ölçülmesi veya analiz yapılması gibi farklı amaçlarla kullanılabilir.</p>

                <h3 class="kvkk-alt-baslik">3. Kullanılan Çerez Türleri</h3>
                <p><strong>A. Zorunlu Çerezler —</strong> İnternet sitesinin temel fonksiyonlarının çalışması, güvenliğin sağlanması ve kullanıcının talep ettiği hizmetin sunulması için gerekli olan çerezlerdir.</p>
                <p><strong>B. İşlevsel Çerezler —</strong> Kullanıcı tercihlerini ve seçimlerini hatırlamak veya internet sitesinin kullanıcı deneyimini geliştirmek amacıyla kullanılabilir.</p>
                <p><strong>C. Performans ve Analitik Çerezler —</strong> İnternet sitesinin kullanım şeklinin analiz edilmesi, ziyaretçi sayısının ölçülmesi ve sitenin performansının geliştirilmesi amacıyla kullanılabilir.</p>
                <p><strong>D. Reklam ve Pazarlama Çerezleri —</strong> Kullanıcıların internet üzerindeki hareketlerinin belirli amaçlarla analiz edilmesi veya kişiselleştirilmiş reklam/pazarlama faaliyetlerinin yürütülmesi amacıyla kullanılabilecek çerezlerdir. Bu tür çerezlerin kullanılması bakımından açık rıza gerektiği durumlarda, kullanıcıların aktif tercihleri alınmadan bu çerezler çalıştırılmayacaktır.</p>

                <h3 class="kvkk-alt-baslik">4. Çerez Tercihlerinin Yönetimi</h3>
                <p>İnternet sitemize ilk girişte, zorunlu olmayan çerezler bakımından kullanıcıya tercihlerini belirleme imkânı sunulacaktır. Açık rıza gerektiren çerezler bakımından kullanıcıların aktif tercihleri alınmadan ilgili çerezler çalıştırılmayacaktır.</p>

                <h3 class="kvkk-alt-baslik">5. Üçüncü Taraf Çerezleri</h3>
                <p>İnternet sitesinde üçüncü taraf hizmet sağlayıcılar tarafından kullanılan çerezlerin bulunması hâlinde, ilgili üçüncü tarafların kimliği, çerezlerin amacı ve saklama süresi ayrıca belirtilir.</p>

                <h3 class="kvkk-alt-baslik">6. Kişisel Verilerin İşlenmesi</h3>
                <p>Çerezler aracılığıyla kişisel veri işlenmesi hâlinde söz konusu faaliyetler KVKK ve ilgili mevzuata uygun şekilde gerçekleştirilecektir. Açık rıza gerektiren çerezler bakımından kullanıcıların açık rızası alınmadan kişisel veri işlenmeye başlanmayacaktır.</p>

                <h3 class="kvkk-alt-baslik">7. İletişim</h3>
                <p>Çerezler ve kişisel verileriniz hakkında sorularınız veya talepleriniz için:</p>
                <div class="kvkk-tablo">
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Unvan</span><span>Trabzonlu Kamu Çalışanları Derneği</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Adres</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">E-posta</span><span>—</span></div>
                    <div class="kvkk-tablo__satir"><span class="kvkk-tablo__etiket">Telefon</span><span>—</span></div>
                </div>
            </section>

        </article>
    </div>
</section>
