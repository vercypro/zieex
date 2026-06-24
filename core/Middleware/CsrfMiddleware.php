<?php
declare(strict_types=1);

namespace Zieex\Middleware;

use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\CSRF;

class CsrfMiddleware
{
    private array $except = ['/api'];

    public function __construct(private ?string $params = null) {}

    public function handle(Request $request, callable $next): Response
    {
        if (!in_array($request->method(), ['POST', 'PUT', 'PATCH', 'DELETE'])) {
            return $next();
        }

        foreach ($this->except as $prefix) {
            if (str_starts_with($request->path(), $prefix)) {
                return $next();
            }
        }

        if (!CSRF::verify()) {
            if ($request->isAjax()) {
                return (new Response())->json(['error' => 'CSRF token mismatch.'], 419);
            }
            http_response_code(419);
            return (new Response())->html('<h1>419 - CSRF Token Mismatch</h1>', 419);
        }

        return $next();
    }
}
