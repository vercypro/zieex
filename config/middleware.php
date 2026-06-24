<?php
return [
    'auth'     => \Zieex\Middleware\AuthMiddleware::class,
    'jwt'      => \Zieex\Middleware\JwtMiddleware::class,
    'csrf'     => \Zieex\Middleware\CsrfMiddleware::class,
    'throttle' => \Zieex\Middleware\ThrottleMiddleware::class,
    'role'     => \Zieex\Middleware\RoleMiddleware::class,
];
