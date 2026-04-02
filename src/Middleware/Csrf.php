<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Exceptions\HttpException;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * CSRF protection middleware.
 *
 * Generates a token per session and validates it on state-changing methods.
 */
class Csrf implements MiddlewareInterface
{
    private const PROTECTED_METHODS = ['POST', 'PUT', 'PATCH', 'DELETE'];

    public function handle(Request $request, callable $next, string ...$params): Response
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }

        // Generate token if not exists
        if (! isset($_SESSION['_csrf_token'])) {
            $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
        }

        // Validate on state-changing methods
        if (in_array($request->method, self::PROTECTED_METHODS, true)) {
            $token = $request->input('_token') ?? $request->header('X-CSRF-Token');

            if ($token === null || ! hash_equals($_SESSION['_csrf_token'], $token)) {
                throw new HttpException(403, 'CSRF token mismatch.');
            }
        }

        return $next($request);
    }
}
