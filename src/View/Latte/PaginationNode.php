<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\Token;

/**
 * {pagination $results}
 * {pagination $results, type: 'simple'}
 * {pagination $results, template: 'my/custom'}
 */
final class PaginationNode extends StatementNode
{
    public ExpressionNode $results;

    public ?ExpressionNode $type = null;

    public ?ExpressionNode $template = null;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->results = $tag->parser->parseExpression();

        while ($tag->parser->stream->tryConsume(',')) {
            $name = $tag->parser->stream->consume(Token::Php_Identifier)->text;
            $tag->parser->stream->consume(':');
            $value = $tag->parser->parseExpression();

            match ($name) {
                'type' => $node->type = $value,
                'template' => $node->template = $value,
                default => null,
            };
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        if ($this->template !== null) {
            return $context->format(
                'echo Colibri\View\View::render(Colibri\View\View::resolveTemplatePath(%node), ["pagination" => %node]) %line;',
                $this->template,
                $this->results,
                $this->position,
            );
        }

        if ($this->type !== null) {
            return $context->format(
                'echo Colibri\View\View::renderPartial("pagination/" . %node, ["pagination" => %node]) %line;',
                $this->type,
                $this->results,
                $this->position,
            );
        }

        return $context->format(
            'echo Colibri\View\View::renderPartial("pagination/default", ["pagination" => %node]) %line;',
            $this->results,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->results;
        if ($this->type) {
            yield $this->type;
        }
        if ($this->template) {
            yield $this->template;
        }
    }
}
