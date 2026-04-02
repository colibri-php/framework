<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Config;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * Security headers middleware.
 */
class Headers implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        $response = $next($request);

        $response->header('X-Content-Type-Options', 'nosniff');
        $response->header('X-Frame-Options', 'DENY');
        $response->header('Referrer-Policy', 'strict-origin-when-cross-origin');

        if (Config::get('app.https', false)) {
            $response->header('Strict-Transport-Security', 'max-age=31536000; includeSubDomains');
        }

        return $response;
    }
}
