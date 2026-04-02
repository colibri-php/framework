<?php

declare(strict_types=1);

use Colibri\App;

beforeEach(function () {
    // Reset singleton between tests
    $ref = new ReflectionClass(App::class);
    $ref->setStaticPropertyValue('instance', null);
});

test('boot returns an App instance', function () {
    $app = App::boot(__DIR__);
    expect($app)->toBeInstanceOf(App::class);
});

test('getInstance returns the same instance', function () {
    $app = App::boot(__DIR__);
    expect(App::getInstance())->toBe($app);
});

test('base_path returns the base path', function () {
    App::boot('/project');
    expect(base_path())->toBe('/project');
});

test('base_path resolves a sub-path', function () {
    App::boot('/project');
    expect(base_path('routes/web'))->toBe('/project' . DIRECTORY_SEPARATOR . 'routes' . DIRECTORY_SEPARATOR . 'web');
});

test('base_path throws if app is not booted', function () {
    base_path();
})->throws(RuntimeException::class, 'App has not been booted.');
