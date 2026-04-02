<?php

declare(strict_types=1);

use Colibri\Http\Request;

beforeEach(function () {
    $_GET = [];
    $_POST = [];
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    unset($_SERVER['CONTENT_TYPE'], $_SERVER['CONTENT_LENGTH'], $_SERVER['HTTP_ACCEPT']);
});

test('method returns the HTTP method in uppercase', function () {
    $_SERVER['REQUEST_METHOD'] = 'post';
    $request = new Request();

    expect($request->method)->toBe('POST');
});

test('path returns the path without query string', function () {
    $_SERVER['REQUEST_URI'] = '/users?page=2';
    $request = new Request();

    expect($request->path)->toBe('/users');
});

test('path returns / for root', function () {
    $_SERVER['REQUEST_URI'] = '/';
    $request = new Request();

    expect($request->path)->toBe('/');
});

test('query returns all query params', function () {
    $_GET = ['page' => '2', 'sort' => 'name'];
    $request = new Request();

    expect($request->query())->toBe(['page' => '2', 'sort' => 'name']);
});

test('query returns a single param with default', function () {
    $_GET = ['page' => '2'];
    $request = new Request();

    expect($request->query('page'))->toBe('2');
    expect($request->query('missing', 'default'))->toBe('default');
});

test('body returns POST data', function () {
    $_POST = ['name' => 'Richard'];
    $request = new Request();

    expect($request->body())->toBe(['name' => 'Richard']);
});

test('input reads from body then query', function () {
    $_POST = ['name' => 'Richard'];
    $_GET = ['page' => '2'];
    $request = new Request();

    expect($request->input('name'))->toBe('Richard');
    expect($request->input('page'))->toBe('2');
    expect($request->input('missing', 'default'))->toBe('default');
});

test('header reads request headers', function () {
    $_SERVER['HTTP_ACCEPT'] = 'text/html';
    $request = new Request();

    expect($request->header('Accept'))->toBe('text/html');
    expect($request->header('Missing', 'fallback'))->toBe('fallback');
});

test('header reads Content-Type from SERVER', function () {
    $_SERVER['CONTENT_TYPE'] = 'application/json';
    $request = new Request();

    expect($request->header('Content-Type'))->toBe('application/json');
});

test('isJson detects JSON content type', function () {
    $_SERVER['CONTENT_TYPE'] = 'application/json; charset=utf-8';
    $request = new Request();

    expect($request->isJson())->toBeTrue();
});

test('isJson returns false for non-JSON', function () {
    $request = new Request();

    expect($request->isJson())->toBeFalse();
});

// --- Validation ---

test('validate returns null when valid', function () {
    $_POST = ['email' => 'alice@test.com', 'name' => 'Alice'];
    $request = new Request();

    $errors = $request->validate([
        'email' => ['required', 'email'],
        'name' => ['required'],
    ]);

    expect($errors)->toBeNull();

    $_POST = [];
});

test('validate returns errors when invalid', function () {
    $_POST = ['email' => 'not-an-email'];
    $request = new Request();

    $errors = $request->validate([
        'email' => ['required', 'email'],
        'name' => ['required'],
    ]);

    expect($errors)->not->toBeNull();
    expect($errors)->toHaveKey('name');
    expect($errors)->toHaveKey('email');

    $_POST = [];
});

test('validate supports parameterized rules', function () {
    $_POST = ['name' => 'AB'];
    $request = new Request();

    $errors = $request->validate([
        'name' => ['required', ['lengthMin', 3]],
    ]);

    expect($errors)->not->toBeNull();
    expect($errors)->toHaveKey('name');

    $_POST = [];
});

test('validate passes parameterized rules when valid', function () {
    $_POST = ['name' => 'Alice'];
    $request = new Request();

    $errors = $request->validate([
        'name' => ['required', ['lengthMin', 3]],
    ]);

    expect($errors)->toBeNull();

    $_POST = [];
});
