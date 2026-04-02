<?php

declare(strict_types=1);

namespace Colibri\Http\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class RoutesCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'routes';
    }

    public function description(): string
    {
        return 'List all registered routes';
    }

    public function handle(array $args): int
    {
        echo "Web routes (routes/web/):\n";
        $this->scanDir(base_path('routes/web'), base_path('routes/web'), '');

        echo "\nAPI routes (routes/api/):\n";
        $this->scanDir(base_path('routes/api'), base_path('routes/api'), '/api');

        return 0;
    }

    private function scanDir(string $dir, string $baseDir, string $prefix): void
    {
        if (! is_dir($dir)) {
            echo "  (no routes)\n";

            return;
        }

        $entries = scandir($dir) ?: [];
        sort($entries);

        foreach ($entries as $entry) {
            if ($entry[0] === '.' || $entry[0] === '_') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                $this->scanDir($fullPath, $baseDir, $prefix);

                continue;
            }

            if (! str_ends_with($entry, '.php') && ! str_ends_with($entry, '.latte')) {
                continue;
            }

            $relative = str_replace($baseDir, '', $dir . DIRECTORY_SEPARATOR . $entry);
            $relative = str_replace('\\', '/', $relative);

            // Build URL from file path
            $url = $prefix . dirname($relative);
            $file = pathinfo($entry, PATHINFO_FILENAME);
            $ext = pathinfo($entry, PATHINFO_EXTENSION);

            if ($file === 'index') {
                $url = rtrim($url, '/');
                if ($url === '') {
                    $url = '/';
                }
            } else {
                $url = rtrim($url, '/') . '/' . $file;
            }

            $type = $ext === 'latte' ? 'latte' : 'php';
            echo "  $url ($type)\n";
        }
    }
}
