<?php
declare(strict_types=1);

namespace Zieex\Http;

class Response
{
    private int    $status  = 200;
    private array  $headers = [];
    private string $body    = '';
    private static array $macros = [];

    public function setStatus(int $status): static
    {
        $this->status = $status;
        return $this;
    }

    public function setHeader(string $key, string $value): static
    {
        $this->headers[$key] = $value;
        return $this;
    }

    public function setBody(string $body): static
    {
        $this->body = $body;
        return $this;
    }

    public function json(mixed $data, int $status = 200): static
    {
        $this->status = $status;
        $this->setHeader('Content-Type', 'application/json');
        $this->body = json_encode($data, JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR);
        return $this;
    }

    public function html(string $content, int $status = 200): static
    {
        $this->status = $status;
        $this->setHeader('Content-Type', 'text/html; charset=UTF-8');
        $this->body = $content;
        return $this;
    }

    public function redirect(string $url, int $status = 302): static
    {
        $this->status = $status;
        $this->setHeader('Location', $url);
        $this->body = '';
        return $this;
    }

    public function withCookie(string $name, string $value, int $ttl = 3600): static
    {
        setcookie($name, $value, time() + $ttl, '/', '', true, true);
        return $this;
    }

    public function send(): void
    {
        http_response_code($this->status);
        foreach ($this->headers as $key => $value) {
            header("{$key}: {$value}");
        }
        echo $this->body;
    }

    // API response helpers
    public function success(mixed $data = null, string $message = 'Success', int $status = 200): static
    {
        return $this->json([
            'success' => true,
            'message' => $message,
            'data'    => $data,
        ], $status);
    }

    public function error(string $message, int $status = 400, mixed $errors = null): static
    {
        return $this->json([
            'success' => false,
            'message' => $message,
            'errors'  => $errors,
        ], $status);
    }

    public static function macro(string $name, callable $callback): void
    {
        self::$macros[$name] = $callback;
    }

    public function __call(string $name, array $args): mixed
    {
        if (isset(self::$macros[$name])) {
            return (self::$macros[$name])($this, ...$args);
        }
        throw new \BadMethodCallException("Response macro [{$name}] not found.");
    }

    public static function make(string $body = '', int $status = 200): static
    {
        return (new static())->setBody($body)->setStatus($status);
    }
}
