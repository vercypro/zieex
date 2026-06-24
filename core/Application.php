<?php
declare(strict_types=1);

namespace Zieex;

use Zieex\Http\Request;
use Zieex\Http\Response;
use Zieex\Router\Router;
use Zieex\Database\DB;
use Zieex\Cache\Cache;

class Application
{
    private static Application $instance;
    private Container $container;
    private array $config = [];

    public function __construct()
    {
        self::$instance = $this;
        $this->container = new Container();
    }

    public static function getInstance(): static
    {
        return self::$instance;
    }

    public function boot(): void
    {
        // Load .env
        Env::load(BASE_PATH . '/.env');

        // Load configs
        $this->loadConfigs();

        // Set error handling
        $this->setupErrorHandling();

        // Set timezone
        date_default_timezone_set($this->config['app']['timezone'] ?? 'UTC');

        // Start session
        if (session_status() === PHP_SESSION_NONE) {
            session_name('zieex_session');
            session_start();
        }

        // Bind core services
        $this->bindServices();
    }

    private function loadConfigs(): void
    {
        $configPath = BASE_PATH . '/config';
        foreach (glob($configPath . '/*.php') as $file) {
            $key = basename($file, '.php');
            $this->config[$key] = require $file;
        }

        // Make config globally accessible
        Config::setAll($this->config);
    }

    private function setupErrorHandling(): void
    {
        $isProduction = env('APP_ENV', 'local') === 'production';

        error_reporting(E_ALL);
        ini_set('display_errors', $isProduction ? '0' : '1');

        set_exception_handler(function (\Throwable $e) use ($isProduction) {
            $this->handleException($e, $isProduction);
        });

        set_error_handler(function (int $errno, string $errstr, string $errfile, int $errline) {
            throw new \ErrorException($errstr, 0, $errno, $errfile, $errline);
        });
    }

    private function handleException(\Throwable $e, bool $isProduction): void
    {
        http_response_code(500);
        $isJson = isset($_SERVER['HTTP_ACCEPT']) && str_contains($_SERVER['HTTP_ACCEPT'], 'application/json');

        if ($isJson) {
            header('Content-Type: application/json');
            echo json_encode([
                'error' => $isProduction ? 'Server Error' : $e->getMessage(),
                'code'  => 500,
            ]);
            return;
        }

        if ($isProduction) {
            require BASE_PATH . '/resources/views/errors/500.ze.php';
        } else {
            $this->renderDevError($e);
        }
    }

    private function renderDevError(\Throwable $e): void
    {
        $message = htmlspecialchars($e->getMessage());
        $file    = htmlspecialchars($e->getFile());
        $line    = $e->getLine();
        $trace   = htmlspecialchars($e->getTraceAsString());
        echo <<<HTML
        <!DOCTYPE html><html><head><title>Error</title>
        <style>body{font-family:monospace;background:#1e1e2e;color:#cdd6f4;padding:2rem}
        .box{background:#181825;border-left:4px solid #f38ba8;padding:1.5rem;border-radius:6px;margin-bottom:1rem}
        h1{color:#f38ba8}pre{overflow:auto;font-size:0.85rem;color:#a6e3a1}</style></head>
        <body><h1>⚠ Exception</h1>
        <div class="box"><b>$message</b><br><small>$file : $line</small></div>
        <div class="box"><pre>$trace</pre></div></body></html>
        HTML;
    }

    private function bindServices(): void
    {
        $this->container->singleton('db', fn() => new DB());
        $this->container->singleton('cache', fn() => new Cache());
        $this->container->singleton('router', fn() => new Router());
        $this->container->singleton('request', fn() => new Request());
        $this->container->singleton('response', fn() => new Response());
    }

    public function run(): void
    {
        $router = $this->container->make('router');

        // Load routes
        require_once BASE_PATH . '/routes/web/index.php';
        require_once BASE_PATH . '/routes/api/index.php';

        $request  = $this->container->make('request');
        $response = $router->dispatch($request);
        $response->send();
    }

    public function make(string $abstract): mixed
    {
        return $this->container->make($abstract);
    }

    public function getConfig(): array
    {
        return $this->config;
    }
}
