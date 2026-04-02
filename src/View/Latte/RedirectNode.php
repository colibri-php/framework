<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;

/**
 * {redirect '/dashboard'}
 * {redirect '/new-url', permanent}
 */
final class RedirectNode extends StatementNode
{
    public ExpressionNode $url;

    public bool $permanent = false;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->url = $tag->parser->parseUnquotedStringOrExpression();

        if ($tag->parser->stream->tryConsume(',')) {
            $tag->parser->stream->consume('permanent');
            $node->permanent = true;
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        $status = $this->permanent ? 301 : 302;

        return $context->format(
            'header("Location: " . %node); http_response_code(%dump); exit;',
            $this->url,
            $status,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->url;
    }
}
