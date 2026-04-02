<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\View\View;
use Colibri\View\Vite;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    $viteRef = new ReflectionClass(Vite::class);
    $viteRef->setStaticPropertyValue('manifest', null);
    $viteRef->setStaticPropertyValue('devServerRunning', null);

    App::boot(dirname(__DIR__, 2));
});

test('asset returns empty when no manifest and no dev server', function () {
    // Force dev server off
    $ref = new ReflectionClass(Vite::class);
    $ref->setStaticPropertyValue('devServerRunning', false);

    expect(Vite::asset('resources/js/app.js'))->toBe('');
});

test('asset reads from manifest in prod', function () {
    $ref = new ReflectionClass(Vite::class);
    $ref->setStaticPropertyValue('devServerRunning', false);
    $ref->setStaticPropertyValue('manifest', [
        'resources/js/app.js' => [
            'file' => 'assets/app-abc123.js',
            'css' => ['assets/app-def456.css'],
        ],
        'resources/css/app.css' => [
            'file' => 'assets/app-ghi789.css',
        ],
    ]);

    $jsTag = Vite::asset('resources/js/app.js');
    expect($jsTag)->toContain('<script type="module" src="/build/assets/app-abc123.js">');
    expect($jsTag)->toContain('<link rel="stylesheet" href="/build/assets/app-def456.css">');

    $cssTag = Vite::asset('resources/css/app.css');
    expect($cssTag)->toContain('<link rel="stylesheet" href="/build/assets/app-ghi789.css">');
});

test('asset returns empty for unknown entry', function () {
    $ref = new ReflectionClass(Vite::class);
    $ref->setStaticPropertyValue('devServerRunning', false);
    $ref->setStaticPropertyValue('manifest', []);

    expect(Vite::asset('nonexistent.js'))->toBe('');
});

test('asset generates dev tags when dev server is running', function () {
    $ref = new ReflectionClass(Vite::class);
    $ref->setStaticPropertyValue('devServerRunning', true);

    $tags = Vite::asset('resources/js/app.js');

    expect($tags)->toContain('/@vite/client');
    expect($tags)->toContain('resources/js/app.js');
    expect($tags)->toContain('type="module"');
});

test('dev tags use css link for css entries', function () {
    $ref = new ReflectionClass(Vite::class);
    $ref->setStaticPropertyValue('devServerRunning', true);

    $tags = Vite::asset('resources/css/app.css');

    expect($tags)->toContain('<link rel="stylesheet"');
    expect($tags)->toContain('resources/css/app.css');
});
