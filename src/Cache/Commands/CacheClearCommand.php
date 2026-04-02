<?php

declare(strict_types=1);

namespace Colibri\Cache\Commands;

use Colibri\Cache\Cache;
use Colibri\CLI\Interfaces\CommandInterface;

class CacheClearCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'cache:clear';
    }

    public function description(): string
    {
        return 'Clear cache [all|app|views|phpstan] (default: all)';
    }

    public function handle(array $args): int
    {
        $target = $args[0] ?? 'all';

        $debug = \Colibri\Config::get('app.debug', false);

        $targets = match ($target) {
            'all' => $debug ? ['app', 'views', 'phpstan'] : ['app', 'views'],
            'app', 'views', 'phpstan' => [$target],
            default => null,
        };

        if ($targets === null) {
            echo "Usage: php colibri cache:clear [all|app|views|phpstan]\n";

            return 1;
        }

        foreach ($targets as $t) {
            match ($t) {
                'app' => $this->clearApp(),
                'views' => $this->clearViews(),
                'phpstan' => $this->clearPhpstan(),
            };
        }

        return 0;
    }

    private function clearApp(): void
    {
        Cache::clear();
        echo "  ✓ Application cache cleared.\n";
    }

    private function clearViews(): void
    {
        $dir = base_path('storage/cache/views');
        if (is_dir($dir)) {
            self::clearDirectory($dir);
        }
        echo "  ✓ Compiled views cleared.\n";
    }

    private function clearPhpstan(): void
    {
        $dir = base_path('storage/cache/phpstan');
        if (is_dir($dir)) {
            self::clearDirectory($dir);
        }
        echo "  ✓ PHPStan cache cleared.\n";
    }

    private static function clearDirectory(string $dir): void
    {
        $iterator = new \RecursiveIteratorIterator(
            new \RecursiveDirectoryIterator($dir, \RecursiveDirectoryIterator::SKIP_DOTS),
            \RecursiveIteratorIterator::CHILD_FIRST,
        );

        foreach ($iterator as $file) {
            if ($file->isDir()) {
                @rmdir($file->getPathname());
            } else {
                @unlink($file->getPathname());
            }
        }
    }
}
