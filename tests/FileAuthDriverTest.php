<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Auth\Auth;
use Colibri\Auth\Drivers\FileAuthDriver;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Storage\Data;
use Colibri\View\View;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    $authRef = new ReflectionClass(Auth::class);
    $authRef->setStaticPropertyValue('currentUser', false);
    $authRef->setStaticPropertyValue('driverInstance', null);

    // Set auth driver to file BEFORE boot
    $_ENV['AUTH_DRIVER'] = 'file';

    App::boot(dirname(__DIR__, 2));

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION = [];

    // Clean test users
    $usersDir = base_path('data/users');
    if (is_dir($usersDir)) {
        $files = glob($usersDir . '/*.json') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
    }
});

afterEach(function () {
    // Clean test users
    $usersDir = base_path('data/users');
    if (is_dir($usersDir)) {
        $files = glob($usersDir . '/*.json') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
    }

    $_ENV['AUTH_DRIVER'] = 'db';
});

// --- FileAuthDriver direct ---

test('FileAuthDriver creates a user as JSON file', function () {
    $driver = new FileAuthDriver();

    $user = $driver->create([
        'email' => 'alice@test.com',
        'password' => 'hashed',
        'name' => 'Alice',
        'role' => 'user',
    ]);

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('alice@test.com');
    expect(Data::exists('users/' . $user['id']))->toBeTrue();
});

test('FileAuthDriver finds user by email', function () {
    $driver = new FileAuthDriver();
    $driver->create([
        'email' => 'bob@test.com',
        'password' => 'hashed',
        'name' => 'Bob',
        'role' => 'user',
    ]);

    $user = $driver->findByEmail('bob@test.com');

    expect($user)->not->toBeNull();
    expect($user['name'])->toBe('Bob');
});

test('FileAuthDriver finds user by ID', function () {
    $driver = new FileAuthDriver();
    $created = $driver->create([
        'email' => 'carol@test.com',
        'password' => 'hashed',
        'name' => 'Carol',
        'role' => 'admin',
    ]);

    $user = $driver->findById($created['id']);

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('carol@test.com');
});

test('FileAuthDriver returns null for unknown email', function () {
    $driver = new FileAuthDriver();

    expect($driver->findByEmail('nobody@test.com'))->toBeNull();
});

// --- Full Auth flow without DB ---

test('register works with file driver', function () {
    $user = Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('alice@test.com');
    expect($user['role'])->toBe('user');
    expect(password_verify('secret123', $user['password']))->toBeTrue();
});

test('attempt works with file driver', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    $result = Auth::attempt('alice@test.com', 'secret123');

    expect($result)->toBeTrue();
    expect(Auth::check())->toBeTrue();
    expect(Auth::user()['email'])->toBe('alice@test.com');
});

test('attempt fails with wrong password on file driver', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    expect(Auth::attempt('alice@test.com', 'wrong'))->toBeFalse();
});

test('logout works with file driver', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret',
        'name' => 'Alice',
    ]);

    Auth::attempt('alice@test.com', 'secret');
    Auth::logout();

    expect(Auth::check())->toBeFalse();
});

test('is() checks role with file driver', function () {
    Auth::register([
        'email' => 'admin@test.com',
        'password' => 'secret',
        'name' => 'Admin',
        'role' => 'admin',
    ]);

    Auth::attempt('admin@test.com', 'secret');

    expect(Auth::is('admin'))->toBeTrue();
    expect(Auth::is('user'))->toBeFalse();
});
