<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {alerts}
 *
 * Renders flash message alerts using templates/partials/alerts/default.latte.
 */
final class AlertsNode extends StatementNode
{
    public static function create(Tag $tag): static
    {
        return new self();
    }

    public function print(PrintContext $context): string
    {
        return $context->format(
            'echo Colibri\View\View::renderPartial("alerts/default", []) %line;',
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        false && yield;
    }
}
