<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\Token;

/**
 * {t 'welcome'}
 * {t 'items', count: 3}
 */
final class TranslateNode extends StatementNode
{
    public ExpressionNode $key;

    /** @var array<string, ExpressionNode> */
    public array $params = [];

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->key = $tag->parser->parseUnquotedStringOrExpression();

        while ($tag->parser->stream->tryConsume(',')) {
            $name = $tag->parser->stream->consume(Token::Php_Identifier);
            $tag->parser->stream->consume(':');
            $value = $tag->parser->parseExpression();
            $node->params[$name->text] = $value;
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        if ($this->params === []) {
            return $context->format(
                'echo Colibri\View\Lang::translate(%node) %line;',
                $this->key,
                $this->position,
            );
        }

        $paramsCode = '[';
        $first = true;
        foreach ($this->params as $name => $value) {
            if (! $first) {
                $paramsCode .= ', ';
            }
            $paramsCode .= "'$name' => " . $value->print($context);
            $first = false;
        }
        $paramsCode .= ']';

        return $context->format(
            'echo Colibri\View\Lang::translate(%node, %raw) %line;',
            $this->key,
            $paramsCode,
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->key;
        foreach ($this->params as &$value) {
            yield $value;
        }
    }
}
