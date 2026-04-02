<?php

declare(strict_types=1);

use Colibri\Support\Str;

test('slug generates a url-friendly slug', function () {
    expect(Str::slug('Mon Article'))->toBe('mon-article');
});

test('slug handles accented characters', function () {
    expect(Str::slug('Café résumé'))->toBe('cafe-resume');
});

test('slug handles special characters', function () {
    expect(Str::slug('Hello, World! #2024'))->toBe('hello-world-2024');
});

test('slug accepts custom separator', function () {
    expect(Str::slug('Hello World', '_'))->toBe('hello_world');
});

test('contains detects substring', function () {
    expect(Str::contains('Hello World', 'World'))->toBeTrue();
    expect(Str::contains('Hello World', 'world'))->toBeFalse();
});

test('startsWith checks prefix', function () {
    expect(Str::startsWith('Hello World', 'Hello'))->toBeTrue();
    expect(Str::startsWith('Hello World', 'World'))->toBeFalse();
});

test('endsWith checks suffix', function () {
    expect(Str::endsWith('Hello World', 'World'))->toBeTrue();
    expect(Str::endsWith('Hello World', 'Hello'))->toBeFalse();
});

test('limit truncates with ellipsis', function () {
    expect(Str::limit('Hello World', 5))->toBe('Hello...');
});

test('limit returns full string if short enough', function () {
    expect(Str::limit('Hi', 10))->toBe('Hi');
});

test('limit accepts custom ending', function () {
    expect(Str::limit('Hello World', 5, '…'))->toBe('Hello…');
});

test('limit handles multibyte characters', function () {
    expect(Str::limit('Héllo Wörld', 5))->toBe('Héllo...');
});

test('random generates a string of expected length', function () {
    $result = Str::random(32);
    expect(strlen($result))->toBeGreaterThanOrEqual(32);
});

test('random generates unique values', function () {
    expect(Str::random())->not->toBe(Str::random());
});

test('lower converts to lowercase (multibyte)', function () {
    expect(Str::lower('HÉLLO'))->toBe('héllo');
});

test('upper converts to uppercase (multibyte)', function () {
    expect(Str::upper('héllo'))->toBe('HÉLLO');
});
