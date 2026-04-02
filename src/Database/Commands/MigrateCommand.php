<?php

declare(strict_types=1);

namespace Colibri\Database\Commands;

use Colibri\CLI\Interfaces\CommandInterface;
use Colibri\Database\Migration;

class MigrateCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'migrate';
    }

    public function description(): string
    {
        return 'Run pending migrations';
    }

    public function handle(array $args): int
    {
        $ran = Migration::up();

        if ($ran === []) {
            echo "Nothing to migrate.\n";

            return 0;
        }

        foreach ($ran as $file) {
            echo "  ✓ $file\n";
        }

        echo count($ran) . " migration(s) applied.\n";

        return 0;
    }
}
