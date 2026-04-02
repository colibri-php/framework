<?php

declare(strict_types=1);

namespace Colibri\Middleware\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class MakeMiddlewareCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'make:middleware';
    }

    public function description(): string
    {
        return 'Create a new middleware in middleware/';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            echo "Usage: php colibri make:middleware <name>\n";

            return 1;
        }

        $dir = base_path('middleware');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $file = $dir . DIRECTORY_SEPARATOR . strtolower($name) . '.php';

        if (file_exists($file)) {
            echo "  ⚠ middleware/$name.php already exists\n";

            return 1;
        }

        $content = <<<'PHP'
<?php

use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Http\Request;
use Colibri\Http\Response;

return new class implements MiddlewareInterface {
    public function handle(Request $request, callable $next, string ...$params): Response
    {
        // Before the request...

        $response = $next($request);

        // After the request...

        return $response;
    }
};
PHP;

        file_put_contents($file, $content);
        echo "  ✓ middleware/" . strtolower($name) . ".php\n";

        return 0;
    }
}
