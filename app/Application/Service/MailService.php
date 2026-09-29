<?php

declare(strict_types=1);

namespace App\Application\Service;

use App\Core\Config;
use App\Core\Env;

/**
 * SMTP üzerinden e-posta gönderir.
 *
 * Neden yerleşik implementasyon: harici PHPMailer/Symfony Mailer
 * bağımlılığı olmadan cPanel SMTP (SSL port 465) ile çalışır.
 * Tüm SMTP kimlik bilgileri .env'den okunur; kaynak kodda saklanmaz.
 *
 * @see https://datatracker.ietf.org/doc/html/rfc5321
 */
final class MailService
{
    private readonly string $host;
    private readonly int    $port;
    private readonly string $encryption;
    private readonly string $username;
    private readonly string $password;
    private readonly string $fromAddress;
    private readonly string $fromName;

    public function __construct(private readonly Config $config)
    {
        $this->host        = Env::string('MAIL_HOST',         'mail.trabzonlukamucalisanlaridernegi.com');
        $this->port        = (int) Env::string('MAIL_PORT',   '465');
        $this->encryption  = Env::string('MAIL_ENCRYPTION',   'ssl');
        $this->username    = Env::string('MAIL_USERNAME',      '');
        $this->password    = Env::string('MAIL_PASSWORD',      '');
        $this->fromAddress = Env::string('MAIL_FROM_ADDRESS',  'info@trabzonlukamucalisanlaridernegi.com');
        $this->fromName    = Env::string('MAIL_FROM_NAME',     'Trabzonlu Kamu Çalışanları Derneği');
    }

    /**
     * Tek veya birden fazla alıcıya düz-metin / HTML e-posta gönderir.
     *
     * @param string|string[] $to      Alıcı adresi veya adresleri
     * @param string          $subject Konu satırı
     * @param string          $text    Düz metin gövdesi
     * @param string          $html    HTML gövdesi (boş bırakılırsa yalnızca düz metin gönderilir)
     * @param string          $replyTo Reply-To adresi (opsiyonel)
     */
    public function send(
        string|array $to,
        string $subject,
        string $text,
        string $html = '',
        string $replyTo = '',
    ): bool {
        $recipients = is_array($to) ? $to : [$to];

        $boundary  = 'b' . bin2hex(random_bytes(12));
        $hasHtml   = $html !== '';

        // ── Başlıklar ──────────────────────────────────────────────────────
        $fromEncoded = $this->encodeHeader($this->fromName) . ' <' . $this->fromAddress . '>';
        $subjectEnc  = $this->encodeHeader($subject);

        $headers  = "From: {$fromEncoded}\r\n";
        $headers .= 'To: ' . implode(', ', $recipients) . "\r\n";
        $headers .= "Subject: {$subjectEnc}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Date: " . date('r') . "\r\n";
        $headers .= "Message-ID: <" . uniqid('tkcd', true) . "@trabzonlukamucalisanlaridernegi.com>\r\n";

        if ($replyTo !== '') {
            $headers .= "Reply-To: {$replyTo}\r\n";
        }

        // ── Gövde ──────────────────────────────────────────────────────────
        if ($hasHtml) {
            $headers .= "Content-Type: multipart/alternative; boundary=\"{$boundary}\"\r\n";
            $body  = "--{$boundary}\r\n";
            $body .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($text)) . "\r\n";
            $body .= "--{$boundary}\r\n";
            $body .= "Content-Type: text/html; charset=UTF-8\r\n";
            $body .= "Content-Transfer-Encoding: base64\r\n\r\n";
            $body .= chunk_split(base64_encode($html)) . "\r\n";
            $body .= "--{$boundary}--";
        } else {
            $headers .= "Content-Type: text/plain; charset=UTF-8\r\n";
            $headers .= "Content-Transfer-Encoding: base64\r\n";
            $body     = chunk_split(base64_encode($text));
        }

        $message = $headers . "\r\n" . $body;

        return $this->smtpSend($recipients, $message);
    }

    // ── SMTP iletişimi ─────────────────────────────────────────────────────

    /**
     * Raw SMTP konuşmasını yürütür.
     *
     * @param string[] $recipients
     */
    private function smtpSend(array $recipients, string $rawMessage): bool
    {
        $prefix  = ($this->encryption === 'ssl') ? 'ssl://' : '';
        $timeout = 15;

        $socket = @fsockopen("{$prefix}{$this->host}", $this->port, $errno, $errstr, $timeout);

        if ($socket === false) {
            error_log("[MailService] Bağlantı hatası ({$errno}): {$errstr}");
            return false;
        }

        stream_set_timeout($socket, $timeout);

        try {
            // Karşılama
            if (!$this->expect($socket, '220')) return false;
            if (!$this->cmd($socket, "EHLO trabzonlukamucalisanlaridernegi.com", '250')) return false;

            // STARTTLS (port 587 için)
            if ($this->encryption === 'tls') {
                if (!$this->cmd($socket, 'STARTTLS', '220')) return false;
                stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
                if (!$this->cmd($socket, "EHLO trabzonlukamucalisanlaridernegi.com", '250')) return false;
            }

            // AUTH LOGIN
            if (!$this->cmd($socket, 'AUTH LOGIN', '334')) return false;
            if (!$this->cmd($socket, base64_encode($this->username), '334')) return false;
            if (!$this->cmd($socket, base64_encode($this->password), '235')) return false;

            // Gönderici
            if (!$this->cmd($socket, "MAIL FROM:<{$this->fromAddress}>", '250')) return false;

            // Alıcılar
            foreach ($recipients as $rcpt) {
                $rcpt = trim($rcpt, '<> ');
                if (!$this->cmd($socket, "RCPT TO:<{$rcpt}>", '250')) return false;
            }

            // Veri
            if (!$this->cmd($socket, 'DATA', '354')) return false;
            fwrite($socket, $rawMessage . "\r\n.\r\n");
            if (!$this->expect($socket, '250')) return false;

            $this->cmd($socket, 'QUIT', '221');
            return true;
        } catch (\Throwable $e) {
            error_log('[MailService] İstisna: ' . $e->getMessage());
            return false;
        } finally {
            fclose($socket);
        }
    }

    /**
     * Komut gönder, beklenen yanıt kodu ile doğrula.
     *
     * @param resource $socket
     */
    private function cmd($socket, string $command, string $expectedCode): bool
    {
        fwrite($socket, $command . "\r\n");
        return $this->expect($socket, $expectedCode);
    }

    /**
     * Sunucu yanıtını oku ve beklenen kod ile karşılaştır.
     *
     * @param resource $socket
     */
    private function expect($socket, string $expectedCode): bool
    {
        $response = '';
        while (!feof($socket)) {
            $line = fgets($socket, 512);
            if ($line === false) break;
            $response .= $line;
            // Çok satırlı yanıt: "250-" devam eder, "250 " sona erer
            if (isset($line[3]) && $line[3] === ' ') break;
        }
        $ok = str_starts_with(trim($response), $expectedCode);
        if (!$ok) {
            error_log("[MailService] Beklenen {$expectedCode}, alınan: " . trim($response));
        }
        return $ok;
    }

    /**
     * RFC 2047 MIME başlık kodlaması (UTF-8 Q-encoding).
     */
    private function encodeHeader(string $value): string
    {
        if (mb_detect_encoding($value, 'ASCII', true)) {
            return $value;
        }
        return mb_encode_mimeheader($value, 'UTF-8', 'Q', "\r\n");
    }
}
