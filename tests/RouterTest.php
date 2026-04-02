<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Http\Request;
use Colibri\Http\Router;
use Colibri\View\View;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    $this->app = App::boot(dirname(__DIR__, 2));
});

test('GET / resolves to routes/web/index.latte', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getBody())->toContain('Welcome to Colibri');
});

test('GET /api/users resolves to routes/api/users/index.php', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getHeaders()['Content-Type'])->toBe('application/json');

    $data = json_decode($response->getBody(), true);
    expect($data)->toBeArray();
    expect($data[0]['name'])->toBe('Alice');
});

test('GET /nonexistent throws HttpException 404', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/nonexistent';
    $request = new Request();

    Router::dispatch($request, $this->app);
})->throws(Colibri\Exceptions\HttpException::class, 'Not Found');

test('GET /api/nonexistent returns JSON 404', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/nonexistent';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(404);
    expect($response->getHeaders()['Content-Type'])->toBe('application/json');

    $data = json_decode($response->getBody(), true);
    expect($data['error'])->toBe('Not Found');
});

test('latte page renders with $page title', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getBody())->toContain('<title>Homepage | Colibri</title>');
});

test('API route returns Content-Type application/json', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getHeaders()['Content-Type'])->toBe('application/json');
});

test('dynamic segment captures param', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users/1';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getBody(), true);
    expect($data['name'])->toBe('Alice');
});

test('static route has priority over dynamic', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users/me';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    $data = json_decode($response->getBody(), true);
    expect($data['self'])->toBeTrue();
});

test('dynamic segment returns 404 for unknown user', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/users/999';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(404);
});

test('method handler GET is called on GET', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/api/posts';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(200);
    $data = json_decode($response->getBody(), true);
    expect($data[0]['title'])->toBe('First post');
});

test('method handler POST is called on POST', function () {
    $_SERVER['REQUEST_METHOD'] = 'POST';
    $_SERVER['REQUEST_URI'] = '/api/posts';
    $_POST = ['title' => 'New post'];
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    $data = json_decode($response->getBody(), true);
    expect($data['created'])->toBeTrue();
    expect($data['title'])->toBe('New post');

    $_POST = [];
});

test('405 returned for unsupported method on API', function () {
    $_SERVER['REQUEST_METHOD'] = 'DELETE';
    $_SERVER['REQUEST_URI'] = '/api/posts';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(405);
    $data = json_decode($response->getBody(), true);
    expect($data['error'])->toBe('Method Not Allowed');
});

test('page without method handlers still works as GET', function () {
    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
    $request = new Request();

    $response = Router::dispatch($request, $this->app);

    expect($response->getStatusCode())->toBe(200);
    expect($response->getBody())->toContain('Welcome to Colibri');
});
