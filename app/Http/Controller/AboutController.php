<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Env;
use App\Core\Http\Response;
use PDO;
use PDOException;
use Throwable;

/**
 * "Hakkımızda" alt kırılım sayfaları.
 *
 * İçerikler ikinci fazda yönetim panelinden beslenecektir; sayfalar şu an
 * kurumsal çerçeve ve bilgilendirici boş durum (empty state) ile yayındadır.
 */
final class AboutController
{
    public function __construct(private readonly PageResponder $responder)
    {
    }

    public function association(): Response
    {
        $seo = $this->responder->seo(
            title: 'Derneğimiz',
            description: 'Trabzonlu Kamu Çalışanları Derneği’nin kuruluş amacı, vizyonu ve '
                . 'çalışma ilkeleri hakkında bilgi edinin.',
            canonicalPath: '/hakkimizda/dernegimiz',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Derneğimiz', 'path' => '/hakkimizda/dernegimiz'],
            ],
        );

        return $this->responder->page('pages/association', $seo, [
            'styles' => ['about.css'],
        ]);
    }

    public function partners(): Response
    {
        $seo = $this->responder->seo(
            title: 'Anlaşmalı Kurumlar',
            description: 'Üyelerimize özel indirim ve avantaj sağlayan anlaşmalı kurum ve '
                . 'kuruluşlarımızın listesi.',
            canonicalPath: '/hakkimizda/anlasmali-kurumlar',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Anlaşmalı Kurumlar', 'path' => '/hakkimizda/anlasmali-kurumlar'],
            ],
        );

        return $this->responder->page('pages/partners', $seo, [
            'styles' => ['about.css'],
        ]);
    }

    public function representatives(): Response
    {
        $seo = $this->responder->seo(
            title: 'Temsilci Ağımız',
            description: 'Türkiye genelinde 81 ilde görev yapan il ve ilçe temsilcilerimize '
                . 'buradan ulaşabilirsiniz.',
            canonicalPath: '/hakkimizda/temsilci-agimiz',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Temsilci Ağımız', 'path' => '/hakkimizda/temsilci-agimiz'],
            ],
        );

        /** @var array<string, array{il_adi: string, temsilci: string, telefon: string, eposta: string, ilceler: list<mixed>}> $temsilciler */
        $temsilciler = $this->fetchIlBaskanlari();

        /** @var list<array{plate: string, il: string, yol: string}> $haritaYollari */
        $haritaYollari = require dirname(__DIR__, 3) . '/resources/data/turkey-map.php';

        return $this->responder->page('pages/representatives', $seo, [
            'styles'        => ['about.css', 'representatives.css'],
            'scripts'       => ['representatives.js'],
            'temsilciler'   => $temsilciler,
            'haritaYollari' => $haritaYollari,
        ]);
    }

    public function gallery(): Response
    {
        $seo = $this->responder->seo(
            title: 'Galeri',
            description: 'Etkinliklerimizden, buluşmalarımızdan ve kültürel programlarımızdan '
                . 'fotoğraf ve videolar.',
            canonicalPath: '/hakkimizda/galeri',
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => 'Galeri', 'path' => '/hakkimizda/galeri'],
            ],
        );

        /** @var list<array{dosya:string,alt:string,boyut:'buyuk'|'normal'}> $gorseller */
        $gorseller = require dirname(__DIR__, 3) . '/resources/data/gallery.php';

        return $this->responder->page('pages/gallery', $seo, [
            'styles'    => ['about.css', 'gallery.css'],
            'scripts'   => ['gallery.js'],
            'gorseller' => $gorseller,
        ]);
    }

    /**
     * Veritabanındaki İl Başkanı rolündeki üyeleri plaka koduna göre indekslenmiş
     * temsilci dizisine dönüştürür.
     *
     * Neden bu yöntem: Harita görünümü statik PHP dosyasına bağımlıydı; yönetim
     * panelinde Il Başkanı olarak atanan üyeler artık otomatik olarak haritaya
     * yansır. Bağlantı hatası oluşursa sessizce boş dizi döner — site hiçbir
     * durumda çökmez.
     *
     * Kayıt koşulu: onay_durumu = 'onayli' VE
     *   (temsilci_turu = 'İl Başkanı' VEYA ek_gorev = 'İl Başkanı')
     * Öncelik: birden fazla il başkanı varsa alfabetik ilk alınır (ORDER BY adi_soyadi).
     *
     * @return array<string, array{il_adi: string, temsilci: string, telefon: string, eposta: string, ilceler: list<mixed>}>
     */
    private function fetchIlBaskanlari(): array
    {
        /** Türkiye 81 il — plaka kodu ↔ il adı eşlemesi. */
        static $plakalar = [
            '01' => 'Adana',    '02' => 'Adıyaman', '03' => 'Afyonkarahisar',
            '04' => 'Ağrı',     '05' => 'Amasya',   '06' => 'Ankara',
            '07' => 'Antalya',  '08' => 'Artvin',   '09' => 'Aydın',
            '10' => 'Balıkesir','11' => 'Bilecik',  '12' => 'Bingöl',
            '13' => 'Bitlis',   '14' => 'Bolu',     '15' => 'Burdur',
            '16' => 'Bursa',    '17' => 'Çanakkale','18' => 'Çankırı',
            '19' => 'Çorum',    '20' => 'Denizli',  '21' => 'Diyarbakır',
            '22' => 'Edirne',   '23' => 'Elazığ',   '24' => 'Erzincan',
            '25' => 'Erzurum',  '26' => 'Eskişehir','27' => 'Gaziantep',
            '28' => 'Giresun',  '29' => 'Gümüşhane','30' => 'Hakkari',
            '31' => 'Hatay',    '32' => 'Isparta',  '33' => 'Mersin',
            '34' => 'İstanbul', '35' => 'İzmir',    '36' => 'Kars',
            '37' => 'Kastamonu','38' => 'Kayseri',  '39' => 'Kırklareli',
            '40' => 'Kırşehir', '41' => 'Kocaeli',  '42' => 'Konya',
            '43' => 'Kütahya',  '44' => 'Malatya',  '45' => 'Manisa',
            '46' => 'Kahramanmaraş','47' => 'Mardin','48' => 'Muğla',
            '49' => 'Muş',      '50' => 'Nevşehir', '51' => 'Niğde',
            '52' => 'Ordu',     '53' => 'Rize',     '54' => 'Sakarya',
            '55' => 'Samsun',   '56' => 'Siirt',    '57' => 'Sinop',
            '58' => 'Sivas',    '59' => 'Tekirdağ', '60' => 'Tokat',
            '61' => 'Trabzon',  '62' => 'Tunceli',  '63' => 'Şanlıurfa',
            '64' => 'Uşak',     '65' => 'Van',      '66' => 'Yozgat',
            '67' => 'Zonguldak','68' => 'Aksaray',  '69' => 'Bayburt',
            '70' => 'Karaman',  '71' => 'Kırıkkale','72' => 'Batman',
            '73' => 'Şırnak',   '74' => 'Bartın',   '75' => 'Ardahan',
            '76' => 'Iğdır',    '77' => 'Yalova',   '78' => 'Karabük',
            '79' => 'Kilis',    '80' => 'Osmaniye', '81' => 'Düzce',
        ];

        // Ters eşleme: 'Trabzon' → '61'
        $adToPlaka = array_flip(array_map('mb_strtolower', $plakalar));

        try {
            $pdo = new PDO(
                sprintf(
                    'mysql:host=%s;port=%s;dbname=%s;charset=%s',
                    Env::string('DB_HOST', '127.0.0.1'),
                    Env::string('DB_PORT', '3306'),
                    Env::string('DB_DATABASE', ''),
                    Env::string('DB_CHARSET', 'utf8mb4'),
                ),
                Env::string('DB_USERNAME', ''),
                Env::string('DB_PASSWORD', ''),
                [
                    PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
                    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                    PDO::ATTR_EMULATE_PREPARES   => false,
                ]
            );

            $stmt = $pdo->query(
                "SELECT adi_soyadi, ikamet_ili, telefon
                   FROM dernek_uyeler
                  WHERE onay_durumu = 'onayli'
                    AND (
                        temsilci_turu = 'İl Başkanı'
                        OR ek_gorev   = 'İl Başkanı'
                    )
                  ORDER BY adi_soyadi ASC"
            );

            if ($stmt === false) {
                return [];
            }

            $temsilciler = [];

            foreach ($stmt->fetchAll() as $satir) {
                $ilAdi = trim((string) ($satir['ikamet_ili'] ?? ''));

                if ($ilAdi === '') {
                    continue;
                }

                $plaka = $adToPlaka[mb_strtolower($ilAdi)] ?? null;

                if ($plaka === null || isset($temsilciler[$plaka])) {
                    // Bilinmeyen il veya aynı il için zaten bir kayıt var — atla.
                    continue;
                }

                $temsilciler[$plaka] = [
                    'il_adi'   => $plakalar[$plaka],
                    'temsilci' => trim((string) ($satir['adi_soyadi'] ?? '')),
                    'telefon'  => trim((string) ($satir['telefon'] ?? '')),
                    'eposta'   => '',
                    'ilceler'  => [],
                ];
            }

            return $temsilciler;
        } catch (PDOException $e) {
            // Bağlantı veya sorgu hatası: üretimde sızdırma olmasın, sessizce boş dön.
            error_log('Temsilci haritası DB hatası: ' . $e->getMessage());

            return [];
        } catch (Throwable $e) {
            error_log('Temsilci haritası beklenmeyen hata: ' . $e->getMessage());

            return [];
        }
    }

    private function section(string $title, string $description, string $path): Response
    {
        $seo = $this->responder->seo(
            title: $title,
            description: $description,
            canonicalPath: $path,
            breadcrumbs: [
                ['label' => 'Hakkımızda', 'path' => '/hakkimizda/dernegimiz'],
                ['label' => $title, 'path' => $path],
            ],
        );

        return $this->responder->page('pages/section', $seo, [
            'pageTitle' => $title,
            'pageDescription' => $description,
            'parentLabel' => 'Hakkımızda',
        ]);
    }
}
