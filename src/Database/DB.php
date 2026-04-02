<?php

declare(strict_types=1);

namespace Colibri\Database;

use Medoo\Medoo;
use Colibri\Support\Str;
use Colibri\Support\Pagination;
use Colibri\Config;

/**
 * Database connection and query façade via Medoo.
 */
class DB
{
    private static ?Medoo $medoo = null;

    /**
     * Check if a database connection is configured and available.
     */
    public static function isAvailable(): bool
    {
        try {
            self::init();

            return true;
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Initialize the database connection from config.
     */
    public static function init(): void
    {
        if (self::$medoo !== null) {
            return;
        }

        $driver = Config::get('database.driver', 'sqlite');
        $options = self::buildOptions($driver);

        self::$medoo = new Medoo($options);

        if ($driver === 'sqlite') {
            self::$medoo->pdo->exec('PRAGMA journal_mode=WAL');
        }
    }

    /**
     * Initialize with a specific Medoo instance (useful for testing).
     *
     * @internal
     */
    public static function initWith(Medoo $medoo): void
    {
        self::$medoo = $medoo;
    }

    /**
     * Get the underlying Medoo instance.
     */
    public static function medoo(): Medoo
    {
        if (self::$medoo === null) {
            self::init();
        }

        /** @var Medoo $medoo */
        $medoo = self::$medoo;

        return $medoo;
    }

    /**
     * Select records from a table.
     *
     * @param array<string, mixed>|string $columns
     * @param array<string, mixed>|null $where
     * @return array<int, mixed>|null
     */
    public static function select(string $table, array|string $columns = '*', ?array $where = null): ?array
    {
        return self::medoo()->select($table, $columns, $where); // @phpstan-ignore arguments.count, argument.type
    }

    /**
     * Get a single record.
     *
     * @param array<string, mixed>|string $columns
     * @param array<string, mixed>|null $where
     */
    public static function get(string $table, array|string $columns = '*', ?array $where = null): mixed
    {
        $result = self::medoo()->get($table, $columns, $where);

        return is_array($result) ? $result : null;
    }

    /**
     * Insert a record.
     *
     * @param array<string, mixed> $values
     */
    public static function insert(string $table, array $values): ?\PDOStatement
    {
        return self::medoo()->insert($table, $values);
    }

    /**
     * Update records.
     *
     * @param array<string, mixed> $data
     * @param array<string, mixed>|null $where
     */
    public static function update(string $table, array $data, ?array $where = null): ?\PDOStatement
    {
        return self::medoo()->update($table, $data, $where);
    }

    /**
     * Delete records.
     *
     * @param array<string, mixed> $where
     */
    public static function delete(string $table, array $where): ?\PDOStatement
    {
        return self::medoo()->delete($table, $where);
    }

    /**
     * Count records.
     *
     * @param array<string, mixed>|null $where
     */
    public static function count(string $table, ?array $where = null): int
    {
        return (int) self::medoo()->count($table, $where);
    }

    /**
     * Check if a record exists.
     *
     * @param array<string, mixed> $where
     */
    public static function has(string $table, array $where): bool
    {
        return self::medoo()->has($table, $where);
    }

    /**
     * Paginate results.
     *
     * @param array<string, mixed>|string $columns
     * @param array<string, mixed>|null $where
     */
    public static function paginate(string $table, array|string $columns = '*', ?array $where = null, int $page = 1, int $perPage = 15): Pagination
    {
        $total = self::count($table, $where);
        $lastPage = max(1, (int) ceil($total / $perPage));
        $page = max(1, min($page, $lastPage));
        $offset = ($page - 1) * $perPage;

        $paginatedWhere = $where ?? [];
        $paginatedWhere['LIMIT'] = [$offset, $perPage];

        $data = self::select($table, $columns, $paginatedWhere) ?? [];

        return new Pagination($data, $total, $page, $perPage, $lastPage);
    }

    /**
     * Execute a raw SQL statement (CREATE, DROP, ALTER, etc.).
     */
    public static function exec(string $sql): int|false
    {
        return self::medoo()->pdo->exec($sql);
    }

    /**
     * Execute a raw SQL query with optional parameters.
     *
     * @param array<int|string, mixed> $params
     */
    public static function query(string $sql, array $params = []): ?\PDOStatement
    {
        return self::medoo()->query($sql, $params);
    }

    /**
     * Get the last inserted ID.
     */
    public static function id(): ?string
    {
        return self::medoo()->id() ?: null;
    }

    /**
     * Build Medoo options from config.
     *
     * @return array<string, mixed>
     */
    private static function buildOptions(string $driver): array
    {
        if ($driver === 'sqlite') {
            $path = Config::get('database.path', 'storage/database.sqlite');

            if (! Str::startsWith($path, '/') && ! Str::contains($path, ':\\') && $path !== ':memory:') {
                $path = base_path($path);
            }

            return [
                'type' => 'sqlite',
                'database' => $path,
            ];
        }

        return [
            'type' => $driver,
            'host' => Config::get('database.host', '127.0.0.1'),
            'port' => (int) Config::get('database.port', 3306),
            'database' => Config::get('database.name', 'colibri'),
            'username' => Config::get('database.user', 'root'),
            'password' => Config::get('database.pass', ''),
        ];
    }
}
