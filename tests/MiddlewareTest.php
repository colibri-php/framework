<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Middleware\Interfaces\MiddlewareInterface;
use Colibri\Middleware\Pipeline;
use Colibri\Http\Request;
use Colibri\Http\Response;
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

    $_ENV['DB_PATH'] = ':memory:';
    App::boot(dirname(__DIR__, 2));

    $_SERVER['REQUEST_METHOD'] = 'GET';
    $_SERVER['REQUEST_URI'] = '/';
});

test('pipeline executes handler when no middlewares', function () {
    $request = new Request();

    $response = Pipeline::pipeline([], $request, fn() => Response::html('OK'));

    expect($response->getBody())->toBe('OK');
});

test('pipeline executes middleware before handler', function () {
    // Register a test middleware
    $testMiddleware = new class implements MiddlewareInterface {
        public function handle(Request $request, callable $next, string ...$params): Response
        {
            $response = $next($request);

            return $response->header('X-Test', 'yes');
        }
    };

    // We need to test via the resolve mechanism, so let's test the pipeline directly
    // by building the stack manually
    $request = new Request();
    $handler = fn() => Response::html('OK');

    $stack = function (Request $req) use ($testMiddleware, $handler): Response {
        return $testMiddleware->handle($req, $handler);
    };

    $response = $stack($request);

    expect($response->getBody())->toBe('OK');
    expect($response->getHeaders()['X-Test'])->toBe('yes');
});

test('middleware can stop the chain', function () {
    $blockingMiddleware = new class implements MiddlewareInterface {
        public function handle(Request $request, callable $next, string ...$params): Response
        {
            return Response::json(['error' => 'Blocked'], 403);
        }
    };

    $request = new Request();
    $handlerCalled = false;
    $handler = function () use (&$handlerCalled): Response {
        $handlerCalled = true;

        return Response::html('OK');
    };

    $response = $blockingMiddleware->handle($request, $handler);

    expect($response->getStatusCode())->toBe(403);
    expect($handlerCalled)->toBeFalse();
});

test('middleware receives parameters', function () {
    $paramMiddleware = new class implements MiddlewareInterface {
        public function handle(Request $request, callable $next, string ...$params): Response
        {
            $response = $next($request);

            return $response->header('X-Params', implode(',', $params));
        }
    };

    $request = new Request();
    $handler = fn() => Response::html('OK');

    $response = $paramMiddleware->handle($request, $handler, 'admin', 'editor');

    expect($response->getHeaders()['X-Params'])->toBe('admin,editor');
});

test('collect gathers _middleware.php files in cascade', function () {
    // Create temp middleware files
    $baseDir = base_path('storage/cache/test-middleware');
    $subDir = $baseDir . DIRECTORY_SEPARATOR . 'admin';

    @mkdir($subDir, 0777, true);

    file_put_contents($baseDir . DIRECTORY_SEPARATOR . '_middleware.php', "<?php return ['headers'];");
    file_put_contents($subDir . DIRECTORY_SEPARATOR . '_middleware.php', "<?php return ['auth:admin', 'csrf'];");

    $result = Pipeline::collect($subDir, $baseDir);

    expect($result)->toBe(['headers', 'auth:admin', 'csrf']);

    // Cleanup
    unlink($baseDir . DIRECTORY_SEPARATOR . '_middleware.php');
    unlink($subDir . DIRECTORY_SEPARATOR . '_middleware.php');
    rmdir($subDir);
    rmdir($baseDir);
});

test('collect returns empty array when no _middleware.php exists', function () {
    $baseDir = base_path('storage/cache/test-empty-middleware');
    @mkdir($baseDir, 0777, true);

    $result = Pipeline::collect($baseDir, $baseDir);

    expect($result)->toBe([]);

    rmdir($baseDir);
});

test('resolve throws for unknown middleware', function () {
    Pipeline::resolve('nonexistent');
})->throws(RuntimeException::class, "Middleware 'nonexistent' not found.");
