<?php

declare(strict_types=1);

namespace Colibri\Auth\Drivers;

use Colibri\Storage\Data;
use Colibri\Auth\Interfaces\AuthDriverInterface;

class FileAuthDriver implements AuthDriverInterface
{
    public function create(array $data): ?array
    {
        $id = bin2hex(random_bytes(8));
        $data['id'] = $id;

        Data::put("users/$id", $data);

        return $data;
    }

    public function findByEmail(string $email): ?array
    {
        $users = Data::where('users', ['email' => $email]);

        return $users[0] ?? null;
    }

    public function findById(int|string $id): ?array
    {
        return Data::get("users/$id");
    }
}
