<?php

declare(strict_types=1);

namespace Colibri\CLI\Interfaces;

interface CommandInterface
{
    /**
     * The command signature (e.g. 'migrate' or 'cache:clear').
     */
    public function signature(): string;

    /**
     * A short description for the help screen.
     */
    public function description(): string;

    /**
     * Execute the command.
     *
     * @param list<string> $args
     * @return int Exit code (0 = success, 1 = error)
     */
    public function handle(array $args): int;
}
