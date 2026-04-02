<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\View\Assets;
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

    App::boot(dirname(__DIR__, 2));
    Assets::reset();

    // Create test directory structure
    $this->baseDir = base_path('storage/cache/test-assets');
    $this->subDir = $this->baseDir . DIRECTORY_SEPARATOR . 'admin';
    mkdir($this->subDir, 0777, true);
});

afterEach(function () {
    foreach ([$this->subDir, $this->baseDir] as $dir) {
        $files = glob($dir . '/*') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        if (is_dir($dir)) {
            rmdir($dir);
        }
    }
});

test('renderStyles returns empty when no assets', function () {
    expect(Assets::renderStyles())->toBe('');
});

test('collectCascading collects styles from base dir', function () {
    file_put_contents($this->baseDir . '/_styles.css', 'body { margin: 0; }');

    Assets::collectCascading($this->baseDir, $this->baseDir);

    expect(Assets::renderStyles())->toContain('body { margin: 0; }');
    expect(Assets::renderStyles())->toContain('<style>');
});

test('collectCascading cascades parent then child', function () {
    file_put_contents($this->baseDir . '/_styles.css', 'body { margin: 0; }');
    file_put_contents($this->subDir . '/_styles.css', '.admin { color: red; }');

    Assets::collectCascading($this->subDir, $this->baseDir);

    $output = Assets::renderStyles();
    $bodyPos = strpos($output, 'body { margin: 0; }');
    $adminPos = strpos($output, '.admin { color: red; }');
    expect($bodyPos)->toBeLessThan($adminPos);
});

test('collectCascading collects scripts', function () {
    file_put_contents($this->baseDir . '/_scripts.js', 'console.log("global");');
    file_put_contents($this->subDir . '/_scripts.js', 'console.log("admin");');

    Assets::collectCascading($this->subDir, $this->baseDir);

    $output = Assets::renderScripts();
    expect($output)->toContain('console.log("global")');
    expect($output)->toContain('console.log("admin")');
});

test('pushCssFile adds a link tag', function () {
    Assets::pushCssFile('admin.css');

    expect(Assets::renderStyles())->toContain('<link rel="stylesheet" href="/assets/admin.css">');
});

test('pushCssFile deduplicates', function () {
    Assets::pushCssFile('admin.css');
    Assets::pushCssFile('admin.css');

    $output = Assets::renderStyles();
    expect(substr_count($output, 'admin.css'))->toBe(1);
});

test('pushJsFile adds a script tag', function () {
    Assets::pushJsFile('chart.js');

    expect(Assets::renderScripts())->toContain('<script src="/assets/chart.js"></script>');
});

test('pushJsFile with defer', function () {
    Assets::pushJsFile('chart.js', defer: true);

    expect(Assets::renderScripts())->toContain('defer');
});

test('pushCss adds inline style', function () {
    Assets::pushCss('.custom { color: blue; }');

    expect(Assets::renderStyles())->toContain('<style>.custom { color: blue; }</style>');
});

test('pushJs adds inline script', function () {
    Assets::pushJs('alert("hi");');

    expect(Assets::renderScripts())->toContain('<script>alert("hi");</script>');
});

test('reset clears all stacks', function () {
    Assets::pushCss('.test {}');
    Assets::pushJs('test();');
    Assets::reset();

    expect(Assets::renderStyles())->toBe('');
    expect(Assets::renderScripts())->toBe('');
});
