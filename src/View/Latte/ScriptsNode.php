<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {scripts}
 *
 * Renders all collected JS (cascading _scripts.js + {js} tags).
 * Place in layout before </body>.
 */
final class ScriptsNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        return new self();
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo Colibri\View\Assets::renderScripts() %line;',
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        false && yield;
    }
}
