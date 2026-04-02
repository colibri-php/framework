<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Auth\Auth;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Exceptions\HttpException;
use Colibri\Middleware\Pipeline;
use Colibri\Middleware\Cors;
use Colibri\Middleware\Csrf;
use Colibri\Middleware\Headers;
use Colibri\Middleware\Json;
use Colibri\Database\Migration;
use Colibri\Http\Request;
use Colibri\Http\Response;
use Colibri\View\View;
use Medoo\Medoo;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    $authRef = new ReflectionClass(Auth::class);
    $authRef->setStaticPropertyValue('currentUser', false);

    $_ENV['DB_PATH'] = ':memory:';
    App::boot(dirname(__DIR__, 2));

    DB::initWith(new Medoo([
        'type' => 'sqlite',
        'database' => ':memory:',
    ]));

    Migration::up();

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION = [];

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    unset($_SERVER['CONTENT_TYPE'], $_SERVER['HTTP_ORIGIN'], $_SERVER['HTTP_X_CSRF_TOKEN']);
});

// --- Auth middleware ---

test('auth middleware redirects when not logged in', function () {
    $middleware = new Colibri\Middleware\Auth();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'));

    expect($response->getStatusCode())->toBe(302);
    expect($response->getHeaders()['Location'])->toBe('/login');
});

test('auth middleware stores intended URL', function () {
    $_SERVER['REQUEST_URI'] = '/admin/dashboard';
    $middleware = new Colibri\Middleware\Auth();
    $request = new Request();

    $middleware->handle($request, fn() => Response::html('OK'));

    expect($_SESSION['_intended_url'])->toBe('/admin/dashboard');
});

test('auth middleware passes when logged in', function () {
    Auth::register(['email' => 'a@test.com', 'password' => 'secret', 'name' => 'A']);
    Auth::attempt('a@test.com', 'secret');

    $middleware = new Colibri\Middleware\Auth();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'));

    expect($response->getBody())->toBe('OK');
});

test('auth middleware with role rejects wrong role', function () {
    Auth::register(['email' => 'a@test.com', 'password' => 'secret', 'name' => 'A', 'role' => 'user']);
    Auth::attempt('a@test.com', 'secret');

    $middleware = new Colibri\Middleware\Auth();
    $request = new Request();

    $middleware->handle($request, fn() => Response::html('OK'), 'admin');
})->throws(HttpException::class);

test('auth middleware with role passes correct role', function () {
    Auth::register(['email' => 'a@test.com', 'password' => 'secret', 'name' => 'A', 'role' => 'admin']);
    Auth::attempt('a@test.com', 'secret');

    $middleware = new Colibri\Middleware\Auth();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'), 'admin');

    expect($response->getBody())->toBe('OK');
});

// --- CSRF middleware ---

test('csrf middleware generates token on GET', function () {
    $middleware = new Csrf();
    $request = new Request();

    $middleware->handle($request, fn() => Response::html('OK'));

    expect($_SESSION['_csrf_token'])->not->toBeEmpty();
});

test('csrf middleware blocks POST without token', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $middleware = new Csrf();
    $request = new Request();

    $middleware->handle($request, fn() => Response::html('OK'));
})->throws(HttpException::class, 'CSRF token mismatch.');

test('csrf middleware passes POST with valid token', function () {
    $_SESSION['_csrf_token'] = 'valid-token';
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_POST = ['_token' => 'valid-token'];
    $middleware = new Csrf();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'));

    expect($response->getBody())->toBe('OK');

    $_POST = [];
});

test('csrf_token helper returns the token', function () {
    $token = csrf_token();

    expect($token)->toBeString();
    expect(strlen($token))->toBe(64);
});

test('csrf_field helper returns hidden input', function () {
    $field = csrf_field();

    expect($field)->toContain('<input type="hidden"');
    expect($field)->toContain('name="_token"');
});

// --- Headers middleware ---

test('headers middleware adds security headers', function () {
    $middleware = new Headers();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'));

    expect($response->getHeaders()['X-Content-Type-Options'])->toBe('nosniff');
    expect($response->getHeaders()['X-Frame-Options'])->toBe('DENY');
    expect($response->getHeaders()['Referrer-Policy'])->toBe('strict-origin-when-cross-origin');
});

// --- Json middleware ---

test('json middleware forces Content-Type', function () {
    $middleware = new Json();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('{"ok":true}'));

    expect($response->getHeaders()['Content-Type'])->toBe('application/json');
});

// --- Cors middleware ---

test('cors middleware adds CORS headers', function () {
    $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
    $middleware = new Cors();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('OK'));

    expect($response->getHeaders()['Access-Control-Allow-Origin'])->toBe('*');
    expect($response->getHeaders()['Access-Control-Allow-Methods'])->toContain('GET');
});

test('cors middleware handles OPTIONS preflight', function () {
    $_SERVER['REQUEST_METHOD'] = 'OPTIONS';
    $_SERVER['HTTP_ORIGIN'] = 'https://example.com';
    $middleware = new Cors();
    $request = new Request();

    $response = $middleware->handle($request, fn() => Response::html('should not reach'));

    expect($response->getStatusCode())->toBe(204);
    expect($response->getHeaders()['Access-Control-Allow-Origin'])->toBe('*');
});

// --- Resolve ---

test('resolve finds built-in middleware by name', function () {
    $middleware = Pipeline::resolve('headers');

    expect($middleware)->toBeInstanceOf(Headers::class);
});

test('resolve throws for unknown middleware', function () {
    Pipeline::resolve('nonexistent');
})->throws(RuntimeException::class, "Middleware 'nonexistent' not found.");
