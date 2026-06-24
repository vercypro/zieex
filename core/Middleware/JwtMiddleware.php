<?php
declare(strict_types=1);

namespace Zieex\Middleware;

use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\JWT;

class JwtMiddleware
{
    public function __construct(private ?string $params = null) {}

    public function handle(Request $request, callable $next): Response
    {
        $token = $request->bearerToken();

        if (!$token) {
            return (new Response())->json(['error' => 'Token required.'], 401);
        }

        $payload = JWT::decode($token);
        if (!$payload) {
            return (new Response())->json(['error' => 'Invalid or expired token.'], 401);
        }

        $_SERVER['JWT_PAYLOAD'] = $payload;
        return $next();
    }
}
