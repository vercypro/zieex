<?php
declare(strict_types=1);

namespace Zieex\Router;

class Route
{
    private string $method;
    private string $path;
    private mixed $handler;
    private array $middleware    = [];
    private ?string $name        = null;
    private int $rateLimitMax    = 0;
    private int $rateLimitWindow = 0;

    public function __construct(string $method, string $path, array|callable $handler)
    {
        $this->method  = $method;
        $this->path    = $path;
        $this->handler = $handler;
    }

    public function middleware(string ...$middleware): static
    {
        $this->middleware = array_merge($this->middleware, $middleware);
        return $this;
    }

    public function name(string $name): static
    {
        $this->name = $name;
        return $this;
    }

    public function rateLimit(int $max, int $windowSeconds): static
    {
        $this->rateLimitMax    = $max;
        $this->rateLimitWindow = $windowSeconds;
        $this->middleware('throttle:' . $max . ',' . $windowSeconds);
        return $this;
    }

    public function matches(string $method, string $path, array &$params = []): bool
    {
        if ($this->method !== $method && $method !== 'HEAD') {
            return false;
        }

        $pattern = preg_replace('/\{(\w+)\}/', '(?P<$1>[^/]+)', $this->path);
        $pattern = '#^' . $pattern . '$#';

        if (preg_match($pattern, $path, $matches)) {
            $params = array_filter($matches, fn($k) => !is_int($k), ARRAY_FILTER_USE_KEY);
            return true;
        }

        return false;
    }

    public function getHandler(): array|callable
    {
        return $this->handler;
    }

    public function getMiddleware(): array
    {
        return $this->middleware;
    }

    public function getName(): ?string
    {
        return $this->name;
    }
}
