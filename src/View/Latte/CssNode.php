<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {css 'admin.css'}
 * {css 'https://cdn.example.com/lib.css'}
 *
 * Resolves paths from public/assets/ unless the path is an absolute URL.
 */
final class CssNode extends StatementNode
{
    public ExpressionNode $path;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->path = $tag->parser->parseUnquotedStringOrExpression();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'Colibri\View\Assets::pushCssFile(%node) %line;',
            $this->path,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->path;
    }
}
