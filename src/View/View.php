<?php

declare(strict_types=1);

namespace Colibri\View;

use Latte\Engine;
use Colibri\Support\Str;
use Colibri\Config;

class View
{
    private static ?Engine $engine = null;

    /**
     * Get or create the Latte engine.
     */
    public static function engine(): Engine
    {
        if (self::$engine !== null) {
            return self::$engine;
        }

        self::$engine = new Engine();
        self::$engine->setLoader(new \Latte\Loaders\FileLoader());
        self::$engine->setTempDirectory(base_path('storage/cache/views'));
        self::$engine->setAutoRefresh(Config::get('app.debug', true));
        self::$engine->addExtension(new LatteExtension());

        return self::$engine;
    }

    /**
     * Render a template with the given data.
     *
     * @param array<string, mixed> $data
     */
    public static function render(string $template, array $data = [], ?Page $page = null): string
    {
        $page ??= new Page();

        $data['page'] = $page;

        $templatePath = self::resolveTemplatePath($template);

        return self::engine()->renderToString($templatePath, $data);
    }

    /**
     * Render a partial template from templates/partials/.
     *
     * @param array<string, mixed> $data
     */
    public static function renderPartial(string $name, array $data = []): string
    {
        $path = base_path('templates' . DIRECTORY_SEPARATOR . 'partials' . DIRECTORY_SEPARATOR . $name . '.latte');

        return self::engine()->renderToString($path, $data);
    }

    /**
     * Resolve a layout name to an absolute file path.
     */
    public static function resolveLayoutPath(string $layout): string
    {
        if (Str::endsWith($layout, '.latte')) {
            return base_path('templates' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout);
        }

        return base_path('templates' . DIRECTORY_SEPARATOR . 'layouts' . DIRECTORY_SEPARATOR . $layout . '.latte');
    }

    /**
     * Resolve a template name to a file path.
     */
    private static function resolveTemplatePath(string $template): string
    {
        // If already an absolute path, use it directly
        if (Str::startsWith($template, '/') || Str::contains($template, ':\\')) {
            return $template;
        }

        // If it already has an extension, use it
        if (Str::endsWith($template, '.latte')) {
            return base_path('templates' . DIRECTORY_SEPARATOR . $template);
        }

        return base_path('templates' . DIRECTORY_SEPARATOR . $template . '.latte');
    }
}
