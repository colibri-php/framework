<?php

declare(strict_types=1);

namespace Colibri\View;

use Colibri\View\Latte\AlertsNode;
use Colibri\View\Latte\DdNode;
use Colibri\View\Latte\DumpNode;
use Colibri\View\Latte\ErrorNode;
use Colibri\View\Latte\PageNode;
use Colibri\View\Latte\RedirectNode;
use Colibri\View\Latte\TranslateNode;
use Colibri\View\Latte\CssNode;
use Colibri\View\Latte\ImageNode;
use Colibri\View\Latte\JsNode;
use Colibri\View\Latte\MetaNode;
use Colibri\View\Latte\PaginationNode;
use Colibri\View\Latte\ScriptsNode;
use Colibri\View\Latte\StylesNode;
use Colibri\View\Latte\ViteNode;
use Latte\Extension;
use Colibri\Support\Image;

class LatteExtension extends Extension
{
    public function getTags(): array
    {
        return [
            'page' => PageNode::create(...),
            'redirect' => RedirectNode::create(...),
            'error' => ErrorNode::create(...),
            'dump' => DumpNode::create(...),
            'dd' => DdNode::create(...),
            't' => TranslateNode::create(...),
            'vite' => ViteNode::create(...),
            'image' => ImageNode::create(...),
            'css' => CssNode::create(...),
            'js' => JsNode::create(...),
            'meta' => MetaNode::create(...),
            'styles' => StylesNode::create(...),
            'scripts' => ScriptsNode::create(...),
            'pagination' => PaginationNode::create(...),
            'alerts' => AlertsNode::create(...),
        ];
    }

    public function getFilters(): array
    {
        return [
            't' => fn(string $key, mixed ...$params): string => Lang::translate($key, $params),
            'resize' => fn(string $path, int $width, ?int $height = null): ?string => Image::resize($path, $width, $height),
            'crop' => fn(string $path, int $width, int $height): ?string => Image::crop($path, $width, $height),
            'thumbnail' => fn(string $path, int $size): ?string => Image::thumbnail($path, $size),
        ];
    }

    public function getFunctions(): array
    {
        return [
            'flash' => flash(...),
            'old' => old(...),
            'csrf_token' => csrf_token(...),
            'csrf_field' => csrf_field(...),
            't' => t(...),
            'url' => url(...),
            'locale' => Lang::locale(...),
            'locales' => Lang::locales(...),
            'site_title' => site_title(...),
        ];
    }

    public function getProviders(): array
    {
        return [
            'coreParentFinder' => function (\Latte\Runtime\Template $template): ?string {
                $params = $template->getParameters();

                if (! isset($params['page']) || ! $params['page'] instanceof Page) {
                    return null;
                }

                // Only auto-layout the main template, not includes
                if ($template->getReferringTemplate() !== null) {
                    return null;
                }

                // Skip layout for HTMX requests (render partial)
                if (isset($params['htmx']) && $params['htmx'] === true) {
                    return null;
                }

                $layoutPath = View::resolveLayoutPath($params['page']->layout);

                return file_exists($layoutPath) ? $layoutPath : null;
            },
        ];
    }
}
