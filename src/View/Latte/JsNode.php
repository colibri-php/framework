<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {js 'chart.js'}
 * {js 'chart.js', defer}
 * {js 'chart.js', async}
 * {js 'https://cdn.example.com/lib.js'}
 *
 * Resolves paths from public/assets/ unless the path is an absolute URL.
 */
final class JsNode extends StatementNode
{
    public ExpressionNode $path;

    public bool $defer = false;

    public bool $async = false;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->path = $tag->parser->parseUnquotedStringOrExpression();

        if ($tag->parser->stream->tryConsume(',')) {
            $attr = $tag->parser->stream->consume()->text;
            match ($attr) {
                'defer' => $node->defer = true,
                'async' => $node->async = true,
                default => null,
            };
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        $defer = $this->defer ? 'true' : 'false';
        $async = $this->async ? 'true' : 'false';

        return $context->format(
            'Colibri\View\Assets::pushJsFile(%node, %raw, %raw) %line;',
            $this->path,
            $defer,
            $async,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->path;
    }
}
