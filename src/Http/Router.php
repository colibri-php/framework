<?php

declare(strict_types=1);

namespace Colibri\Http;

use Colibri\App;
use Colibri\Config;
use Colibri\Exceptions\HttpException;
use Colibri\Support\Str;
use Colibri\View\Assets;
use Colibri\View\View;
use Colibri\View\Page;
use Colibri\View\Lang;
use Colibri\Middleware\Pipeline;

class Router
{
    /** @var array<string, string> */
    private static array $params = [];

    /** @var array<string, mixed>|null */
    private static ?array $lastScope = null;

    /**
     * Resolve a request path to a route and dispatch it.
     */
    public static function dispatch(Request $request, App $app): Response
    {
        $path = $request->path;

        // Detect and strip locale prefix from path
        $path = self::resolveLocale($path);

        // API routes: /api/...
        if (Str::startsWith($path, '/api/') || $path === '/api') {
            $routePath = preg_replace('#^/api#', '', $path) ?: '/';

            return self::resolveApi($routePath, $request, $app);
        }

        // Web routes
        return self::resolveWeb($path, $request, $app);
    }

    /**
     * Detect locale from URL prefix and set it. Returns the path without the prefix.
     */
    private static function resolveLocale(string $path): string
    {
        $prefixes = Lang::locales();

        // Sort by length descending so longer prefixes match first
        uasort($prefixes, fn(string $a, string $b): int => strlen($b) <=> strlen($a));

        foreach ($prefixes as $locale => $prefix) {
            if ($prefix === '/') {
                continue;
            }

            if ($path === $prefix || Str::startsWith($path, $prefix . '/')) {
                Lang::setLocale($locale);

                $stripped = substr($path, strlen($prefix)) ?: '/';

                return '/' . ltrim($stripped, '/');
            }
        }

        // No prefix matched — use default locale
        $default = Config::get('i18n.default', 'en');
        Lang::setLocale($default);

        return $path;
    }

    /**
     * Resolve a web route (HTML).
     */
    private static function resolveWeb(string $path, Request $request, App $app): Response
    {
        $basePath = base_path('routes/web');
        $resolved = self::resolveFile($basePath, $path, allowLatte: true);

        if ($resolved === null) {
            throw new HttpException(404);
        }

        $routeFile = $resolved['php'] ?? $resolved['latte'];
        $routeDir = dirname($routeFile ?? $basePath);
        $middlewares = Pipeline::collect($routeDir, $basePath);

        // Collect cascading assets into the stack
        Assets::reset();
        Assets::collectCascading($routeDir, $basePath);

        $page = new Page();
        $handler = static fn(Request $req): Response => self::executePage($resolved, $req, $app, $page);

        if ($middlewares === []) {
            return $handler($request);
        }

        return Pipeline::pipeline($middlewares, $request, $handler);
    }

    /**
     * Resolve an API route (JSON).
     */
    private static function resolveApi(string $path, Request $request, App $app): Response
    {
        $basePath = base_path('routes/api');
        $resolved = self::resolveFile($basePath, $path, allowLatte: false);

        if ($resolved === null) {
            return Response::json(['error' => 'Not Found'], 404);
        }

        $routeDir = dirname($resolved['php'] ?? $basePath);
        $middlewares = Pipeline::collect($routeDir, $basePath);

        $page = new Page();
        $params = self::$params;

        $handler = static function (Request $req) use ($resolved, $app, $page, $params): Response {
            try {
                $result = self::executePhp($resolved['php'], $req, $app, $page, $params);
            } catch (HttpException $e) {
                return Response::json(['error' => $e->getMessage()], $e->statusCode);
            }

            if ($result instanceof Response) {
                return $result;
            }

            return Response::json($result);
        };

        if ($middlewares === []) {
            return $handler($request);
        }

        return Pipeline::pipeline($middlewares, $request, $handler);
    }

    /**
     * Resolve a file path from a URL path, supporting dynamic [param] segments.
     *
     * @return array{php: string|null, latte: string|null}|null
     */
    private static function resolveFile(string $basePath, string $urlPath, bool $allowLatte): ?array
    {
        self::$params = [];
        $segments = array_filter(explode('/', trim($urlPath, '/')));

        if ($segments === []) {
            return self::matchFiles($basePath, 'index', $allowLatte);
        }

        return self::walkSegments($basePath, array_values($segments), [], $allowLatte);
    }

    /**
     * Recursively walk URL segments, matching static files/dirs first, then dynamic [param].
     *
     * @param list<string> $segments
     * @param array<string, string> $params
     * @return array{php: string|null, latte: string|null}|null
     */
    private static function walkSegments(string $currentPath, array $segments, array $params, bool $allowLatte): ?array
    {
        if ($segments === []) {
            // Try index file in current directory
            $match = self::matchFiles($currentPath, 'index', $allowLatte);
            if ($match !== null) {
                self::$params = $params;

                return $match;
            }

            return null;
        }

        $segment = $segments[0];
        $remaining = array_slice($segments, 1);

        // If this is the last segment, try it as a file first
        if ($remaining === []) {
            // Static file match (priority)
            $match = self::matchFiles($currentPath, $segment, $allowLatte);
            if ($match !== null) {
                self::$params = $params;

                return $match;
            }

            // Static directory with index
            $staticDir = $currentPath . DIRECTORY_SEPARATOR . $segment;
            if (is_dir($staticDir)) {
                $match = self::matchFiles($staticDir, 'index', $allowLatte);
                if ($match !== null) {
                    self::$params = $params;

                    return $match;
                }
            }

            // Dynamic [param] file match
            $dynamicMatch = self::findDynamicFile($currentPath, $segment, $params, $allowLatte);
            if ($dynamicMatch !== null) {
                return $dynamicMatch;
            }

            // Dynamic [param] directory with index
            $dynamicDirMatch = self::findDynamicDir($currentPath, $segment, $remaining, $params, $allowLatte);
            if ($dynamicDirMatch !== null) {
                return $dynamicDirMatch;
            }

            return null;
        }

        // More segments remain — look for directories
        // Static directory match (priority)
        $staticDir = $currentPath . DIRECTORY_SEPARATOR . $segment;
        if (is_dir($staticDir)) {
            $match = self::walkSegments($staticDir, $remaining, $params, $allowLatte);
            if ($match !== null) {
                return $match;
            }
        }

        // Dynamic [param] directory match
        return self::findDynamicDir($currentPath, $segment, $remaining, $params, $allowLatte);
    }

    /**
     * Try to match a filename (without extension) in a directory.
     *
     * @return array{php: string|null, latte: string|null}|null
     */
    private static function matchFiles(string $dir, string $name, bool $allowLatte): ?array
    {
        $php = null;
        $latte = null;

        $phpPath = $dir . DIRECTORY_SEPARATOR . $name . '.php';
        if (file_exists($phpPath)) {
            $php = $phpPath;
        }

        if ($allowLatte) {
            $lattePath = $dir . DIRECTORY_SEPARATOR . $name . '.latte';
            if (file_exists($lattePath)) {
                $latte = $lattePath;
            }
        }

        if ($php === null && $latte === null) {
            return null;
        }

        return ['php' => $php, 'latte' => $latte];
    }

    /**
     * Find a dynamic [param] file in a directory.
     *
     * @param array<string, string> $params
     * @return array{php: string|null, latte: string|null}|null
     */
    private static function findDynamicFile(string $dir, string $value, array $params, bool $allowLatte): ?array
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return null;
        }

        foreach ($entries as $entry) {
            if (preg_match('/^\[(\w+)\]\.(php|latte)$/', $entry, $matches)) {
                $paramName = $matches[1];
                $ext = $matches[2];

                if ($ext === 'latte' && ! $allowLatte) {
                    continue;
                }

                $params[$paramName] = $value;
                self::$params = $params;

                $php = null;
                $latte = null;
                $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;

                if ($ext === 'php') {
                    $php = $fullPath;
                    // Check for twin .latte
                    if ($allowLatte) {
                        $twinLatte = $dir . DIRECTORY_SEPARATOR . '[' . $paramName . '].latte';
                        if (file_exists($twinLatte)) {
                            $latte = $twinLatte;
                        }
                    }
                } else {
                    $latte = $fullPath;
                    // Check for twin .php
                    $twinPhp = $dir . DIRECTORY_SEPARATOR . '[' . $paramName . '].php';
                    if (file_exists($twinPhp)) {
                        $php = $twinPhp;
                    }
                }

                return ['php' => $php, 'latte' => $latte];
            }
        }

        return null;
    }

    /**
     * Find a dynamic [param] directory and continue walking.
     *
     * @param list<string> $remaining
     * @param array<string, string> $params
     * @return array{php: string|null, latte: string|null}|null
     */
    private static function findDynamicDir(string $dir, string $value, array $remaining, array $params, bool $allowLatte): ?array
    {
        $entries = @scandir($dir);
        if ($entries === false) {
            return null;
        }

        foreach ($entries as $entry) {
            if (preg_match('/^\[(\w+)\]$/', $entry, $matches) && is_dir($dir . DIRECTORY_SEPARATOR . $entry)) {
                $paramName = $matches[1];
                $params[$paramName] = $value;

                $match = self::walkSegments($dir . DIRECTORY_SEPARATOR . $entry, $remaining, $params, $allowLatte);
                if ($match !== null) {
                    return $match;
                }
            }
        }

        return null;
    }

    /**
     * Execute a page based on the resolved files (3 modes).
     *
     * @param array{php: string|null, latte: string|null} $resolved
     */
    private static function executePage(array $resolved, Request $request, App $app, Page $page): Response
    {
        $phpFile = $resolved['php'];
        $latteFile = $resolved['latte'];
        $params = self::$params;
        $baseVars = [
            'page' => $page,
            'request' => $request,
            'app' => $app,
            'params' => $params,
            'htmx' => $request->isHtmx(),
        ];

        // Mode 1: .latte only — static page
        if ($phpFile === null && $latteFile !== null) {
            $html = View::render($latteFile, $baseVars, $page);

            return Response::html($html);
        }

        // Mode 2 & 3: .php exists
        if ($phpFile !== null) {
            $result = self::executePhp($phpFile, $request, $app, $page, $params);

            // If PHP returned a Response, use it directly
            if ($result instanceof Response) {
                return $result;
            }

            // If PHP returned a string (from view() or echo capture), wrap in HTML response
            if (is_string($result) && $result !== '') {
                return Response::html($result);
            }

            // Mode 3: .php + .latte twins — inject PHP variables into Latte
            if ($latteFile !== null) {
                $vars = self::$lastScope ?? [];
                $vars = [...$vars, ...$baseVars];

                $html = View::render($latteFile, $vars, $page);

                return Response::html($html);
            }

            // Mode 2: .php only, no return value — empty 200
            return Response::html('');
        }

        throw new HttpException(404);
    }

    /**
     * Check if a result from require is a method handler map.
     *
     * @phpstan-assert-if-true array<string, callable> $result
     */
    private static function isMethodMap(mixed $result): bool
    {
        if (! is_array($result) || $result === []) {
            return false;
        }

        foreach ($result as $key => $value) {
            if (! is_string($key) || ! is_callable($value)) {
                return false;
            }
            // Keys must look like HTTP methods (uppercase alpha)
            if (! preg_match('/^[A-Z]+$/', $key)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Execute a PHP route file and capture its output/variables.
     *
     * @param array<string, string> $params
     */
    private static function executePhp(string $file, Request $request, App $app, Page $page, array $params = []): mixed
    {
        self::$lastScope = null;

        $__file = $file;
        $__result = (static function () use ($__file, $request, $app, $page, $params) {
            ob_start();
            $result = require $__file;
            $output = ob_get_clean();

            // Capture defined variables from the file's scope
            $vars = get_defined_vars();
            unset($vars['__file'], $vars['result'], $vars['output']);
            Router::setLastScope($vars);

            // If the file returned a method handler map, dispatch by HTTP method
            if (Router::isMethodMap($result)) {
                $method = $request->method;
                if (! isset($result[$method])) {
                    throw new HttpException(405);
                }

                return $result[$method]($request, $params);
            }

            // If the file returned a value, use it
            if ($result !== 1 && $result !== true) {
                return $result;
            }

            // If there was output, return it
            if ($output !== '' && $output !== false) {
                return $output;
            }

            return null;
        })();

        return $__result;
    }

    /**
     * Store the last executed scope (called from executePhp closure).
     *
     * @param array<string, mixed> $vars
     *
     * @internal
     */
    public static function setLastScope(array $vars): void
    {
        self::$lastScope = $vars;
    }
}
