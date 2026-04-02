<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Database\Migration;
use Colibri\View\View;
use Medoo\Medoo;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    $_ENV['DB_PATH'] = ':memory:';
    App::boot(dirname(__DIR__, 2));

    DB::initWith(new Medoo([
        'type' => 'sqlite',
        'database' => ':memory:',
    ]));
});

test('up runs pending migrations', function () {
    $ran = Migration::up();

    expect($ran)->toContain('001_create_users.php');
});

test('up is idempotent', function () {
    Migration::up();
    $ran = Migration::up();

    expect($ran)->toBeEmpty();
});

test('pending returns pending migrations', function () {
    $pending = Migration::pending();

    expect($pending)->toContain('001_create_users.php');

    Migration::up();

    expect(Migration::pending())->toBeEmpty();
});

test('down rolls back the last batch', function () {
    Migration::up();

    $rolledBack = Migration::down();

    expect($rolledBack)->toContain('001_create_users.php');
    expect(Migration::pending())->toContain('001_create_users.php');
});

test('down with nothing to rollback returns empty', function () {
    $rolledBack = Migration::down();

    expect($rolledBack)->toBeEmpty();
});

test('migration creates users table', function () {
    Migration::up();

    DB::insert('users', [
        'email' => 'test@test.com',
        'password' => 'hashed',
        'name' => 'Test',
        'role' => 'admin',
    ]);

    $user = DB::get('users', '*', ['email' => 'test@test.com']);

    expect($user)->not->toBeNull();
    expect($user['role'])->toBe('admin');
});
