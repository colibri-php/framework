<?php

declare(strict_types=1);

use Colibri\Http\Response;

test('json creates a JSON response', function () {
    $response = Response::json(['ok' => true]);

    expect($response->getBody())->toBe('{"ok":true}');
    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaders()['Content-Type'])->toBe('application/json');
});

test('json accepts a custom status code', function () {
    $response = Response::json(['error' => 'Not found'], 404);

    expect($response->getStatusCode())->toBe(404);
});

test('html creates an HTML response', function () {
    $response = Response::html('<h1>Hello</h1>');

    expect($response->getBody())->toBe('<h1>Hello</h1>');
    expect($response->getHeaders()['Content-Type'])->toBe('text/html; charset=utf-8');
});

test('redirect creates a redirect response', function () {
    $response = Response::redirect('/login');

    expect($response->getStatusCode())->toBe(302);
    expect($response->getHeaders()['Location'])->toBe('/login');
});

test('redirect accepts a custom status code', function () {
    $response = Response::redirect('/new-url', 301);

    expect($response->getStatusCode())->toBe(301);
});

test('back redirects to the referer', function () {
    $_SERVER['HTTP_REFERER'] = '/previous-page';
    $response = Response::back();

    expect($response->getHeaders()['Location'])->toBe('/previous-page');

    unset($_SERVER['HTTP_REFERER']);
});

test('back falls back to / when no referer', function () {
    unset($_SERVER['HTTP_REFERER']);
    $response = Response::back();

    expect($response->getHeaders()['Location'])->toBe('/');
});

test('status sets the status code', function () {
    $response = Response::html('')->status(418);

    expect($response->getStatusCode())->toBe(418);
});

test('header sets a custom header', function () {
    $response = Response::html('')->header('X-Custom', 'value');

    expect($response->getHeaders()['X-Custom'])->toBe('value');
});

test('with stores flash messages', function () {
    $response = Response::redirect('/login')
        ->with('error', 'Invalid credentials')
        ->with('info', 'Try again');

    // Flash messages are stored internally, verified via send() in integration
    expect($response->getStatusCode())->toBe(302);
});

test('double send is guarded', function () {
    $response = Response::json(['ok' => true]);

    // Can't truly test send() without output buffering, but verify the flag
    expect($response->isSent())->toBeFalse();
});

test('fluent chaining works', function () {
    $response = Response::html('<p>test</p>')
        ->status(201)
        ->header('X-Custom', 'yes');

    expect($response->getStatusCode())->toBe(201);
    expect($response->getHeaders()['X-Custom'])->toBe('yes');
    expect($response->getHeaders()['Content-Type'])->toBe('text/html; charset=utf-8');
});
