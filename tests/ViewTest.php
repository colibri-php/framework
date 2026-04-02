<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\View\Page;
use Colibri\View\View;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    App::boot(dirname(__DIR__, 2));

    // Create temp test templates directory
    $this->tempDir = base_path('storage/cache/test-templates');
    if (! is_dir($this->tempDir)) {
        mkdir($this->tempDir, 0777, true);
    }
});

afterEach(function () {
    // Clean up temp templates
    if (isset($this->tempDir) && is_dir($this->tempDir)) {
        $files = glob($this->tempDir . '/*');
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->tempDir);
    }
});

test('render produces HTML with page title in layout', function () {
    $template = $this->tempDir . '/title-test.latte';
    file_put_contents($template, '{block content}<h1>{$page->title}</h1>{/block}');

    $page = new Page();
    $page->title = 'Hello World';

    $html = View::render($template, [], $page);

    expect($html)->toContain('<title>Hello World | Colibri</title>');
    expect($html)->toContain('<h1>Hello World</h1>');
});

test('page title falls back to app name', function () {
    $template = $this->tempDir . '/fallback-test.latte';
    file_put_contents($template, '{block content}test{/block}');

    $html = View::render($template);

    expect($html)->toContain('<title>Colibri</title>');
});

test('view helper function works', function () {
    $template = $this->tempDir . '/helper-test.latte';
    file_put_contents($template, '{block content}Hello {$name}{/block}');

    $html = view($template, ['name' => 'Richard']);

    expect($html)->toContain('Hello Richard');
});

test('auto-escaping is active', function () {
    $template = $this->tempDir . '/xss-test.latte';
    file_put_contents($template, '{block content}{$input}{/block}');

    $html = View::render($template, ['input' => '<script>alert("xss")</script>']);

    expect($html)->not->toContain('<script>alert');
    expect($html)->toContain('&lt;script&gt;');
});

test('auto-layout wraps template in default layout', function () {
    $template = $this->tempDir . '/auto-layout-test.latte';
    file_put_contents($template, '{block content}<p>Hello</p>{/block}');

    $page = new Page();
    $page->title = 'Auto Layout';

    $html = View::render($template, [], $page);

    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('<title>Auto Layout | Colibri</title>');
    expect($html)->toContain('<p>Hello</p>');
});

test('{layout} native tag still works with absolute path', function () {
    $template = $this->tempDir . '/layout-native-test.latte';
    $layoutPath = str_replace('\\', '/', base_path('templates/layouts/default.latte'));
    file_put_contents($template, '{layout "' . $layoutPath . '"}
{block content}<p>Native</p>{/block}');

    $page = new Page();
    $page->title = 'Native Layout';

    $html = View::render($template, [], $page);

    expect($html)->toContain('<!DOCTYPE html>');
    expect($html)->toContain('<p>Native</p>');
});

test('page object sets description', function () {
    $page = new Page();
    $page->description = 'My description';

    expect($page->description)->toBe('My description');
});

test('page object sets custom layout', function () {
    $page = new Page();
    $page->layout = 'admin';

    expect($page->layout)->toBe('admin');
});
