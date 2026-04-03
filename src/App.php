<?php

declare(strict_types=1);

namespace Colibri;

use Colibri\Exceptions\HttpException;
use Colibri\Support\Str;
use Colibri\Http\Request;
use Colibri\Http\Response;
use Colibri\Http\Router;
use Colibri\Support\Flash;
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

        // Clear old input after rendering a non-redirect response (flash-once behavior)
        if (! $response->isRedirect()) {
            Flash::clearOldInput();
        }
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

        $debug = Config::get('app.debug', false);

        if ($debug && $e->statusCode === 404) {
            return Response::html(self::renderDebug404($request), 404);
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
     * Render a debug-friendly 404 page showing the requested path and available routes.
     */
    private static function renderDebug404(Request $request): string
    {
        $path = htmlspecialchars($request->path);
        $method = htmlspecialchars($request->method);

        $html = '<!DOCTYPE html><html><head><meta charset="UTF-8">';
        $html .= '<title>404 — Not Found</title>';
        $html .= '<style>';
        $html .= 'body{font-family:-apple-system,sans-serif;max-width:800px;margin:2rem auto;padding:0 1rem;color:#333}';
        $html .= 'h1{color:#c0392b}h2{color:#666;margin-top:2rem}';
        $html .= '.path{background:#fff3cd;padding:.5rem 1rem;border-radius:4px;font-family:monospace;font-size:1.1rem}';
        $html .= 'table{width:100%;border-collapse:collapse;margin-top:1rem}';
        $html .= 'td,th{text-align:left;padding:.4rem .8rem;border-bottom:1px solid #eee}';
        $html .= 'th{color:#888;font-size:.85rem;text-transform:uppercase}';
        $html .= '.type{color:#888;font-size:.85rem}';
        $html .= '</style>';
        $html .= '</head><body>';
        $html .= '<h1>404 — Not Found</h1>';
        $html .= "<p class=\"path\">$method $path</p>";

        // Scan routes
        $html .= '<h2>Available routes</h2>';
        $html .= '<table><tr><th>URL</th><th>Type</th><th>File</th></tr>';

        $basePath = base_path();
        $html .= self::scanRoutesForDebug(base_path('routes/web'), base_path('routes/web'), '', $basePath);
        $html .= self::scanRoutesForDebug(base_path('routes/api'), base_path('routes/api'), '/api', $basePath);

        $html .= '</table>';
        $html .= '<p style="margin-top:2rem;color:#aaa;font-size:.85rem">This page is only visible in debug mode.</p>';
        $html .= '</body></html>';

        return $html;
    }

    /**
     * Scan a routes directory and return HTML table rows.
     */
    private static function scanRoutesForDebug(string $dir, string $baseDir, string $prefix, string $projectPath): string
    {
        if (! is_dir($dir)) {
            return '';
        }

        $html = '';
        $entries = scandir($dir) ?: [];
        sort($entries);

        foreach ($entries as $entry) {
            if ($entry[0] === '.' || $entry[0] === '_') {
                continue;
            }

            $fullPath = $dir . DIRECTORY_SEPARATOR . $entry;

            if (is_dir($fullPath)) {
                $html .= self::scanRoutesForDebug($fullPath, $baseDir, $prefix, $projectPath);

                continue;
            }

            if (! Str::endsWith($entry, '.php') && ! Str::endsWith($entry, '.latte')) {
                continue;
            }

            $relative = str_replace($baseDir, '', $dir . DIRECTORY_SEPARATOR . $entry);
            $relative = str_replace('\\', '/', $relative);

            $file = pathinfo($entry, PATHINFO_FILENAME);
            $ext = pathinfo($entry, PATHINFO_EXTENSION);

            $dirPart = str_replace('\\', '/', dirname($relative));
            $url = $prefix . $dirPart;
            if ($file === 'index') {
                $url = rtrim($url, '/') ?: '/';
            } else {
                $url = rtrim($url, '/') . '/' . $file;
            }

            $filePath = str_replace($projectPath, '', $fullPath);
            $filePath = str_replace('\\', '/', $filePath);

            $html .= '<tr>';
            $html .= '<td>' . htmlspecialchars($url) . '</td>';
            $html .= '<td class="type">' . $ext . '</td>';
            $html .= '<td class="type">' . htmlspecialchars($filePath) . '</td>';
            $html .= '</tr>';
        }

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
