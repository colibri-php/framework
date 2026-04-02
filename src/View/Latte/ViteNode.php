<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {vite 'resources/js/app.js'}
 * {vite 'resources/css/app.css'}
 */
final class ViteNode extends StatementNode
{
    public ExpressionNode $entry;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->entry = $tag->parser->parseUnquotedStringOrExpression();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo Colibri\View\Vite::asset(%node) %line;',
            $this->entry,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->entry;
    }
}
