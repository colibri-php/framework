<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Storage\Data;
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

    // Create test data directory
    $this->testDir = base_path('data/test-collection');
    if (! is_dir($this->testDir)) {
        mkdir($this->testDir, 0777, true);
    }
});

afterEach(function () {
    // Clean up test data
    if (isset($this->testDir) && is_dir($this->testDir)) {
        $files = glob($this->testDir . '/*.json') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->testDir);
    }
});

test('put creates a JSON file', function () {
    Data::put('test-collection/article', [
        'title' => 'Hello',
        'status' => 'published',
    ]);

    expect(file_exists($this->testDir . '/article.json'))->toBeTrue();

    $content = json_decode(file_get_contents($this->testDir . '/article.json'), true);
    expect($content['title'])->toBe('Hello');
});

test('get reads a JSON file', function () {
    Data::put('test-collection/article', ['title' => 'Hello']);

    $data = Data::get('test-collection/article');

    expect($data)->not->toBeNull();
    expect($data['title'])->toBe('Hello');
});

test('get returns null for missing file', function () {
    expect(Data::get('test-collection/nonexistent'))->toBeNull();
});

test('exists checks file existence', function () {
    Data::put('test-collection/article', ['title' => 'Hello']);

    expect(Data::exists('test-collection/article'))->toBeTrue();
    expect(Data::exists('test-collection/missing'))->toBeFalse();
});

test('delete removes a file', function () {
    Data::put('test-collection/article', ['title' => 'Hello']);

    expect(Data::delete('test-collection/article'))->toBeTrue();
    expect(Data::exists('test-collection/article'))->toBeFalse();
});

test('delete returns false for missing file', function () {
    expect(Data::delete('test-collection/missing'))->toBeFalse();
});

test('all returns all items in a collection', function () {
    Data::put('test-collection/first', ['title' => 'First', 'order' => 1]);
    Data::put('test-collection/second', ['title' => 'Second', 'order' => 2]);

    $items = Data::all('test-collection');

    expect($items)->toHaveCount(2);
});

test('all adds _slug to each item', function () {
    Data::put('test-collection/my-post', ['title' => 'My Post']);

    $items = Data::all('test-collection');

    expect($items[0]['_slug'])->toBe('my-post');
});

test('all sorts by field ascending', function () {
    Data::put('test-collection/b', ['title' => 'B', 'order' => 2]);
    Data::put('test-collection/a', ['title' => 'A', 'order' => 1]);

    $items = Data::all('test-collection', sort: 'order');

    expect($items[0]['title'])->toBe('A');
    expect($items[1]['title'])->toBe('B');
});

test('all sorts descending', function () {
    Data::put('test-collection/a', ['title' => 'A', 'order' => 1]);
    Data::put('test-collection/b', ['title' => 'B', 'order' => 2]);

    $items = Data::all('test-collection', sort: 'order', order: 'desc');

    expect($items[0]['title'])->toBe('B');
    expect($items[1]['title'])->toBe('A');
});

test('all returns empty for missing collection', function () {
    expect(Data::all('nonexistent'))->toBe([]);
});

test('where filters by conditions', function () {
    Data::put('test-collection/draft', ['title' => 'Draft', 'status' => 'draft']);
    Data::put('test-collection/published', ['title' => 'Published', 'status' => 'published']);

    $results = Data::where('test-collection', ['status' => 'published']);

    expect($results)->toHaveCount(1);
    expect($results[0]['title'])->toBe('Published');
});

test('where returns empty when no match', function () {
    Data::put('test-collection/draft', ['title' => 'Draft', 'status' => 'draft']);

    $results = Data::where('test-collection', ['status' => 'archived']);

    expect($results)->toBe([]);
});
