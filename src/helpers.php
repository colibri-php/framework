<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Support\Flash;
use Colibri\View\Lang;
use Colibri\View\Page;
use Colibri\View\View;

/**
 * Get an environment variable with an optional default.
 */
function env(string $key, mixed $default = null): mixed
{
    $value = $_ENV[$key] ?? getenv($key);

    if ($value === false) {
        return $default;
    }

    // Cast common string values
    return match (strtolower((string) $value)) {
        'true', '(true)' => true,
        'false', '(false)' => false,
        'null', '(null)' => null,
        'empty', '(empty)' => '',
        default => $value,
    };
}

/**
 * Resolve a path relative to the project root.
 */
function base_path(string $path = ''): string
{
    $app = App::getInstance()
        ?? throw new RuntimeException('App has not been booted.');

    return $app->basePath($path);
}

/**
 * Render a Latte template with the given data.
 *
 * @param array<string, mixed> $data
 */
function view(string $template, array $data = [], ?Page $page = null): string
{
    return View::render($template, $data, $page);
}

/**
 * Get the current CSRF token.
 */
function csrf_token(): string
{
    if (session_status() === PHP_SESSION_NONE) {
        session_start();
    }

    if (! isset($_SESSION['_csrf_token'])) {
        $_SESSION['_csrf_token'] = bin2hex(random_bytes(32));
    }

    return $_SESSION['_csrf_token'];
}

/**
 * Generate a hidden input field with the CSRF token.
 */
function csrf_field(): string
{
    return '<input type="hidden" name="_token" value="' . csrf_token() . '">';
}

/**
 * Get flash messages.
 *
 * @return array<string, string>|string|null All messages if no type, single message if type given.
 */
function flash(?string $type = null): array|string|null
{
    if ($type === null) {
        return Flash::all();
    }

    return Flash::get($type);
}

/**
 * Get an old input value for form repopulation.
 */
function old(string $key, mixed $default = null): mixed
{
    return Flash::getOldInput($key, $default);
}

/**
 * Translate a key.
 *
 * @param array<string, mixed> $params
 */
function t(string $key, array $params = []): string
{
    return Lang::translate($key, $params);
}

/**
 * Build a URL with locale support.
 *
 * @param array<string, mixed> $query
 */
function url(?string $path = null, ?string $locale = null, array $query = [], string $anchor = ''): string
{
    $prefixes = Lang::locales();
    $activeLocale = $locale ?? Lang::locale();
    $prefix = $prefixes[$activeLocale] ?? '/';

    // Current path if none given
    if ($path === null) {
        $path = $_SERVER['REQUEST_URI'] ?? '/';
        $path = parse_url($path, PHP_URL_PATH) ?: '/';

        // Strip existing locale prefix from path
        foreach ($prefixes as $loc => $pfx) {
            if ($pfx !== '/' && Colibri\Support\Str::startsWith($path, $pfx)) {
                $path = substr($path, strlen($pfx)) ?: '/';
                break;
            }
        }
    }

    // Build the URL
    $path = '/' . ltrim($path, '/');

    if ($prefix !== '/') {
        $url = rtrim($prefix, '/') . $path;
    } else {
        $url = $path;
    }

    // Clean double slashes
    $url = (string) preg_replace('#/+#', '/', $url);

    // Ensure leading slash (unless it's a full URL)
    if (! Colibri\Support\Str::startsWith($url, 'http')) {
        $url = '/' . ltrim($url, '/');
    }

    // Query string
    if ($query !== []) {
        $url .= '?' . http_build_query($query);
    }

    // Anchor
    if ($anchor !== '') {
        $url .= '#' . $anchor;
    }

    return $url;
}

/**
 * Generate the formatted site title for the <title> tag.
 */
function site_title(string $pageTitle): string
{
    $siteName = Colibri\Config::get('app.name', 'Colibri');
    $format = Colibri\Config::get('app.title_format', ':title | :site');

    if ($pageTitle === $siteName) {
        return $siteName;
    }

    return str_replace([':title', ':site'], [$pageTitle, $siteName], $format);
}
