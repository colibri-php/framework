<?php

declare(strict_types=1);

namespace Colibri\Middleware;

use Colibri\Database\DB;
use Colibri\Exceptions\HttpException;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

/**
 * Rate limiting middleware.
 *
 * Usage: 'rate:60,1' = 60 requests per 1 minute.
 */
class Rate implements MiddlewareInterface
{
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        $max = (int) ($params[0] ?? 60);
        $decayMinutes = (int) ($params[1] ?? 1);

        self::ensureTable();
        self::cleanup();

        $ip = $_SERVER['REMOTE_ADDR'] ?? '127.0.0.1';
        $route = $request->path;
        $key = md5($ip . '|' . $route);
        $windowStart = date('Y-m-d H:i:s', time() - ($decayMinutes * 60));

        // Count hits in the current window
        $hits = DB::count('_rate_limits', [
            'key' => $key,
            'created_at[>=]' => $windowStart,
        ]);

        if ($hits >= $max) {
            $retryAfter = $decayMinutes * 60;

            throw new HttpException(429, "Rate limit exceeded. Retry after $retryAfter seconds.");
        }

        // Record this hit
        DB::insert('_rate_limits', [
            'key' => $key,
            'created_at' => date('Y-m-d H:i:s'),
        ]);

        return $next($request);
    }

    private static function ensureTable(): void
    {
        DB::exec('
            CREATE TABLE IF NOT EXISTS _rate_limits (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                key TEXT NOT NULL,
                created_at TEXT NOT NULL
            )
        ');
    }

    private static function cleanup(): void
    {
        $cutoff = date('Y-m-d H:i:s', time() - 3600);
        DB::exec("DELETE FROM _rate_limits WHERE created_at < '$cutoff'");
    }
}
