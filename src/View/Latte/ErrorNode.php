<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {error 404}
 * {error 403}
 */
final class ErrorNode extends StatementNode
{
    public ExpressionNode $code;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->code = $tag->parser->parseExpression();

        return $node;
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'throw new Colibri\Exceptions\HttpException(%node);',
            $this->code,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->code;
    }
}
