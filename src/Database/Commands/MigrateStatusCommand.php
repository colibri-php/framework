<?php

declare(strict_types=1);

namespace Colibri\Database\Commands;

use Colibri\CLI\Interfaces\CommandInterface;
use Colibri\Database\Migration;

class MigrateStatusCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'migrate:status';
    }

    public function description(): string
    {
        return 'Show pending migrations';
    }

    public function handle(array $args): int
    {
        $pending = Migration::pending();

        if ($pending === []) {
            echo "All migrations are up to date.\n";

            return 0;
        }

        echo count($pending) . " pending migration(s):\n";
        foreach ($pending as $file) {
            echo "  - $file\n";
        }

        return 0;
    }
}
