<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {styles}
 *
 * Renders all collected CSS (cascading _styles.css + {css} tags).
 * Place in layout <head>.
 */
final class StylesNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        return new self();
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo Colibri\View\Assets::renderStyles() %line;',
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        false && yield;
    }
}
