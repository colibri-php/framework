<?php

declare(strict_types=1);

namespace Colibri;

use Dotenv\Dotenv;

class Config
{
    /** @var array<string, array<string, mixed>> */
    public private(set) array $loaded = [];

    private static ?self $instance = null;

    private string $configPath;

    private function __construct(string $basePath)
    {
        $this->configPath = $basePath . DIRECTORY_SEPARATOR . 'config';

        if (file_exists($basePath . DIRECTORY_SEPARATOR . '.env')) {
            Dotenv::createImmutable($basePath)->load();
        }
    }

    /**
     * Initialize the config system.
     */
    public static function init(string $basePath): self
    {
        self::$instance ??= new self($basePath);

        return self::$instance;
    }

    /**
     * Get a config value using dot notation.
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $instance = self::$instance
            ?? throw new \RuntimeException('Config has not been initialized.');

        $segments = explode('.', $key);
        $file = array_shift($segments);

        $instance->loadFile($file);

        $value = $instance->loaded[$file] ?? [];

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return $default;
            }
            $value = $value[$segment];
        }

        return $value;
    }

    /**
     * Load a config file if not already loaded.
     */
    private function loadFile(string $file): void
    {
        if (array_key_exists($file, $this->loaded)) {
            return;
        }

        $path = $this->configPath . DIRECTORY_SEPARATOR . $file . '.php';

        $this->loaded[$file] = file_exists($path) ? require $path : [];
    }
}
