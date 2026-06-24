<?php
declare(strict_types=1);

namespace Zieex\Cache;

class Cache
{
    private string $path;

    public function __construct()
    {
        $this->path = BASE_PATH . '/storage/cache';
        if (!is_dir($this->path)) {
            mkdir($this->path, 0755, true);
        }
    }

    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->file($key);
        if (!file_exists($file)) return $default;

        $data = unserialize(file_get_contents($file));

        if ($data['expires'] !== 0 && $data['expires'] < time()) {
            unlink($file);
            return $default;
        }

        return $data['value'];
    }

    public function set(string $key, mixed $value, int $ttl = 3600): void
    {
        $data = [
            'value'   => $value,
            'expires' => $ttl === 0 ? 0 : time() + $ttl,
        ];
        file_put_contents($this->file($key), serialize($data), LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key) !== null;
    }

    public function delete(string $key): void
    {
        $file = $this->file($key);
        if (file_exists($file)) unlink($file);
    }

    public function flush(): void
    {
        foreach (glob($this->path . '/*.cache') as $file) {
            unlink($file);
        }
    }

    public function remember(string $key, int $ttl, callable $callback): mixed
    {
        $value = $this->get($key);
        if ($value !== null) return $value;

        $value = $callback();
        $this->set($key, $value, $ttl);
        return $value;
    }

    public function forever(string $key, mixed $value): void
    {
        $this->set($key, $value, 0);
    }

    private function file(string $key): string
    {
        return $this->path . '/' . md5($key) . '.cache';
    }
}
