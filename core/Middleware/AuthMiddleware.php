<?php
declare(strict_types=1);

namespace Zieex\Middleware;

use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Auth\Auth;

class AuthMiddleware
{
    public function __construct(private ?string $params = null) {}

    public function handle(Request $request, callable $next): Response
    {
        if (Auth::check()) {
            return $next();
        }

        if ($request->isAjax()) {
            return (new Response())->json(['error' => 'Unauthenticated.'], 401);
        }

        return (new Response())->redirect('/login');
    }
}
