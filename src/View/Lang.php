<?php

declare(strict_types=1);

namespace Colibri\View;

use Colibri\Config;

class Lang
{
    private static ?string $currentLocale = null;

    /** @var array<string, array<string, mixed>> */
    private static array $translations = [];

    /**
     * Get the active locale.
     */
    public static function locale(): string
    {
        if (self::$currentLocale !== null) {
            return self::$currentLocale;
        }

        // Try session first
        if (session_status() !== PHP_SESSION_NONE && isset($_SESSION['_locale'])) {
            self::$currentLocale = $_SESSION['_locale'];

            return self::$currentLocale;
        }

        self::$currentLocale = Config::get('i18n.default', 'en');

        return self::$currentLocale;
    }

    /**
     * Set the active locale.
     */
    public static function setLocale(string $locale): void
    {
        self::$currentLocale = $locale;

        if (session_status() === PHP_SESSION_NONE) {
            @session_start();
        }

        $_SESSION['_locale'] = $locale;
    }

    /**
     * Get all configured locales with their prefixes.
     *
     * @return array<string, string>
     */
    public static function locales(): array
    {
        return Config::get('i18n.prefixes', ['en' => '/']);
    }

    /**
     * Check if the active locale matches.
     */
    public static function isLocale(string $locale): bool
    {
        return self::locale() === $locale;
    }

    /**
     * Translate a key.
     *
     * @param array<string, mixed> $params
     */
    public static function translate(string $key, array $params = [], ?string $locale = null): string
    {
        $locale ??= self::locale();
        $fallback = Config::get('i18n.fallback', 'en');

        // Try active locale
        $value = self::resolve($key, $locale);

        // Fallback
        if ($value === null && $locale !== $fallback) {
            $value = self::resolve($key, $fallback);
        }

        // Return the key itself if not found
        if ($value === null) {
            return $key;
        }

        // Interpolation: replace {param} with values
        foreach ($params as $name => $val) {
            $value = str_replace('{' . $name . '}', (string) $val, $value);
        }

        return $value;
    }

    /**
     * Resolve a translation key in a specific locale.
     */
    private static function resolve(string $key, string $locale): ?string
    {
        self::load($locale);

        $segments = explode('.', $key);
        $value = self::$translations[$locale] ?? [];

        foreach ($segments as $segment) {
            if (! is_array($value) || ! array_key_exists($segment, $value)) {
                return null;
            }
            $value = $value[$segment];
        }

        return is_string($value) ? $value : null;
    }

    /**
     * Load a locale file if not already loaded.
     */
    private static function load(string $locale): void
    {
        if (isset(self::$translations[$locale])) {
            return;
        }

        $file = base_path('locales' . DIRECTORY_SEPARATOR . $locale . '.json');

        if (! file_exists($file)) {
            self::$translations[$locale] = [];

            return;
        }

        $content = file_get_contents($file);
        $decoded = $content !== false ? json_decode($content, true) : null;

        self::$translations[$locale] = is_array($decoded) ? $decoded : [];
    }
}
