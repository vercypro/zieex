<?php
declare(strict_types=1);

if (!function_exists('env')) {
    function env(string $key, mixed $default = null): mixed
    {
        return \Zieex\Env::get($key, $default);
    }
}

if (!function_exists('config')) {
    function config(string $key, mixed $default = null): mixed
    {
        return \Zieex\Config::get($key, $default);
    }
}

if (!function_exists('view')) {
    function view(string $template, array $data = []): \Zieex\Http\Response
    {
        $content = \Zieex\Template\View::render($template, $data);
        return (new \Zieex\Http\Response())->html($content);
    }
}

if (!function_exists('redirect')) {
    function redirect(string $url, int $status = 302): \Zieex\Http\Response
    {
        return (new \Zieex\Http\Response())->redirect($url, $status);
    }
}

if (!function_exists('response')) {
    function response(): \Zieex\Http\Response
    {
        return new \Zieex\Http\Response();
    }
}

if (!function_exists('base_path')) {
    function base_path(string $path = ''): string
    {
        return BASE_PATH . ($path ? '/' . ltrim($path, '/') : '');
    }
}

if (!function_exists('storage_path')) {
    function storage_path(string $path = ''): string
    {
        return base_path('storage' . ($path ? '/' . ltrim($path, '/') : ''));
    }
}

if (!function_exists('dd')) {
    function dd(mixed ...$vars): never
    {
        echo '<pre style="background:#1e1e2e;color:#cdd6f4;padding:1rem;font-family:monospace;font-size:14px">';
        foreach ($vars as $v) {
            var_dump($v);
        }
        echo '</pre>';
        exit;
    }
}

if (!function_exists('dump')) {
    function dump(mixed ...$vars): void
    {
        echo '<pre style="background:#1e1e2e;color:#cdd6f4;padding:1rem;font-family:monospace;font-size:14px">';
        foreach ($vars as $v) {
            var_dump($v);
        }
        echo '</pre>';
    }
}

if (!function_exists('flash')) {
    function flash(string $key, string $message): void
    {
        $_SESSION['flash'][$key] = $message;
    }
}

if (!function_exists('flash_get')) {
    function flash_get(string $key): ?string
    {
        $msg = $_SESSION['flash'][$key] ?? null;
        unset($_SESSION['flash'][$key]);
        return $msg;
    }
}

if (!function_exists('csrf_token')) {
    function csrf_token(): string
    {
        return \Zieex\Auth\CSRF::token();
    }
}

if (!function_exists('csrf_field')) {
    function csrf_field(): string
    {
        return \Zieex\Auth\CSRF::field();
    }
}

if (!function_exists('auth')) {
    function auth(): \Zieex\Auth\Auth
    {
        return new \Zieex\Auth\Auth();
    }
}

if (!function_exists('now')) {
    function now(string $format = 'Y-m-d H:i:s'): string
    {
        return date($format);
    }
}

if (!function_exists('uuid')) {
    function uuid(): string
    {
        $data    = random_bytes(16);
        $data[6] = chr(ord($data[6]) & 0x0f | 0x40);
        $data[8] = chr(ord($data[8]) & 0x3f | 0x80);
        return vsprintf('%s%s-%s-%s-%s-%s%s%s', str_split(bin2hex($data), 4));
    }
}

if (!function_exists('abort')) {
    function abort(int $code, string $message = ''): never
    {
        http_response_code($code);
        $viewFile = BASE_PATH . "/resources/views/errors/{$code}.ze.php";
        if (file_exists($viewFile)) {
            require $viewFile;
        } else {
            echo "<h1>{$code}</h1><p>{$message}</p>";
        }
        exit;
    }
}

if (!function_exists('app')) {
    function app(): \Zieex\Application
    {
        return \Zieex\Application::getInstance();
    }
}

if (!function_exists('cache')) {
    function cache(): \Zieex\Cache\Cache
    {
        return app()->make('cache');
    }
}

if (!function_exists('log_info')) {
    function log_info(string $message, array $context = []): void
    {
        \Zieex\Log::info($message, $context);
    }
}

if (!function_exists('old')) {
    function old(string $key, mixed $default = ''): mixed
    {
        return $_SESSION['_old_input'][$key] ?? $default;
    }
}

if (!function_exists('back')) {
    function back(): \Zieex\Http\Response
    {
        $url = $_SERVER['HTTP_REFERER'] ?? '/';
        return redirect($url);
    }
}

if (!function_exists('asset')) {
    function asset(string $path): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}

if (!function_exists('url')) {
    function url(string $path = ''): string
    {
        $base = rtrim(env('APP_URL', ''), '/');
        return $base . '/' . ltrim($path, '/');
    }
}
