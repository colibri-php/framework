<?php

declare(strict_types=1);

namespace Colibri\Http\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class MakeApiCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'make:api';
    }

    public function description(): string
    {
        return 'Create a new API endpoint in routes/api/';
    }

    public function handle(array $args): int
    {
        $path = $args[0] ?? null;

        if ($path === null) {
            echo "Usage: php colibri make:api <path>\n";

            return 1;
        }

        $dir = base_path('routes/api/' . dirname($path));
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $phpPath = base_path('routes/api/' . $path . '.php');

        if (file_exists($phpPath)) {
            echo "  ⚠ routes/api/$path.php already exists\n";

            return 1;
        }

        $content = <<<'PHP'
<?php

return [
    'GET' => function ($request, $params) {
        return ['message' => 'OK'];
    },
    'POST' => function ($request, $params) {
        return ['created' => true];
    },
];
PHP;

        file_put_contents($phpPath, $content);
        echo "  ✓ routes/api/$path.php\n";

        return 0;
    }
}
