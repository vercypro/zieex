<?php
declare(strict_types=1);

namespace Zieex\Middleware;

use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\Auth;

class RoleMiddleware
{
    public function __construct(private ?string $params = null) {}

    public function handle(Request $request, callable $next): Response
    {
        $user = Auth::user();

        if (!$user) {
            return (new Response())->redirect('/login');
        }

        $required = $this->params;
        $userRole = $user['role'] ?? null;

        if ($required && $userRole !== $required) {
            if ($request->isAjax()) {
                return (new Response())->json(['error' => 'Forbidden.'], 403);
            }
            return (new Response())->html('<h1>403 - Forbidden</h1>', 403);
        }

        return $next();
    }
}
