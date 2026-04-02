<?php

declare(strict_types=1);

namespace Colibri\Middleware\Interfaces;

use Colibri\Http\Request;
use Colibri\Http\Response;

interface MiddlewareInterface
{
    /**
     * Handle the request.
     *
     * @param callable(Request): Response $next
     */
    public function handle(Request $request, callable $next, string ...$params): Response;
}
