<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Cache\Cache;
use Colibri\Cache\Drivers\DbCacheDriver;
use Colibri\Config;
use Colibri\Database\DB;
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

    $cacheRef = new ReflectionClass(Cache::class);
    $cacheRef->setStaticPropertyValue('instance', null);

    $_ENV['DB_PATH'] = ':memory:';
    $_ENV['CACHE_DRIVER'] = 'db';

    App::boot(dirname(__DIR__, 2));

    DB::initWith(new Medoo([
        'type' => 'sqlite',
        'database' => ':memory:',
    ]));
});

afterEach(function () {
    $_ENV['CACHE_DRIVER'] = 'file';
});

// --- DbCacheDriver direct ---

test('DbCacheDriver auto-creates _cache table', function () {
    $driver = new DbCacheDriver();
    $driver->put('test', 'value');

    // If we got here without error, the table was created
    expect(DB::has('_cache', ['key' => 'test']))->toBeTrue();
});

test('DbCacheDriver put and get', function () {
    $driver = new DbCacheDriver();
    $driver->put('name', 'Colibri', 60);

    expect($driver->get('name'))->toBe('Colibri');
});

test('DbCacheDriver get returns default when missing', function () {
    $driver = new DbCacheDriver();

    expect($driver->get('missing', 'default'))->toBe('default');
});

test('DbCacheDriver has', function () {
    $driver = new DbCacheDriver();
    $driver->put('exists', true);

    expect($driver->has('exists'))->toBeTrue();
    expect($driver->has('missing'))->toBeFalse();
});

test('DbCacheDriver forget', function () {
    $driver = new DbCacheDriver();
    $driver->put('temp', 'value');
    $driver->forget('temp');

    expect($driver->has('temp'))->toBeFalse();
});

test('DbCacheDriver clear', function () {
    $driver = new DbCacheDriver();
    $driver->put('a', 1);
    $driver->put('b', 2);
    $driver->clear();

    expect($driver->has('a'))->toBeFalse();
    expect($driver->has('b'))->toBeFalse();
});

test('DbCacheDriver respects TTL', function () {
    $driver = new DbCacheDriver();
    $driver->put('expired', 'old', -1);

    expect($driver->get('expired', 'default'))->toBe('default');
});

test('DbCacheDriver stores forever with 0 TTL', function () {
    $driver = new DbCacheDriver();
    $driver->put('forever', 'value', 0);

    expect($driver->get('forever'))->toBe('value');
});

test('DbCacheDriver stores complex values', function () {
    $driver = new DbCacheDriver();
    $data = ['users' => [['name' => 'Alice'], ['name' => 'Bob']]];
    $driver->put('complex', $data);

    expect($driver->get('complex'))->toBe($data);
});

// --- Via Cache facade ---

test('Cache facade uses db driver when configured', function () {
    Cache::put('facade-test', 'works');

    expect(Cache::get('facade-test'))->toBe('works');
    expect(DB::has('_cache', ['key' => 'facade-test']))->toBeTrue();
});
