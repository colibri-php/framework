<?php

declare(strict_types=1);

namespace Colibri\View;

use Colibri\Support\Str;
use Colibri\Config;

class Vite
{
    /** @var array<string, array{file: string, css?: list<string>}>|null */
    private static ?array $manifest = null;

    private static ?bool $devServerRunning = null;

    /**
     * Generate HTML tags for a Vite entry point.
     */
    public static function asset(string $entry): string
    {
        if (self::isDevServerRunning()) {
            return self::devTags($entry);
        }

        $manifest = self::manifest();
        if ($manifest === null) {
            return '';
        }

        return self::prodTags($entry, $manifest);
    }

    /**
     * Check if the Vite dev server is running.
     */
    private static function isDevServerRunning(): bool
    {
        if (self::$devServerRunning !== null) {
            return self::$devServerRunning;
        }

        if (! Config::get('app.debug', false)) {
            self::$devServerRunning = false;

            return false;
        }

        $host = Config::get('app.vite_host', 'http://localhost:5173');
        $handle = @fopen($host . '/@vite/client', 'r');

        if ($handle !== false) {
            fclose($handle);
            self::$devServerRunning = true;

            return true;
        }

        self::$devServerRunning = false;

        return false;
    }

    /**
     * Generate dev server tags (HMR).
     */
    private static function devTags(string $entry): string
    {
        $host = Config::get('app.vite_host', 'http://localhost:5173');
        $tags = '<script type="module" src="' . $host . '/@vite/client"></script>';

        if (Str::endsWith($entry, '.css')) {
            $tags .= '<link rel="stylesheet" href="' . $host . '/' . $entry . '">';
        } else {
            $tags .= '<script type="module" src="' . $host . '/' . $entry . '"></script>';
        }

        return $tags;
    }

    /**
     * Generate production tags from manifest.
     *
     * @param array<string, array{file: string, css?: list<string>}> $manifest
     */
    private static function prodTags(string $entry, array $manifest): string
    {
        if (! isset($manifest[$entry])) {
            return '';
        }

        $chunk = $manifest[$entry];
        $tags = '';

        // CSS files associated with this entry
        foreach ($chunk['css'] ?? [] as $css) {
            $tags .= '<link rel="stylesheet" href="/build/' . $css . '">';
        }

        // The JS/CSS file itself
        if (Str::endsWith($entry, '.css')) {
            $tags .= '<link rel="stylesheet" href="/build/' . $chunk['file'] . '">';
        } else {
            $tags .= '<script type="module" src="/build/' . $chunk['file'] . '"></script>';
        }

        return $tags;
    }

    /**
     * Read the Vite manifest file.
     *
     * @return array<string, array{file: string, css?: list<string>}>|null
     */
    private static function manifest(): ?array
    {
        if (self::$manifest !== null) {
            return self::$manifest;
        }

        $path = base_path('public/build/manifest.json');

        if (! file_exists($path)) {
            return null;
        }

        $content = file_get_contents($path);
        $decoded = $content !== false ? json_decode($content, true) : null;

        self::$manifest = is_array($decoded) ? $decoded : null;

        return self::$manifest;
    }
}
