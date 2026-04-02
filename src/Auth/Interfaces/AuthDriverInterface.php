<?php

declare(strict_types=1);

namespace Colibri\Auth\Interfaces;

interface AuthDriverInterface
{
    /**
     * Create a new user.
     *
     * @param array<string, mixed> $data Must contain 'email' and 'password' (plain text).
     * @return array<string, mixed>|null The created user, or null on failure.
     */
    public function create(array $data): ?array;

    /**
     * Find a user by email.
     *
     * @return array<string, mixed>|null
     */
    public function findByEmail(string $email): ?array;

    /**
     * Find a user by ID.
     *
     * @return array<string, mixed>|null
     */
    public function findById(int|string $id): ?array;
}
