<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Auth\Auth;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Database\Migration;
use Colibri\View\View;
use Medoo\Medoo;

beforeEach(function () {
    // Reset singletons
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

    $_ENV['DB_PATH'] = ':memory:';
    App::boot(dirname(__DIR__, 2));

    DB::initWith(new Medoo([
        'type' => 'sqlite',
        'database' => ':memory:',
    ]));

    Migration::up();

    // Ensure session is available for tests
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION = [];
});

test('register creates a user with hashed password', function () {
    $user = Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('alice@test.com');
    expect($user['role'])->toBe('user');
    expect($user['password'])->not->toBe('secret123');
    expect(password_verify('secret123', $user['password']))->toBeTrue();
});

test('register uses default role', function () {
    $user = Auth::register([
        'email' => 'bob@test.com',
        'password' => 'secret',
        'name' => 'Bob',
    ]);

    expect($user['role'])->toBe('user');
});

test('register accepts custom role', function () {
    $user = Auth::register([
        'email' => 'admin@test.com',
        'password' => 'secret',
        'name' => 'Admin',
        'role' => 'admin',
    ]);

    expect($user['role'])->toBe('admin');
});

test('attempt succeeds with valid credentials', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    $result = Auth::attempt('alice@test.com', 'secret123');

    expect($result)->toBeTrue();
    expect($_SESSION['_user_id'])->not->toBeNull();
});

test('attempt fails with wrong password', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret123',
        'name' => 'Alice',
    ]);

    $result = Auth::attempt('alice@test.com', 'wrongpassword');

    expect($result)->toBeFalse();
});

test('attempt fails with unknown email', function () {
    $result = Auth::attempt('nobody@test.com', 'secret');

    expect($result)->toBeFalse();
});

test('user returns authenticated user after login', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret',
        'name' => 'Alice',
    ]);

    Auth::attempt('alice@test.com', 'secret');

    $user = Auth::user();

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('alice@test.com');
});

test('user returns null when not authenticated', function () {
    expect(Auth::user())->toBeNull();
});

test('check returns true when authenticated', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret',
        'name' => 'Alice',
    ]);

    Auth::attempt('alice@test.com', 'secret');

    expect(Auth::check())->toBeTrue();
});

test('check returns false when not authenticated', function () {
    expect(Auth::check())->toBeFalse();
});

test('is checks the user role', function () {
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

test('logout clears the session', function () {
    Auth::register([
        'email' => 'alice@test.com',
        'password' => 'secret',
        'name' => 'Alice',
    ]);

    Auth::attempt('alice@test.com', 'secret');
    expect(Auth::check())->toBeTrue();

    Auth::logout();

    expect(Auth::check())->toBeFalse();
    expect(Auth::user())->toBeNull();
});
