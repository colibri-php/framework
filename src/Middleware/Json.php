<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * JSON mode middleware for API routes.
 *
 * Forces Content-Type: application/json on responses.
 */
class Json implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        $response = $next($request);

        $response->header('Content-Type', 'application/json');

        return $response;
    }
}
