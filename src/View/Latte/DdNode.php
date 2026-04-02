<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {dd $var}
 *
 * Dump and die — debug mode only.
 */
final class DdNode extends StatementNode
{
    public ExpressionNode $expression;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->expression = $tag->parser->parseExpression();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'if (Colibri\Config::get("app.debug", false) && function_exists("dd")) { dd(%node); }',
            $this->expression,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->expression;
    }
}
