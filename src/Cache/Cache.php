<?php

declare(strict_types=1);

namespace Colibri\Cache;

use Colibri\Cache\Drivers\DbCacheDriver;
use Colibri\Cache\Drivers\FileCacheDriver;
use Colibri\Cache\Interfaces\CacheDriverInterface;
use Colibri\Config;

class Cache
{
    private static ?CacheDriverInterface $instance = null;

    /** @var array<string, class-string<CacheDriverInterface>> */
    private static array $drivers = [
        'file' => FileCacheDriver::class,
        'db' => DbCacheDriver::class,
    ];

    /**
     * Register a custom cache driver.
     *
     * @param class-string<CacheDriverInterface> $class
     */
    public static function registerDriver(string $name, string $class): void
    {
        self::$drivers[$name] = $class;
    }

    public static function get(string $key, mixed $default = null): mixed
    {
        return self::driver()->get($key, $default);
    }

    /**
     * @param int $minutes TTL in minutes. 0 = forever.
     */
    public static function put(string $key, mixed $value, int $minutes = 0): void
    {
        self::driver()->put($key, $value, $minutes);
    }

    public static function has(string $key): bool
    {
        return self::driver()->has($key);
    }

    public static function forget(string $key): bool
    {
        return self::driver()->forget($key);
    }

    public static function clear(): void
    {
        self::driver()->clear();
    }

    private static function driver(): CacheDriverInterface
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        $name = Config::get('cache.datasource', 'file');

        if (! isset(self::$drivers[$name])) {
            throw new \RuntimeException("Cache driver '$name' not found.");
        }

        $class = self::$drivers[$name];
        self::$instance = new $class();

        return self::$instance;
    }
}
