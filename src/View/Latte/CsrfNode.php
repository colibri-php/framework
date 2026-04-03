<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {csrf}
 *
 * Outputs a hidden CSRF token input field.
 */
final class CsrfNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        return new self();
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo csrf_field() %line;',
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        false && yield;
    }
}
