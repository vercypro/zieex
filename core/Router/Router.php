<?php

declare(strict_types=1);

namespace Zieex\Router;

use Zieex\Http\Request;
use Zieex\Http\Response;

class Router
{
    private array $routes        = [];
    private array $groupStack    = [];
    private ?Route $currentRoute = null;

    private static Router $instance;

    public function __construct()
    {
        self::$instance = $this;
    }

    public static function get(string $path, array|callable $handler): Route
    {
        return self::$instance->addRoute('GET', $path, $handler);
    }

    public static function post(string $path, array|callable $handler): Route
    {
        return self::$instance->addRoute('POST', $path, $handler);
    }

    public static function put(string $path, array|callable $handler): Route
    {
        return self::$instance->addRoute('PUT', $path, $handler);
    }

    public static function patch(string $path, array|callable $handler): Route
    {
        return self::$instance->addRoute('PATCH', $path, $handler);
    }

    public static function delete(string $path, array|callable $handler): Route
    {
        return self::$instance->addRoute('DELETE', $path, $handler);
    }

    public static function any(string $path, array|callable $handler): Route
    {
        foreach (['GET', 'POST', 'PUT', 'PATCH', 'DELETE'] as $method) {
            self::$instance->addRoute($method, $path, $handler);
        }
        return self::$instance->currentRoute;
    }

    public static function group(array $attributes, callable $callback): void
    {
        self::$instance->groupStack[] = $attributes;
        $callback();
        array_pop(self::$instance->groupStack);
    }

    public static function resource(string $path, string $controller): void
    {
        $r = self::$instance;
        $r->addRoute('GET',    $path,             [$controller, 'index']);
        $r->addRoute('GET',    $path . '/{id}',   [$controller, 'show']);
        $r->addRoute('POST',   $path,             [$controller, 'store']);
        $r->addRoute('PUT',    $path . '/{id}',   [$controller, 'update']);
        $r->addRoute('DELETE', $path . '/{id}',   [$controller, 'destroy']);
    }

    private function addRoute(string $method, string $path, array|callable $handler): Route
    {
        $prefix     = '';
        $middleware = [];
        foreach ($this->groupStack as $group) {
            $prefix     .= $group['prefix'] ?? '';
            $middleware  = array_merge($middleware, $group['middleware'] ?? []);
        }

        $route = new Route($method, $prefix . $path, $handler);
        $route->middleware(...$middleware);

        $this->routes[] = $route;
        $this->currentRoute = $route;
        return $route;
    }

    public function dispatch(Request $request): Response
    {
        $method = $request->method();
        $path   = $request->path();
        $params = [];

        foreach ($this->routes as $route) {
            if ($route->matches($method, $path, $params)) {
                $request->setRouteParams($params);
                return $this->runRoute($route, $request);
            }
        }

        return $this->notFound();
    }

    private function runRoute(Route $route, Request $request): Response
    {
        $middlewares = $route->getMiddleware();
        $handler     = fn() => $this->callHandler($route->getHandler(), $request);

        $pipeline = array_reduce(
            array_reverse($middlewares),
            fn($next, $mw) => fn() => $this->resolveMiddleware($mw)->handle($request, $next),
            $handler
        );

        return $pipeline();
    }

    private function resolveMiddleware(string $mw): object
    {
        [$name, $params] = array_pad(explode(':', $mw, 2), 2, null);

        $map = \Zieex\Config::get('middleware', []);
        $class = $map[$name] ?? null;

        if (!$class || !class_exists($class)) {
            throw new \RuntimeException("Middleware [{$name}] not found.");
        }

        return new $class($params);
    }

    private function callHandler(array|callable $handler, Request $request): Response
    {
        if (is_callable($handler)) {
            $result = $handler($request);
        } else {
            [$class, $method] = $handler;
            $controller = new $class();
            $result = $controller->$method($request);
        }

        if ($result instanceof Response) {
            return $result;
        }

        $response = new Response();
        $response->setBody(is_string($result) ? $result : json_encode($result));
        return $response;
    }

    private function notFound(): Response
    {
        $response = new Response();
        $response->setStatus(404);
        if (file_exists(BASE_PATH . '/resources/views/errors/404.ze.php')) {
            ob_start();
            require BASE_PATH . '/resources/views/errors/404.ze.php';
            $response->setBody(ob_get_clean());
        } else {
            $response->setBody('<h1>404 - Not Found</h1>');
        }
        return $response;
    }
}
