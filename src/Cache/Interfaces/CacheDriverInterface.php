<?php

declare(strict_types=1);

namespace Colibri\Cache\Interfaces;

interface CacheDriverInterface
{
    /**
     * Get a cached value.
     */
    public function get(string $key, mixed $default = null): mixed;

    /**
     * Store a value in the cache.
     *
     * @param int $minutes TTL in minutes. 0 = forever.
     */
    public function put(string $key, mixed $value, int $minutes = 0): void;

    /**
     * Check if a key exists and is not expired.
     */
    public function has(string $key): bool;

    /**
     * Remove a key from the cache.
     */
    public function forget(string $key): bool;

    /**
     * Clear the entire cache.
     */
    public function clear(): void;
}
