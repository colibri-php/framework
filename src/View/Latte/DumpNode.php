<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {dump $var}
 *
 * Uses symfony/var-dumper in debug mode, ignored in production.
 */
final class DumpNode extends StatementNode
{
    public ?ExpressionNode $expression = null;

    public static function create(Tag $tag): static
    {
        $node = new self();
        if (! $tag->parser->isEnd()) {
            $node->expression = $tag->parser->parseExpression();
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        if ($this->expression) {
            return $context->format(
                'if (Colibri\Config::get("app.debug", false) && function_exists("dump")) { dump(%node); }',
                $this->expression,
            );
        }

        return $context->format(
            'if (Colibri\Config::get("app.debug", false) && function_exists("dump")) { dump(get_defined_vars()); }',
        );
    }

    public function &getIterator(): \Generator
    {
        if ($this->expression) {
            yield $this->expression;
        }
    }
}
