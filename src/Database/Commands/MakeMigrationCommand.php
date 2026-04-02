<?php

declare(strict_types=1);

namespace Colibri\Database\Commands;

use Colibri\CLI\Interfaces\CommandInterface;

class MakeMigrationCommand implements CommandInterface
{
    public function signature(): string
    {
        return 'make:migration';
    }

    public function description(): string
    {
        return 'Create a new migration file';
    }

    public function handle(array $args): int
    {
        $name = $args[0] ?? null;

        if ($name === null) {
            echo "Usage: php colibri make:migration <name>\n";

            return 1;
        }

        $dir = base_path('migrations');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        // Find next number
        $files = glob($dir . '/*.php') ?: [];
        $next = count($files) + 1;
        $prefix = str_pad((string) $next, 3, '0', STR_PAD_LEFT);
        $filename = "{$prefix}_{$name}.php";

        $content = <<<'PHP'
<?php

use Colibri\Database\DB;
use Colibri\Database\Interfaces\MigrationInterface;

return new class implements MigrationInterface {
    public function up(): void
    {
        DB::exec('
            -- CREATE TABLE ...
        ');
    }

    public function down(): void
    {
        DB::exec('
            -- DROP TABLE IF EXISTS ...
        ');
    }
};
PHP;

        file_put_contents($dir . DIRECTORY_SEPARATOR . $filename, $content);
        echo "  ✓ migrations/$filename\n";

        return 0;
    }
}
