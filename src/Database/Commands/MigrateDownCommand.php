<?php

declare(strict_types=1);

namespace Colibri\Database\Commands;

use Colibri\CLI\Interfaces\CommandInterface;
use Colibri\Database\Migration;

class MigrateDownCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'migrate:down';
    }

    public function description(): string
    {
        return 'Rollback the last migration batch';
    }

    public function handle(array $args): int
    {
        $rolledBack = Migration::down();

        if ($rolledBack === []) {
            echo "Nothing to rollback.\n";

            return 0;
        }

        foreach ($rolledBack as $file) {
            echo "  ↩ $file\n";
        }

        echo count($rolledBack) . " migration(s) rolled back.\n";

        return 0;
    }
}
