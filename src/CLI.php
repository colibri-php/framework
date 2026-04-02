<?php

declare(strict_types=1);

namespace Colibri;

use Colibri\CLI\Interfaces\CommandInterface;

class CLI
{
    /** @var array<string, CommandInterface> */
    private static array $commands = [];

    /**
     * Register a command.
     */
    public static function register(CommandInterface $command): void
    {
        self::$commands[$command->signature()] = $command;
    }

    /**
     * Boot built-in commands and user commands from config.
     */
    public static function boot(): void
    {
        // Built-in commands (each domain registers its own)
        $builtinCommands = [
            \Colibri\Http\Commands\ServeCommand::class,
            \Colibri\Http\Commands\RoutesCommand::class,
            \Colibri\Http\Commands\MakePageCommand::class,
            \Colibri\Http\Commands\MakeApiCommand::class,
            \Colibri\Database\Commands\MigrateCommand::class,
            \Colibri\Database\Commands\MigrateDownCommand::class,
            \Colibri\Database\Commands\MigrateStatusCommand::class,
            \Colibri\Database\Commands\MakeMigrationCommand::class,
            \Colibri\Cache\Commands\CacheClearCommand::class,
            \Colibri\Middleware\Commands\MakeMiddlewareCommand::class,
            \Colibri\CLI\Commands\DownCommand::class,
            \Colibri\CLI\Commands\UpCommand::class,
        ];

        foreach ($builtinCommands as $class) {
            self::register(new $class());
        }

        // User commands from config/commands.php
        $userCommands = Config::get('commands', []);
        if (is_array($userCommands)) {
            foreach ($userCommands as $class) {
                if (is_string($class) && class_exists($class)) {
                    self::register(new $class());
                }
            }
        }
    }

    /**
     * Run the CLI dispatcher.
     *
     * @param list<string> $argv
     */
    public static function run(array $argv): void
    {
        self::boot();

        $name = $argv[1] ?? null;
        $args = array_slice($argv, 2);

        if ($name === null || $name === 'help') {
            self::help();

            return;
        }

        if (! isset(self::$commands[$name])) {
            echo "Unknown command: $name\n\n";
            self::help();

            return;
        }

        $exitCode = self::$commands[$name]->handle($args);
        exit($exitCode);
    }

    /**
     * Display the help screen with all registered commands.
     */
    private static function help(): void
    {
        echo "Colibri CLI\n\n";
        echo "Usage: php colibri <command> [arguments]\n\n";

        // Group commands by prefix
        $groups = [];
        foreach (self::$commands as $command) {
            $sig = $command->signature();
            $prefix = str_contains($sig, ':') ? explode(':', $sig)[0] : '';
            $groups[$prefix][] = $command;
        }

        // Sort groups alphabetically, standalone commands first
        ksort($groups);
        if (isset($groups[''])) {
            $standalone = $groups[''];
            unset($groups['']);
            $groups = ['' => $standalone] + $groups;
        }

        // Find max signature length for padding
        $maxLen = 0;
        foreach (self::$commands as $command) {
            $maxLen = max($maxLen, strlen($command->signature()));
        }

        foreach ($groups as $prefix => $commands) {
            usort($commands, fn($a, $b) => strcmp($a->signature(), $b->signature()));

            $label = $prefix !== '' ? $prefix : 'Commands';
            echo "\033[33m$label\033[0m\n";

            foreach ($commands as $command) {
                $sig = str_pad($command->signature(), $maxLen + 2);
                echo "  \033[32m$sig\033[0m {$command->description()}\n";
            }

            echo "\n";
        }
    }
}
