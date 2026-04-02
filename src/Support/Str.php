<?php

declare(strict_types=1);

namespace Colibri\Support;

class Str
{
    /**
     * Generate a URL-friendly slug.
     */
    public static function slug(string $value, string $separator = '-'): string
    {
        // Transliterate accented characters
        $value = transliterator_transliterate('Any-Latin; Latin-ASCII', $value) ?: $value;

        // Lowercase
        $value = mb_strtolower($value);

        // Replace non-alphanumeric characters with separator
        $value = (string) preg_replace('/[^a-z0-9]+/', $separator, $value);

        // Trim separators from edges
        return trim($value, $separator);
    }

    /**
     * Check if a string contains a substring.
     */
    public static function contains(string $haystack, string $needle): bool
    {
        return str_contains($haystack, $needle);
    }

    /**
     * Check if a string starts with a prefix.
     */
    public static function startsWith(string $haystack, string $needle): bool
    {
        return str_starts_with($haystack, $needle);
    }

    /**
     * Check if a string ends with a suffix.
     */
    public static function endsWith(string $haystack, string $needle): bool
    {
        return str_ends_with($haystack, $needle);
    }

    /**
     * Truncate a string to a maximum length.
     */
    public static function limit(string $value, int $limit = 100, string $end = '...'): string
    {
        if (mb_strlen($value) <= $limit) {
            return $value;
        }

        return mb_substr($value, 0, $limit) . $end;
    }

    /**
     * Generate a cryptographically secure random string.
     */
    public static function random(int $length = 32): string
    {
        return bin2hex(random_bytes((int) ceil($length / 2)));
    }

    /**
     * Convert a string to lowercase (multibyte safe).
     */
    public static function lower(string $value): string
    {
        return mb_strtolower($value);
    }

    /**
     * Convert a string to uppercase (multibyte safe).
     */
    public static function upper(string $value): string
    {
        return mb_strtoupper($value);
    }
}
