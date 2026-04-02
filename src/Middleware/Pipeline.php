<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

class Pipeline
{
    /**
     * Run a pipeline of middlewares then call the handler.
     *
     * @param list<string> $middlewareNames e.g. ['auth', 'csrf', 'rate:60,1']
     * @param callable(Request): Response $handler
     */
    public static function pipeline(array $middlewareNames, Request $request, callable $handler): Response
    {
        $stack = self::buildStack($middlewareNames, $handler);

        return $stack($request);
    }

    /**
     * Collect _middleware.php files from root to the route's directory (cascade).
     *
     * @return list<string>
     */
    public static function collect(string $routeDir, string $baseDir): array
    {
        $middlewares = [];
        $routeDir = realpath($routeDir) ?: $routeDir;
        $baseDir = realpath($baseDir) ?: $baseDir;

        // Walk from baseDir down to routeDir
        $relative = str_replace($baseDir, '', $routeDir);
        $segments = array_filter(explode(DIRECTORY_SEPARATOR, $relative));

        // Check _middleware.php at each level
        $current = $baseDir;
        $file = $current . DIRECTORY_SEPARATOR . '_middleware.php';
        if (file_exists($file)) {
            $middlewares = array_merge($middlewares, require $file);
        }

        foreach ($segments as $segment) {
            $current .= DIRECTORY_SEPARATOR . $segment;
            $file = $current . DIRECTORY_SEPARATOR . '_middleware.php';
            if (file_exists($file)) {
                $middlewares = array_merge($middlewares, require $file);
            }
        }

        return $middlewares;
    }

    /**
     * Resolve a middleware name to an instance.
     */
    public static function resolve(string $name): MiddlewareInterface
    {
        $className = ucfirst($name);

        // Project middleware first (file-based, anonymous class)
        $projectFile = base_path('middleware' . DIRECTORY_SEPARATOR . $name . '.php');
        if (file_exists($projectFile)) {
            $instance = require $projectFile;
            if ($instance instanceof MiddlewareInterface) {
                return $instance;
            }

            throw new \RuntimeException("Middleware file '$name.php' must return an instance of MiddlewareInterface.");
        }

        // Built-in middleware (namespaced class)
        $builtinClass = 'Colibri\\Middleware\\' . $className;
        if (class_exists($builtinClass)) {
            $instance = new $builtinClass();
            if ($instance instanceof MiddlewareInterface) {
                return $instance;
            }
        }

        throw new \RuntimeException("Middleware '$name' not found.");
    }

    /**
     * Build a nested callable stack from middleware names.
     *
     * @param list<string> $names
     * @param callable(Request): Response $handler
     * @return callable(Request): Response
     */
    private static function buildStack(array $names, callable $handler): callable
    {
        $stack = $handler;

        // Build from inside out (last middleware wraps first)
        foreach (array_reverse($names) as $entry) {
            $parts = explode(':', $entry, 2);
            $name = $parts[0];
            $params = isset($parts[1]) ? explode(',', $parts[1]) : [];

            $middleware = self::resolve($name);
            $next = $stack;

            $stack = static function (Request $request) use ($middleware, $next, $params): Response {
                return $middleware->handle($request, $next, ...$params);
            };
        }

        return $stack;
    }
}
