<?php

declare(strict_types=1);

namespace Colibri\Database;

use Colibri\Database\Interfaces\MigrationInterface;
use Colibri\Support\Str;

class Migration
{
    /**
     * Run all pending migrations.
     *
     * @return list<string> List of applied migration filenames.
     */
    public static function up(): array
    {
        self::ensureTable();

        $applied = self::getApplied();
        $files = self::getMigrationFiles();
        $pending = array_diff($files, $applied);
        $batch = self::getNextBatch();
        $ran = [];

        foreach ($pending as $file) {
            $migration = self::load($file);
            $migration->up();

            DB::insert('_migrations', [
                'migration' => $file,
                'batch' => $batch,
                'applied_at' => date('Y-m-d H:i:s'),
            ]);

            $ran[] = $file;
        }

        return $ran;
    }

    /**
     * Rollback the last batch of migrations.
     *
     * @return list<string> List of rolled back migration filenames.
     */
    public static function down(): array
    {
        self::ensureTable();

        $lastBatch = self::getLastBatch();
        if ($lastBatch === 0) {
            return [];
        }

        $migrations = DB::select('_migrations', '*', [
            'batch' => $lastBatch,
            'ORDER' => ['id' => 'DESC'],
        ]) ?? [];

        $rolledBack = [];

        foreach ($migrations as $row) {
            $file = $row['migration'];
            $path = base_path('migrations' . DIRECTORY_SEPARATOR . $file);

            if (file_exists($path)) {
                $migration = self::load($file);
                $migration->down();
            }

            DB::delete('_migrations', ['id' => $row['id']]);
            $rolledBack[] = $file;
        }

        return $rolledBack;
    }

    /**
     * Get list of pending migrations.
     *
     * @return list<string>
     */
    public static function pending(): array
    {
        self::ensureTable();

        $applied = self::getApplied();
        $files = self::getMigrationFiles();

        return array_values(array_diff($files, $applied));
    }

    /**
     * Load a migration file and return the MigrationInterface instance.
     */
    private static function load(string $file): MigrationInterface
    {
        $migration = require base_path('migrations' . DIRECTORY_SEPARATOR . $file);

        if (! $migration instanceof MigrationInterface) {
            throw new \RuntimeException("Migration $file must return an instance of MigrationInterface.");
        }

        return $migration;
    }

    /**
     * Create the _migrations table if it doesn't exist.
     */
    private static function ensureTable(): void
    {
        DB::exec('
            CREATE TABLE IF NOT EXISTS _migrations (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                migration TEXT NOT NULL,
                batch INTEGER NOT NULL,
                applied_at TEXT NOT NULL
            )
        ');
    }

    /**
     * Get list of already applied migration filenames.
     *
     * @return list<string>
     */
    private static function getApplied(): array
    {
        return DB::select('_migrations', 'migration') ?? [];
    }

    /**
     * Get all migration files sorted alphabetically.
     *
     * @return list<string>
     */
    private static function getMigrationFiles(): array
    {
        $dir = base_path('migrations');

        if (! is_dir($dir)) {
            return [];
        }

        $files = array_filter(
            scandir($dir) ?: [],
            fn(string $f) => Str::endsWith($f, '.php'),
        );

        sort($files);

        return $files;
    }

    /**
     * Get the next batch number.
     */
    private static function getNextBatch(): int
    {
        return self::getLastBatch() + 1;
    }

    /**
     * Get the last batch number.
     */
    private static function getLastBatch(): int
    {
        $result = DB::query('SELECT MAX(batch) FROM _migrations');

        return $result ? (int) $result->fetchColumn() : 0;
    }
}
