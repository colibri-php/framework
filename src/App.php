<?php

declare(strict_types=1);

namespace Colibri;

use Colibri\Exceptions\HttpException;
use Colibri\Support\Str;
use Colibri\Http\Request;
use Colibri\Http\Response;
use Colibri\Http\Router;
use Colibri\Support\Log;

class App
{
    private static ?self $instance = null;

    private function __construct(
        private(set) string $basePath,
    ) {}

    /**
     * Boot the application with the given base path.
     */
    public static function boot(string $basePath): self
    {
        if (self::$instance !== null) {
            return self::$instance;
        }

        self::$instance = new self(rtrim($basePath, '/\\'));

        Config::init(self::$instance->basePath);

        date_default_timezone_set(Config::get('app.timezone', 'UTC'));

        return self::$instance;
    }

    /**
     * Get the singleton instance.
     */
    public static function getInstance(): ?self
    {
        return self::$instance;
    }

    /**
     * Resolve a path relative to the project root.
     */
    public function basePath(string $path = ''): string
    {
        if ($path === '') {
            return $this->basePath;
        }

        return $this->basePath . DIRECTORY_SEPARATOR . ltrim(str_replace('/', DIRECTORY_SEPARATOR, $path), DIRECTORY_SEPARATOR);
    }

    /**
     * Handle the incoming request and send a response.
     */
    public function run(): void
    {
        $request = new Request();

        // Check maintenance mode
        $maintenanceFile = $this->basePath('storage/maintenance');
        if (file_exists($maintenanceFile)) {
            $data = json_decode(file_get_contents($maintenanceFile) ?: '{}', true);

            // Allow bypass via secret
            $secret = $data['secret'] ?? null;
            $querySecret = $request->query('secret');

            if ($secret !== null && $querySecret === $secret) {
                // Set cookie and let through
                setcookie('colibri_maintenance', $secret, time() + 3600, '/');
            } elseif ($secret === null || ($_COOKIE['colibri_maintenance'] ?? '') !== $secret) {
                $response = Response::html(self::renderErrorPage(503, 'Service Unavailable'), 503);
                $response->send();

                return;
            }
        }

        try {
            $response = Router::dispatch($request, $this);
        } catch (HttpException $e) {
            $response = self::handleHttpException($e, $request);
        } catch (\Throwable $e) {
            Log::error($e->getMessage(), [
                'file' => $e->getFile(),
                'line' => $e->getLine(),
                'trace' => $e->getTraceAsString(),
            ]);

            $response = self::handleException($e, $request);
        }

        $response->send();
    }

    /**
     * Handle an HTTP exception (404, 403, 405, etc.).
     */
    private static function handleHttpException(HttpException $e, Request $request): Response
    {
        $isApi = $request->path === '/api' || Str::startsWith($request->path, '/api/');

        if ($isApi) {
            return Response::json(['error' => $e->getMessage()], $e->statusCode);
        }

        return Response::html(self::renderErrorPage($e->statusCode, $e->getMessage()), $e->statusCode);
    }

    /**
     * Handle an unexpected exception.
     */
    private static function handleException(\Throwable $e, Request $request): Response
    {
        $isApi = $request->path === '/api' || Str::startsWith($request->path, '/api/');
        $debug = Config::get('app.debug', false);

        if ($isApi) {
            $data = ['error' => 'Internal Server Error'];
            if ($debug) {
                $data['message'] = $e->getMessage();
                $data['file'] = $e->getFile() . ':' . $e->getLine();
                $data['trace'] = explode("\n", $e->getTraceAsString());
            }

            return Response::json($data, 500);
        }

        // Use Whoops in debug mode if available
        if ($debug && class_exists(\Whoops\Run::class)) {
            return Response::html(self::renderWhoops($e), 500);
        }

        return Response::html(self::renderErrorPage(500), 500);
    }

    /**
     * Render an error page using Latte template if available, fallback to inline HTML.
     */
    private static function renderErrorPage(int $code, ?string $message = null): string
    {
        // Try Latte template first
        $templatePath = base_path("templates/errors/$code.latte");
        if (file_exists($templatePath)) {
            try {
                return View\View::engine()->renderToString($templatePath, [
                    'code' => $code,
                    'message' => $message,
                ]);
            } catch (\Throwable) {
                // Fallback to inline if template rendering fails
            }
        }

        // Inline fallback
        $title = match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            503 => 'Service Unavailable',
            default => 'Error',
        };

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= "<title>$code $title</title>";
        $html .= '<style>body{font-family:-apple-system,sans-serif;max-width:640px;margin:4rem auto;padding:0 1rem;color:#333;text-align:center}';
        $html .= 'h1{font-size:4rem;color:#ccc;margin-bottom:0}p{color:#888}a{color:#2d6a4f}</style>';
        $html .= "</head><body><h1>$code</h1><p>$title</p>";
        if ($message !== null) {
            $html .= '<p>' . htmlspecialchars($message) . '</p>';
        }
        $html .= '<a href="/">← Back to home</a></body></html>';

        return $html;
    }

    /**
     * Render an exception using Whoops (debug mode only).
     */
    private static function renderWhoops(\Throwable $e): string
    {
        $whoops = new \Whoops\Run();
        $handler = new \Whoops\Handler\PrettyPageHandler();
        $handler->setPageTitle('Colibri Error');
        $whoops->pushHandler($handler);
        $whoops->allowQuit(false);
        $whoops->writeToOutput(false);

        return $whoops->handleException($e);
    }
}
