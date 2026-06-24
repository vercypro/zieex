<?php
declare(strict_types=1);

namespace Zieex\Mail;

class Mail
{
    private string $driver   = 'mail';
    private string $from     = '';
    private string $fromName = '';
    private string $to       = '';
    private string $subject  = '';
    private string $body     = '';
    private bool   $isHtml   = true;
    private array  $cc       = [];
    private array  $bcc      = [];

    public static function driver(string $driver = 'smtp'): static
    {
        $mail = new static();
        $mail->driver = $driver;
        return $mail;
    }

    public function from(string $email, string $name = ''): static
    {
        $this->from     = $email;
        $this->fromName = $name;
        return $this;
    }

    public function to(string $email): static
    {
        $this->to = $email;
        return $this;
    }

    public function cc(string $email): static
    {
        $this->cc[] = $email;
        return $this;
    }

    public function bcc(string $email): static
    {
        $this->bcc[] = $email;
        return $this;
    }

    public function subject(string $subject): static
    {
        $this->subject = $subject;
        return $this;
    }

    public function html(string $body): static
    {
        $this->body   = $body;
        $this->isHtml = true;
        return $this;
    }

    public function text(string $body): static
    {
        $this->body   = $body;
        $this->isHtml = false;
        return $this;
    }

    public function view(string $template, array $data = []): static
    {
        $this->body   = \Zieex\Template\View::render($template, $data);
        $this->isHtml = true;
        return $this;
    }

    public function send(): bool
    {
        $from = $this->from ?: env('MAIL_FROM', 'noreply@example.com');
        $name = $this->fromName ?: env('MAIL_FROM_NAME', env('APP_NAME', 'Zieex'));

        return match ($this->driver) {
            'smtp'    => $this->sendSmtp($from, $name),
            'resend'  => $this->sendResend($from, $name),
            'api'     => $this->sendApi($from, $name),
            default   => $this->sendMail($from, $name),
        };
    }

    private function sendMail(string $from, string $name): bool
    {
        $headers  = "From: {$name} <{$from}>\r\n";
        $headers .= "Reply-To: {$from}\r\n";
        if ($this->isHtml) {
            $headers .= "MIME-Version: 1.0\r\n";
            $headers .= "Content-Type: text/html; charset=UTF-8\r\n";
        }
        return mail($this->to, $this->subject, $this->body, $headers);
    }

    private function sendSmtp(string $from, string $name): bool
    {
        $host     = env('SMTP_HOST', '127.0.0.1');
        $port     = (int) env('SMTP_PORT', 587);
        $user     = env('SMTP_USER', '');
        $pass     = env('SMTP_PASS', '');
        $secure   = env('SMTP_SECURE', 'tls');

        $errno = $errstr = null;
        $prefix  = $secure === 'ssl' ? 'ssl://' : '';
        $socket  = fsockopen($prefix . $host, $port, $errno, $errstr, 10);

        if (!$socket) {
            \Zieex\Log::error("SMTP connect failed: {$errstr}", ['errno' => $errno]);
            return false;
        }

        $read = fn() => fgets($socket, 512);
        $write = fn(string $cmd) => fwrite($socket, $cmd . "\r\n");

        $read(); // greeting

        $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
        while ($line = $read()) {
            if ($line[3] === ' ') break;
        }

        if ($secure === 'tls') {
            $write("STARTTLS");
            $read();
            stream_socket_enable_crypto($socket, true, STREAM_CRYPTO_METHOD_TLS_CLIENT);
            $write("EHLO " . ($_SERVER['HTTP_HOST'] ?? 'localhost'));
            while ($line = $read()) {
                if ($line[3] === ' ') break;
            }
        }

        if ($user) {
            $write("AUTH LOGIN");
            $read();
            $write(base64_encode($user));
            $read();
            $write(base64_encode($pass));
            $read();
        }

        $write("MAIL FROM:<{$from}>");
        $read();
        $write("RCPT TO:<{$this->to}>");
        $read();

        foreach ($this->cc as $cc) {
            $write("RCPT TO:<{$cc}>");
            $read();
        }

        $write("DATA");
        $read();

        $contentType = $this->isHtml ? 'text/html' : 'text/plain';
        $headers  = "From: {$name} <{$from}>\r\n";
        $headers .= "To: {$this->to}\r\n";
        $headers .= "Subject: {$this->subject}\r\n";
        $headers .= "MIME-Version: 1.0\r\n";
        $headers .= "Content-Type: {$contentType}; charset=UTF-8\r\n";
        $headers .= "Date: " . date('r') . "\r\n";

        $write($headers . "\r\n" . $this->body . "\r\n.");
        $read();
        $write("QUIT");
        fclose($socket);

        return true;
    }

    private function sendResend(string $from, string $name): bool
    {
        $apiKey = env('RESEND_API_KEY', '');
        if (!$apiKey) return false;

        $payload = json_encode([
            'from'    => "{$name} <{$from}>",
            'to'      => [$this->to],
            'subject' => $this->subject,
            'html'    => $this->isHtml ? $this->body : null,
            'text'    => !$this->isHtml ? $this->body : null,
        ]);

        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $payload,
        ]]);

        $response = file_get_contents('https://api.resend.com/emails', false, $context);
        return $response !== false;
    }

    private function sendApi(string $from, string $name): bool
    {
        $url    = env('MAIL_API_URL', '');
        $apiKey = env('MAIL_API_KEY', '');
        if (!$url) return false;

        $payload = json_encode([
            'from'    => $from,
            'to'      => $this->to,
            'subject' => $this->subject,
            'body'    => $this->body,
        ]);

        $context = stream_context_create(['http' => [
            'method'  => 'POST',
            'header'  => "Content-Type: application/json\r\nAuthorization: Bearer {$apiKey}\r\n",
            'content' => $payload,
        ]]);

        $response = file_get_contents($url, false, $context);
        return $response !== false;
    }
}
