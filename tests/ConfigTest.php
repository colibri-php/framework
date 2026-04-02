<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;

beforeEach(function () {
    // Reset singletons between tests
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    // Boot app with project root so it loads real config files
    App::boot(dirname(__DIR__, 2));
});

test('get returns a top-level config value', function () {
    expect(Config::get('app.name'))->toBe('Colibri');
});

test('get returns a nested config value', function () {
    expect(Config::get('app.log.channel'))->toBe('daily');
});

test('get returns default when key is missing', function () {
    expect(Config::get('app.nonexistent', 'fallback'))->toBe('fallback');
});

test('get returns default when file does not exist', function () {
    expect(Config::get('nope.key', 'default'))->toBe('default');
});

test('get loads from different config files', function () {
    expect(Config::get('database.driver'))->toBe('sqlite');
    expect(Config::get('cors.max_age'))->toBe(86400);
});

test('lazy loading only loads file on first access', function () {
    $configRef = new ReflectionClass(Config::class);
    $instance = $configRef->getStaticPropertyValue('instance');

    // app.php is already loaded by boot (timezone), but database should not be
    expect($instance->loaded)->not->toHaveKey('database');

    Config::get('database.driver');

    expect($instance->loaded)->toHaveKey('database');
    expect($instance->loaded)->not->toHaveKey('cors');
});

test('env variables are loaded', function () {
    expect($_ENV['APP_NAME'])->toBe('Colibri');
});

test('throws if not initialized', function () {
    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    Config::get('app.name');
})->throws(RuntimeException::class, 'Config has not been initialized.');
