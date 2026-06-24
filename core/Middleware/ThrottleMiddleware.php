<?php
declare(strict_types=1);

namespace Zieex\Middleware;

use Zieex\Http\Request;
use Zieex\Http\Response;

class ThrottleMiddleware
{
    private int $max    = 60;
    private int $window = 60;

    public function __construct(private ?string $params = null)
    {
        if ($params) {
            [$this->max, $this->window] = array_map('intval', explode(',', $params));
        }
    }

    public function handle(Request $request, callable $next): Response
    {
        $key   = 'throttle:' . $request->ip() . ':' . md5($request->path());
        $file  = BASE_PATH . '/storage/cache/throttle_' . md5($key) . '.json';

        $data = ['count' => 0, 'reset_at' => time() + $this->window];

        if (file_exists($file)) {
            $stored = json_decode(file_get_contents($file), true);
            if ($stored['reset_at'] > time()) {
                $data = $stored;
            }
        }

        if ($data['count'] >= $this->max) {
            return (new Response())->json([
                'error'     => 'Too many requests.',
                'retry_after' => $data['reset_at'] - time(),
            ], 429);
        }

        $data['count']++;
        file_put_contents($file, json_encode($data));

        return $next();
    }
}
