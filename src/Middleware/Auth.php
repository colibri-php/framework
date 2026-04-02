<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Auth\Auth as AuthService;
use Colibri\Config;
use Colibri\Exceptions\HttpException;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * Authentication middleware.
 *
 * Usage: 'auth' (must be logged in) or 'auth:admin' / 'auth:admin,editor' (role check).
 */
class Auth implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        if (! AuthService::check()) {
            // Store intended URL for redirect-back after login
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }
            $_SESSION['_intended_url'] = $request->path;

            $loginPath = Config::get('auth.login_path', '/login');

            return Response::redirect($loginPath);
        }

        // Role check if params provided
        if ($params !== []) {
            $allowedRoles = $params;
            $user = AuthService::user();
            $userRole = $user['role'] ?? '';

            if (! in_array($userRole, $allowedRoles, true)) {
                throw new HttpException(403);
            }
        }

        return $next($request);
    }
}
