<?php

declare(strict_types=1);

namespace Colibri\Storage;

use Colibri\Config;

class Data
{
    /**
     * Get a single item by path.
     *
     * @return array<string, mixed>|null
     */
    public static function get(string $path): ?array
    {
        $file = self::resolve($path);

        if (! file_exists($file)) {
            return null;
        }

        $content = file_get_contents($file);
        if ($content === false) {
            return null;
        }

        $decoded = json_decode($content, true);

        return is_array($decoded) ? $decoded : null;
    }

    /**
     * Get all items in a collection.
     *
     * @return list<array<string, mixed>>
     */
    public static function all(string $collection, ?string $sort = null, string $order = 'asc'): array
    {
        $dir = self::resolveDir($collection);

        if (! is_dir($dir)) {
            return [];
        }

        $files = glob($dir . DIRECTORY_SEPARATOR . '*.json') ?: [];
        $items = [];

        foreach ($files as $file) {
            $content = file_get_contents($file);
            if ($content === false) {
                continue;
            }

            $decoded = json_decode($content, true);
            if (is_array($decoded)) {
                $decoded['_slug'] = basename($file, '.json');
                $items[] = $decoded;
            }
        }

        if ($sort !== null) {
            usort($items, function (array $a, array $b) use ($sort, $order): int {
                $va = $a[$sort] ?? '';
                $vb = $b[$sort] ?? '';
                $cmp = $va <=> $vb;

                return $order === 'desc' ? -$cmp : $cmp;
            });
        }

        return $items;
    }

    /**
     * Filter items in a collection by simple key-value matching.
     *
     * @param array<string, mixed> $conditions
     * @return list<array<string, mixed>>
     */
    public static function where(string $collection, array $conditions): array
    {
        $items = self::all($collection);

        return array_values(array_filter($items, function (array $item) use ($conditions): bool {
            foreach ($conditions as $key => $value) {
                if (($item[$key] ?? null) !== $value) {
                    return false;
                }
            }

            return true;
        }));
    }

    /**
     * Create or update an item.
     *
     * @param array<string, mixed> $data
     */
    public static function put(string $path, array $data): bool
    {
        $file = self::resolve($path);
        $dir = dirname($file);

        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $json = json_encode($data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);

        return file_put_contents($file, $json, LOCK_EX) !== false;
    }

    /**
     * Delete an item.
     */
    public static function delete(string $path): bool
    {
        $file = self::resolve($path);

        if (! file_exists($file)) {
            return false;
        }

        return unlink($file);
    }

    /**
     * Check if an item exists.
     */
    public static function exists(string $path): bool
    {
        return file_exists(self::resolve($path));
    }

    /**
     * Resolve a data path to an absolute file path.
     */
    private static function resolve(string $path): string
    {
        $basePath = Config::get('data.path', 'data');

        return base_path($basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $path) . '.json');
    }

    /**
     * Resolve a collection name to an absolute directory path.
     */
    private static function resolveDir(string $collection): string
    {
        $basePath = Config::get('data.path', 'data');

        return base_path($basePath . DIRECTORY_SEPARATOR . str_replace('/', DIRECTORY_SEPARATOR, $collection));
    }
}
