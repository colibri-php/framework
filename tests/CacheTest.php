<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Cache\Cache;
use Colibri\Config;
use Colibri\Database\DB;
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

    $cacheRef = new ReflectionClass(Cache::class);
    $cacheRef->setStaticPropertyValue('instance', null);

    App::boot(dirname(__DIR__, 2));

    Cache::clear();
});

afterEach(function () {
    Cache::clear();
});

test('put and get store and retrieve a value', function () {
    Cache::put('name', 'Colibri', 60);

    expect(Cache::get('name'))->toBe('Colibri');
});

test('get returns default when key is missing', function () {
    expect(Cache::get('missing', 'default'))->toBe('default');
});

test('get returns null by default when key is missing', function () {
    expect(Cache::get('missing'))->toBeNull();
});

test('has returns true for existing key', function () {
    Cache::put('exists', true);

    expect(Cache::has('exists'))->toBeTrue();
});

test('has returns false for missing key', function () {
    expect(Cache::has('missing'))->toBeFalse();
});

test('forget removes a key', function () {
    Cache::put('temp', 'value');
    Cache::forget('temp');

    expect(Cache::has('temp'))->toBeFalse();
});

test('forget returns false for missing key', function () {
    expect(Cache::forget('missing'))->toBeFalse();
});

test('clear removes all cached values', function () {
    Cache::put('a', 1);
    Cache::put('b', 2);
    Cache::put('c', 3);

    Cache::clear();

    expect(Cache::has('a'))->toBeFalse();
    expect(Cache::has('b'))->toBeFalse();
    expect(Cache::has('c'))->toBeFalse();
});

test('expired cache returns default', function () {
    // Put with -1 minutes to simulate expired
    Cache::put('expired', 'old', -1);

    expect(Cache::get('expired', 'default'))->toBe('default');
});

test('has respects TTL', function () {
    Cache::put('expired', 'old', -1);

    expect(Cache::has('expired'))->toBeFalse();
});

test('put with 0 TTL stores forever', function () {
    Cache::put('forever', 'value', 0);

    expect(Cache::get('forever'))->toBe('value');
});

test('cache stores complex values', function () {
    $data = ['users' => [['name' => 'Alice'], ['name' => 'Bob']]];
    Cache::put('complex', $data);

    expect(Cache::get('complex'))->toBe($data);
});
