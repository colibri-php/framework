<?php

declare(strict_types=1);

namespace Colibri\CLI\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class UpCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'up';
    }

    public function description(): string
    {
        return 'Bring the application out of maintenance mode';
    }

    public function handle(array $args): int
    {
        $file = base_path('storage/maintenance');

        if (! file_exists($file)) {
            echo "  Application is not in maintenance mode.\n";

            return 0;
        }

        unlink($file);
        echo "  ✓ Application is now live.\n";

        return 0;
    }
}
