<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\Token;

/**
 * {page title: 'About', description: '...', layout: 'admin'}
 *
 * Sets $page properties declaratively.
 */
final class PageNode extends StatementNode
{
    /** @var array<string, ExpressionNode> */
    public array $properties = [];

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();

        do {
            $key = $tag->parser->stream->consume(Token::Php_Identifier);
            $tag->parser->stream->consume(':');
            $value = $tag->parser->parseExpression();
            $node->properties[$key->text] = $value;
        } while ($tag->parser->stream->tryConsume(','));

        return $node;
    }

    public function print(PrintContext $context): string
    {
        $code = '';
        foreach ($this->properties as $key => $value) {
            if ($key === 'layout') {
                // Set both $page->layout and Latte's parent template
                $code .= $context->format(
                    '$page->layout = %node; $this->parentName = Colibri\View\View::resolveLayoutPath(%node);',
                    $value,
                    $value,
                );
            } else {
                $code .= $context->format(
                    '$page->%raw = %node;',
                    $key,
                    $value,
                );
            }
        }

        return $code;
    }

    public function &getIterator(): \Generator
    {
        foreach ($this->properties as &$value) {
            yield $value;
        }
    }
}
