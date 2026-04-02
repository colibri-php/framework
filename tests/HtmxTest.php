<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\View\Lang;
use Colibri\Http\Request;
use Colibri\Http\Response;
use Colibri\Http\Router;
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

    $langRef = new ReflectionClass(Lang::class);
    $langRef->setStaticPropertyValue('currentLocale', null);
    $langRef->setStaticPropertyValue('translations', []);

    App::boot(dirname(__DIR__, 2));

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    unset($_SERVER['HTTP_HX_REQUEST'], $_SERVER['HTTP_HX_TARGET'], $_SERVER['HTTP_HX_TRIGGER'], $_SERVER['HTTP_HX_BOOSTED']);
});

// --- Request detection ---

test('isHtmx returns true when HX-Request header is set', function () {
    $_SERVER['HTTP_HX_REQUEST'] = 'true';
    $request = new Request();

    expect($request->isHtmx())->toBeTrue();
});

test('isHtmx returns false for normal request', function () {
    $request = new Request();

    expect($request->isHtmx())->toBeFalse();
});

test('htmxTarget returns the target element', function () {
    $_SERVER['HTTP_HX_TARGET'] = 'content';
    $request = new Request();

    expect($request->htmxTarget())->toBe('content');
});

test('htmxTrigger returns the trigger element', function () {
    $_SERVER['HTTP_HX_TRIGGER'] = 'search-input';
    $request = new Request();

    expect($request->htmxTrigger())->toBe('search-input');
});

test('htmxBoosted detects boosted requests', function () {
    $_SERVER['HTTP_HX_BOOSTED'] = 'true';
    $request = new Request();

    expect($request->htmxBoosted())->toBeTrue();
});

// --- Response helpers ---

test('htmxRedirect sets HX-Redirect header', function () {
    $response = Response::htmxRedirect('/dashboard');

    expect($response->getHeaders()['HX-Redirect'])->toBe('/dashboard');
});

test('htmxRefresh sets HX-Refresh header', function () {
    $response = Response::htmxRefresh();

    expect($response->getHeaders()['HX-Refresh'])->toBe('true');
});

test('htmxTrigger sets HX-Trigger header', function () {
    $response = Response::htmxSwap('<p>done</p>')->htmxTrigger('formSaved');

    expect($response->getHeaders()['HX-Trigger'])->toBe('formSaved');
});

test('htmxSwap returns HTML fragment', function () {
    $response = Response::htmxSwap('<p>Hello</p>');

    expect($response->getBody())->toBe('<p>Hello</p>');
    expect($response->getStatusCode())->toBe(200);
});

// --- Partial rendering ---

test('HTMX request renders without layout', function () {
    $_SERVER['HTTP_HX_REQUEST'] = 'true';
    $request = new Request();

    $response = Router::dispatch($request, App::getInstance());

    $body = $response->getBody();
    expect($body)->not->toContain('<!DOCTYPE html>');
    expect($body)->toContain('Welcome to Colibri');
});

test('normal request renders with layout', function () {
    $request = new Request();

    $response = Router::dispatch($request, App::getInstance());

    $body = $response->getBody();
    expect($body)->toContain('<!DOCTYPE html>');
    expect($body)->toContain('Welcome to Colibri');
});
