<?php

declare(strict_types=1);

namespace Colibri\Support;

class Arr
{
    /**
     * Get a value from an array using dot notation.
     *
     * @param array<string, mixed> $array
     */
    public static function get(array $array, string $key, mixed $default = null): mixed
    {
        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return $default;
            }
            $array = $array[$segment];
        }

        return $array;
    }

    /**
     * Check if a key exists using dot notation.
     *
     * @param array<string, mixed> $array
     */
    public static function has(array $array, string $key): bool
    {
        foreach (explode('.', $key) as $segment) {
            if (! is_array($array) || ! array_key_exists($segment, $array)) {
                return false;
            }
            $array = $array[$segment];
        }

        return true;
    }

    /**
     * Return only the specified keys from an array.
     *
     * @param array<string, mixed> $array
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public static function only(array $array, array $keys): array
    {
        return array_intersect_key($array, array_flip($keys));
    }

    /**
     * Return all keys except the specified ones.
     *
     * @param array<string, mixed> $array
     * @param list<string> $keys
     * @return array<string, mixed>
     */
    public static function except(array $array, array $keys): array
    {
        return array_diff_key($array, array_flip($keys));
    }
}
