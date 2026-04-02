<?php

declare(strict_types=1);

use Colibri\Support\Arr;

test('get retrieves a value with dot notation', function () {
    $data = ['user' => ['name' => 'Alice', 'email' => 'alice@test.com']];

    expect(Arr::get($data, 'user.name'))->toBe('Alice');
});

test('get returns default when key is missing', function () {
    $data = ['user' => ['name' => 'Alice']];

    expect(Arr::get($data, 'user.age', 25))->toBe(25);
});

test('get returns top-level value', function () {
    $data = ['name' => 'Alice'];

    expect(Arr::get($data, 'name'))->toBe('Alice');
});

test('has checks deep key existence', function () {
    $data = ['user' => ['name' => 'Alice']];

    expect(Arr::has($data, 'user.name'))->toBeTrue();
    expect(Arr::has($data, 'user.age'))->toBeFalse();
});

test('only returns specified keys', function () {
    $data = ['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'secret'];

    $result = Arr::only($data, ['name', 'email']);

    expect($result)->toBe(['name' => 'Alice', 'email' => 'alice@test.com']);
});

test('except excludes specified keys', function () {
    $data = ['name' => 'Alice', 'email' => 'alice@test.com', 'password' => 'secret'];

    $result = Arr::except($data, ['password']);

    expect($result)->toBe(['name' => 'Alice', 'email' => 'alice@test.com']);
});
