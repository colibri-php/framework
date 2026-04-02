<?php

declare(strict_types=1);

namespace Colibri\CLI\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class DownCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'down';
    }

    public function description(): string
    {
        return 'Put the application into maintenance mode';
    }

    public function handle(array $args): int
    {
        $file = base_path('storage/maintenance');

        $data = ['time' => date('Y-m-d H:i:s')];

        // Parse --secret=xxx
        foreach ($args as $arg) {
            if (str_starts_with($arg, '--secret=')) {
                $data['secret'] = substr($arg, 9);
            }
        }

        file_put_contents($file, json_encode($data));
        echo "  ✓ Application is now in maintenance mode.\n";

        if (isset($data['secret'])) {
            echo "  Secret: {$data['secret']}\n";
        }

        return 0;
    }
}
