<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
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

    // Set in-memory SQLite BEFORE boot so config picks it up
    $_ENV['DB_PATH'] = ':memory:';

    App::boot(dirname(__DIR__, 2));

    // Use in-memory SQLite for tests
    DB::initWith(new Medoo([
        'type' => 'sqlite',
        'database' => ':memory:',
    ]));

    // Create a test table
    DB::medoo()->pdo->exec('
        CREATE TABLE IF NOT EXISTS users (
            id INTEGER PRIMARY KEY AUTOINCREMENT,
            name TEXT NOT NULL,
            email TEXT NOT NULL
        )
    ');
});

test('insert and select work', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);
    DB::insert('users', ['name' => 'Bob', 'email' => 'bob@test.com']);

    $users = DB::select('users', '*');

    expect($users)->toHaveCount(2);
    expect($users[0]['name'])->toBe('Alice');
    expect($users[1]['name'])->toBe('Bob');
});

test('get returns a single record', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);

    $user = DB::get('users', '*', ['name' => 'Alice']);

    expect($user)->not->toBeNull();
    expect($user['email'])->toBe('alice@test.com');
});

test('get returns null when not found', function () {
    $user = DB::get('users', '*', ['name' => 'Nobody']);

    expect($user)->toBeNull();
});

test('update modifies records', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);

    DB::update('users', ['email' => 'new@test.com'], ['name' => 'Alice']);

    $user = DB::get('users', '*', ['name' => 'Alice']);
    expect($user['email'])->toBe('new@test.com');
});

test('delete removes records', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);
    DB::insert('users', ['name' => 'Bob', 'email' => 'bob@test.com']);

    DB::delete('users', ['name' => 'Alice']);

    expect(DB::count('users'))->toBe(1);
});

test('count returns the number of records', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);
    DB::insert('users', ['name' => 'Bob', 'email' => 'bob@test.com']);

    expect(DB::count('users'))->toBe(2);
    expect(DB::count('users', ['name' => 'Alice']))->toBe(1);
});

test('has checks existence', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);

    expect(DB::has('users', ['name' => 'Alice']))->toBeTrue();
    expect(DB::has('users', ['name' => 'Nobody']))->toBeFalse();
});

test('id returns last inserted id', function () {
    DB::insert('users', ['name' => 'Alice', 'email' => 'alice@test.com']);

    expect(DB::id())->toBe('1');
});

test('paginate returns paginated results', function () {
    for ($i = 1; $i <= 25; $i++) {
        DB::insert('users', ['name' => "User $i", 'email' => "user$i@test.com"]);
    }

    $page1 = DB::paginate('users', '*', null, page: 1, perPage: 10);

    expect($page1->items())->toHaveCount(10);
    expect($page1->total())->toBe(25);
    expect($page1->currentPage())->toBe(1);
    expect($page1->perPage())->toBe(10);
    expect($page1->totalPages())->toBe(3);

    $page3 = DB::paginate('users', '*', null, page: 3, perPage: 10);

    expect($page3->items())->toHaveCount(5);
    expect($page3->currentPage())->toBe(3);
});

test('init from config creates connection', function () {
    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    DB::init();

    expect(DB::medoo())->toBeInstanceOf(Medoo::class);
});

test('env helper works with defaults', function () {
    expect(env('APP_NAME'))->toBe('Colibri');
    expect(env('NONEXISTENT', 'fallback'))->toBe('fallback');
});

test('env helper casts boolean strings', function () {
    expect(env('APP_DEBUG'))->toBeTrue();
});
