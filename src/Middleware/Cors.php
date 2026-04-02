<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Config;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * CORS middleware.
 *
 * Reads configuration from config/cors.php.
 */
class Cors implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        $origins = Config::get('cors.origins', ['*']);
        $methods = Config::get('cors.methods', ['GET', 'POST', 'PUT', 'DELETE']);
        $headers = Config::get('cors.headers', ['Content-Type', 'Authorization']);
        $maxAge = Config::get('cors.max_age', 86400);

        // Handle preflight OPTIONS request
        if ($request->method === 'OPTIONS') {
            $response = Response::html('', 204);
        } else {
            $response = $next($request);
        }

        $origin = $request->header('Origin', '');
        $allowOrigin = in_array('*', $origins, true) ? '*' : (in_array($origin, $origins, true) ? $origin : '');

        if ($allowOrigin !== '') {
            $response->header('Access-Control-Allow-Origin', $allowOrigin);
        }

        $response->header('Access-Control-Allow-Methods', implode(', ', $methods));
        $response->header('Access-Control-Allow-Headers', implode(', ', $headers));
        $response->header('Access-Control-Max-Age', (string) $maxAge);

        return $response;
    }
}
