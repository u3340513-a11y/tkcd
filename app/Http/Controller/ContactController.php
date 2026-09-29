<?php

declare(strict_types=1);

namespace App\Http\Controller;

use App\Application\Service\MailService;
use App\Application\Service\PageResponder;
use App\Core\Config;
use App\Core\Http\Request;
use App\Core\Http\Response;
use App\Infrastructure\Persistence\ContactRepository;

/**
 * İletişim formu denetleyicisi.
 *
 * GET  /iletisim → formu göster (opsiyonel durum parametresiyle)
 * POST /iletisim → doğrula, SMTP ile mail gönder, DB'ye kaydet, yönlendir
 *
 * İki hedef:
 *   1. info@trabzonlukamucalisanlaridernegi.com adresine SMTP ile e-posta
 *   2. iletisim_mesajlari tablosuna kayıt (geliştirici yönetim panelinde görür)
 */
final class ContactController
{
    public function __construct(
        private readonly PageResponder      $responder,
        private readonly Config             $config,
        private readonly Request            $request,
        private readonly MailService        $mailService,
        private readonly ContactRepository  $contactRepository,
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
        $ad     = trim((string) ($this->request->body['ad']     ?? ''));
        $eposta = trim((string) ($this->request->body['eposta'] ?? ''));
        $konu   = trim((string) ($this->request->body['konu']   ?? ''));
        $mesaj  = trim((string) ($this->request->body['mesaj']  ?? ''));

        if ($ad === '' || $eposta === '' || $konu === '' || $mesaj === ''
            || !filter_var($eposta, FILTER_VALIDATE_EMAIL)
        ) {
            return Response::redirect('/iletisim?durum=hata');
        }

        // XSS koruması — DB'ye ve mail'e sadece temizlenmiş veri gider
        $adTemiz     = htmlspecialchars($ad,     ENT_QUOTES, 'UTF-8');
        $epostaTemiz = htmlspecialchars($eposta, ENT_QUOTES, 'UTF-8');
        $konuTemiz   = htmlspecialchars($konu,   ENT_QUOTES, 'UTF-8');
        $mesajTemiz  = htmlspecialchars($mesaj,  ENT_QUOTES, 'UTF-8');

        $alici = $this->config->string('site.contact.email');
        $ip    = $_SERVER['HTTP_X_FORWARDED_FOR'] ?? $_SERVER['REMOTE_ADDR'] ?? '';

        // ── 1. SMTP ile e-posta gönder ──────────────────────────────────────
        $text = implode("\n", [
            "Gönderen : {$adTemiz}",
            "E-posta  : {$epostaTemiz}",
            '',
            'Mesaj:',
            $mesajTemiz,
        ]);

        $html = <<<HTML
<!DOCTYPE html>
<html lang="tr">
<head><meta charset="UTF-8"><title>İletişim Formu</title></head>
<body style="font-family:Arial,sans-serif;color:#1a1a2e;max-width:600px;margin:auto;padding:24px;">
  <div style="background:#c62828;padding:16px 24px;border-radius:8px 8px 0 0;">
    <h1 style="color:#fff;margin:0;font-size:18px;">📬 Yeni İletişim Mesajı</h1>
  </div>
  <div style="border:1px solid #e2e8f0;border-top:none;padding:24px;border-radius:0 0 8px 8px;">
    <table style="width:100%;border-collapse:collapse;">
      <tr><td style="padding:8px 0;color:#64748b;width:110px;">Gönderen</td>
          <td style="padding:8px 0;font-weight:600;">{$adTemiz}</td></tr>
      <tr><td style="padding:8px 0;color:#64748b;">E-posta</td>
          <td style="padding:8px 0;"><a href="mailto:{$epostaTemiz}">{$epostaTemiz}</a></td></tr>
      <tr><td style="padding:8px 0;color:#64748b;">Konu</td>
          <td style="padding:8px 0;font-weight:600;">{$konuTemiz}</td></tr>
    </table>
    <hr style="border:none;border-top:1px solid #e2e8f0;margin:16px 0;">
    <p style="color:#64748b;margin-bottom:8px;">Mesaj:</p>
    <div style="background:#f8fafc;padding:16px;border-radius:6px;border-left:4px solid #c62828;white-space:pre-wrap;">{$mesajTemiz}</div>
  </div>
  <p style="text-align:center;color:#94a3b8;font-size:12px;margin-top:16px;">
    Trabzonlu Kamu Çalışanları Derneği — İletişim Formu
  </p>
</body>
</html>
HTML;

        $mailGonderildi = $this->mailService->send(
            to:      $alici,
            subject: '[İletişim Formu] ' . $konuTemiz,
            text:    $text,
            html:    $html,
            replyTo: $epostaTemiz,
        );

        // ── 2. DB'ye kaydet (mail başarısız olsa bile kaydedilir) ───────────
        $this->contactRepository->save([
            'ad'         => $adTemiz,
            'eposta'     => $epostaTemiz,
            'konu'       => $konuTemiz,
            'mesaj'      => $mesajTemiz,
            'mail_durum' => $mailGonderildi ? 'gonderildi' : 'hata',
            'ip_adresi'  => substr((string) $ip, 0, 45),
        ]);

        return Response::redirect($mailGonderildi ? '/iletisim?durum=basarili' : '/iletisim?durum=hata');
    }
}
