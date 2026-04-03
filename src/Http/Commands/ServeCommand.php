<?php

declare(strict_types=1);

namespace Colibri\Http\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class ServeCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'serve';
    }

    public function description(): string
    {
        return 'Start development server (default: localhost:8000)';
    }

    public function handle(array $args): int
    {
        $host = $args[0] ?? 'localhost';
        $port = $args[1] ?? '8000';

        echo "Colibri development server started: http://$host:$port\n";
        echo "Press Ctrl+C to stop.\n\n";

        $process = proc_open("php -S $host:$port -t public", [STDIN, STDOUT, STDERR], $pipes);

        if (is_resource($process)) {
            proc_close($process);
        }

        return 0;
    }
}
