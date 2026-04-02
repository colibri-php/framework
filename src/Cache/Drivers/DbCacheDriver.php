<?php

declare(strict_types=1);

namespace Colibri\Cache\Drivers;

use Colibri\Database\DB;
use Colibri\Cache\Interfaces\CacheDriverInterface;

class DbCacheDriver implements CacheDriverInterface
{
    private bool $tableReady = false;

    public function get(string $key, mixed $default = null): mixed
    {
        $this->ensureTable();

        $row = DB::get('_cache', '*', ['key' => $key]);

        if ($row === null) {
            return $default;
        }

        if ($row['expires_at'] !== null && $row['expires_at'] < time()) {
            DB::delete('_cache', ['key' => $key]);

            return $default;
        }

        return unserialize($row['value']);
    }

    public function put(string $key, mixed $value, int $minutes = 0): void
    {
        $this->ensureTable();

        $expiresAt = $minutes !== 0 ? time() + ($minutes * 60) : null;

        // Delete existing then insert (upsert)
        DB::delete('_cache', ['key' => $key]);
        DB::insert('_cache', [
            'key' => $key,
            'value' => serialize($value),
            'expires_at' => $expiresAt,
        ]);
    }

    public function has(string $key): bool
    {
        return $this->get($key, $sentinel = new \stdClass()) !== $sentinel;
    }

    public function forget(string $key): bool
    {
        $this->ensureTable();

        return DB::has('_cache', ['key' => $key])
            && DB::delete('_cache', ['key' => $key]) !== null;
    }

    public function clear(): void
    {
        $this->ensureTable();

        DB::exec('DELETE FROM _cache');
    }

    private function ensureTable(): void
    {
        if ($this->tableReady) {
            return;
        }

        DB::exec('
            CREATE TABLE IF NOT EXISTS _cache (
                key TEXT PRIMARY KEY,
                value TEXT NOT NULL,
                expires_at INTEGER
            )
        ');

        $this->tableReady = true;
    }
}
