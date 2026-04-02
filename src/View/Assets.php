<?php

declare(strict_types=1);

namespace Colibri\View;

use Colibri\Support\Str;

class Assets
{
    /** @var list<string> */
    private static array $cssStack = [];

    /** @var list<string> */
    private static array $jsStack = [];

    /**
     * Reset the stacks (called at the start of each request).
     */
    public static function reset(): void
    {
        self::$cssStack = [];
        self::$jsStack = [];
    }

    /**
     * Push inline CSS to the stack.
     */
    public static function pushCss(string $css): void
    {
        self::$cssStack[] = '<style>' . $css . '</style>';
    }

    /**
     * Push a CSS file link to the stack.
     */
    public static function pushCssFile(string $path): void
    {
        $href = Str::startsWith($path, 'http') ? $path : '/assets/' . ltrim($path, '/');
        $tag = '<link rel="stylesheet" href="' . htmlspecialchars($href) . '">';

        if (! in_array($tag, self::$cssStack, true)) {
            self::$cssStack[] = $tag;
        }
    }

    /**
     * Push inline JS to the stack.
     */
    public static function pushJs(string $js): void
    {
        self::$jsStack[] = '<script>' . $js . '</script>';
    }

    /**
     * Push a JS file to the stack.
     */
    public static function pushJsFile(string $path, bool $defer = false, bool $async = false): void
    {
        $src = Str::startsWith($path, 'http') ? $path : '/assets/' . ltrim($path, '/');
        $attrs = $defer ? ' defer' : ($async ? ' async' : '');
        $tag = '<script src="' . htmlspecialchars($src) . '"' . $attrs . '></script>';

        if (! in_array($tag, self::$jsStack, true)) {
            self::$jsStack[] = $tag;
        }
    }

    /**
     * Collect _styles.css files from baseDir to routeDir and push to stack.
     */
    public static function collectCascading(string $routeDir, string $baseDir): void
    {
        foreach (self::collectFiles($routeDir, $baseDir, '_styles.css') as $content) {
            self::pushCss($content);
        }

        foreach (self::collectFiles($routeDir, $baseDir, '_scripts.js') as $content) {
            self::pushJs($content);
        }
    }

    /**
     * Render all collected CSS.
     */
    public static function renderStyles(): string
    {
        return implode("\n", self::$cssStack);
    }

    /**
     * Render all collected JS.
     */
    public static function renderScripts(): string
    {
        return implode("\n", self::$jsStack);
    }

    /**
     * Collect a specific asset file in cascade from baseDir to routeDir.
     *
     * @return list<string> File contents in cascade order.
     */
    private static function collectFiles(string $routeDir, string $baseDir, string $filename): array
    {
        $contents = [];
        $routeDir = realpath($routeDir) ?: $routeDir;
        $baseDir = realpath($baseDir) ?: $baseDir;

        $relative = str_replace($baseDir, '', $routeDir);
        $segments = array_filter(explode(DIRECTORY_SEPARATOR, $relative));

        $file = $baseDir . DIRECTORY_SEPARATOR . $filename;
        if (file_exists($file)) {
            $contents[] = file_get_contents($file) ?: '';
        }

        $current = $baseDir;
        foreach ($segments as $segment) {
            $current .= DIRECTORY_SEPARATOR . $segment;
            $file = $current . DIRECTORY_SEPARATOR . $filename;
            if (file_exists($file)) {
                $contents[] = file_get_contents($file) ?: '';
            }
        }

        return $contents;
    }
}
