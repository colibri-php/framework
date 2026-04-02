<?php

declare(strict_types=1);

namespace Colibri\Database\Interfaces;

interface MigrationInterface
{
    /**
     * Apply the migration.
     */
    public function up(): void;

    /**
     * Reverse the migration.
     */
    public function down(): void;
}
