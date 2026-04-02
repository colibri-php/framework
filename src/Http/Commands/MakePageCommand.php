<?php

declare(strict_types=1);

namespace Colibri\Http\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class MakePageCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'make:page';
    }

    public function description(): string
    {
        return 'Create a new page in routes/web/';
    }

    public function handle(array $args): int
    {
        $path = $args[0] ?? null;

        if ($path === null) {
            echo "Usage: php colibri make:page <path> [--latte|--php|--both]\n";

            return 1;
        }

        $type = 'latte';
        foreach ($args as $arg) {
            if ($arg === '--php') {
                $type = 'php';
            }
            if ($arg === '--both') {
                $type = 'both';
            }
            if ($arg === '--latte') {
                $type = 'latte';
            }
        }

        $dir = base_path('routes/web/' . dirname($path));
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $basePath = base_path('routes/web/' . $path);
        $name = basename($path);

        if ($type === 'latte' || $type === 'both') {
            $lattePath = $basePath . '.latte';
            if (! file_exists($lattePath)) {
                file_put_contents($lattePath, "{page title: '$name'}\n\n{block content}\n<h1>$name</h1>\n{/block}\n");
                echo "  ✓ routes/web/$path.latte\n";
            } else {
                echo "  ⚠ routes/web/$path.latte already exists\n";
            }
        }

        if ($type === 'php' || $type === 'both') {
            $phpPath = $basePath . '.php';
            if (! file_exists($phpPath)) {
                file_put_contents($phpPath, "<?php\n\n// \$page, \$request, \$app, \$params available\n");
                echo "  ✓ routes/web/$path.php\n";
            } else {
                echo "  ⚠ routes/web/$path.php already exists\n";
            }
        }

        return 0;
    }
}
