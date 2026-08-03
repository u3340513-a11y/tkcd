<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\PageResponder;
use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;

/**
 * İletişim formu denetleyicisi.
 *
 * GET  /iletisim → formu göster (opsiyonel durum parametresiyle)
 * POST /iletisim → formu doğrula ve mail gönder, ardından yönlendir
 *
 * Neden `mail()`: Harici bir SMTP bağımlılığı olmadan çalışabilmek için
 * PHP'nin yerleşik mail fonksiyonu kullanılır. Admin panel fazında bu
 * bölüm bir MailService soyutlaması ile değiştirilecektir.
 */
final class ContactController
{
    public function __construct(
        private readonly PageResponder $responder,
        private readonly Config $config,
        private readonly Request $request,
    ) {
    }

    public function index(): Response
    {
        $seo = $this->responder->seo(
            title: 'İletişim',
            description: 'Derneğimize ulaşabileceğiniz adres, telefon, e-posta ve '
                . 'iletişim formu.',
            canonicalPath: '/iletisim',
            breadcrumbs: [['label' => 'İletişim', 'path' => '/iletisim']],
        );

        $durum = trim((string) ($this->request->query['durum'] ?? ''));

        return $this->responder->page('pages/contact', $seo, [
            'styles' => ['contact.css'],
            'durum'  => in_array($durum, ['basarili', 'hata'], true) ? $durum : null,
        ]);
    }

    public function store(): Response
    {
        $ad     = trim((string) ($this->request->body['ad'] ?? ''));
        $eposta = trim((string) ($this->request->body['eposta'] ?? ''));
        $konu   = trim((string) ($this->request->body['konu'] ?? ''));
        $mesaj  = trim((string) ($this->request->body['mesaj'] ?? ''));

        if ($ad === '' || $eposta === '' || $konu === '' || !filter_var($eposta, FILTER_VALIDATE_EMAIL)) {
            return Response::redirect('/iletisim?durum=hata');
        }

        $alici   = $this->config->string('site.contact.email');
        $baslik  = mb_encode_mimeheader('[İletişim Formu] ' . $konu, 'UTF-8', 'Q');
        $govde   = implode("\r\n", [
            'Gönderen : ' . $ad,
            'E-posta  : ' . $eposta,
            '',
            'Mesaj:',
            $mesaj,
        ]);
        $headers = implode("\r\n", [
            'From: noreply@trabzonlukamucalisanlaridernegi.com',
            'Reply-To: ' . $eposta,
            'Content-Type: text/plain; charset=UTF-8',
            'Content-Transfer-Encoding: 8bit',
        ]);

        $gonderildi = @mail($alici, $baslik, $govde, $headers);

        return Response::redirect($gonderildi ? '/iletisim?durum=basarili' : '/iletisim?durum=hata');
    }
}
