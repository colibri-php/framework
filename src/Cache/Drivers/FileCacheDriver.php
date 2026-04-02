<?php

declare(strict_types=1);

namespace Colibri\Cache\Drivers;

use Colibri\Config;
use Colibri\Cache\Interfaces\CacheDriverInterface;

class FileCacheDriver implements CacheDriverInterface
{
    public function get(string $key, mixed $default = null): mixed
    {
        $file = $this->path($key);

        if (! file_exists($file)) {
            return $default;
        }

        $data = unserialize(file_get_contents($file) ?: '');

        if (! is_array($data) || ! isset($data['expires_at'], $data['value'])) {
            return $default;
        }

        if ($data['expires_at'] !== 0 && $data['expires_at'] < time()) {
            unlink($file);

            return $default;
        }

        return $data['value'];
    }

    public function put(string $key, mixed $value, int $minutes = 0): void
    {
        $dir = $this->dir();
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $data = [
            'expires_at' => $minutes !== 0 ? time() + ($minutes * 60) : 0,
            'value' => $value,
        ];

        file_put_contents($this->path($key), serialize($data), LOCK_EX);
    }

    public function has(string $key): bool
    {
        return $this->get($key, $sentinel = new \stdClass()) !== $sentinel;
    }

    public function forget(string $key): bool
    {
        $file = $this->path($key);

        if (! file_exists($file)) {
            return false;
        }

        return unlink($file);
    }

    public function clear(): void
    {
        $dir = $this->dir();

        if (! is_dir($dir)) {
            return;
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.cache') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
    }

    private function dir(): string
    {
        return base_path(Config::get('cache.path', 'storage/cache/data'));
    }

    private function path(string $key): string
    {
        return $this->dir() . DIRECTORY_SEPARATOR . md5($key) . '.cache';
    }
}
