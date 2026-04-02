<?php

declare(strict_types=1);

namespace Colibri\Auth\Drivers;

use Colibri\Database\DB;
use Colibri\Auth\Interfaces\AuthDriverInterface;

class DbAuthDriver implements AuthDriverInterface
{
    public function create(array $data): ?array
    {
        DB::insert('users', $data);
        $id = DB::id();

        if ($id === null) {
            return null;
        }

        return DB::get('users', '*', ['id' => (int) $id]);
    }

    public function findByEmail(string $email): ?array
    {
        return DB::get('users', '*', ['email' => $email]);
    }

    public function findById(int|string $id): ?array
    {
        return DB::get('users', '*', ['id' => $id]);
    }
}
