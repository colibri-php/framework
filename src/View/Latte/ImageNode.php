<?php

declare(strict_types=1);

namespace Colibri\View\Latte;

use Latte\Compiler\Nodes\Php\ExpressionNode;
use Latte\Compiler\Nodes\StatementNode;
use Latte\Compiler\PrintContext;
use Latte\Compiler\Tag;
use Latte\Compiler\Token;

/**
 * {image 'images/photo.jpg', resize: 400}
 * {image 'images/photo.jpg', thumbnail: 100}
 * {image 'images/photo.jpg', crop: 300, cropHeight: 200}
 * {image 'images/photo.jpg', resize: 400, alt: 'Description'}
 */
final class ImageNode extends StatementNode
{
    public ExpressionNode $path;

    public ?ExpressionNode $resize = null;

    public ?ExpressionNode $resizeHeight = null;

    public ?ExpressionNode $thumbnail = null;

    public ?ExpressionNode $crop = null;

    public ?ExpressionNode $cropHeight = null;

    public ?ExpressionNode $alt = null;

    public static function create(Tag $tag): static
    {
        $tag->expectArguments();
        $node = new self();
        $node->path = $tag->parser->parseUnquotedStringOrExpression();

        while ($tag->parser->stream->tryConsume(',')) {
            $name = $tag->parser->stream->consume(Token::Php_Identifier)->text;
            $tag->parser->stream->consume(':');
            $value = $tag->parser->parseExpression();

            match ($name) {
                'resize' => $node->resize = $value,
                'resizeHeight' => $node->resizeHeight = $value,
                'thumbnail' => $node->thumbnail = $value,
                'crop' => $node->crop = $value,
                'cropHeight' => $node->cropHeight = $value,
                'alt' => $node->alt = $value,
                default => null,
            };
        }

        return $node;
    }

    public function print(PrintContext $context): string
    {
        if ($this->resize !== null) {
            if ($this->resizeHeight !== null) {
                return $context->format(
                    'echo \'<img src="\' . htmlspecialchars(Colibri\Support\Image::resize(%node, %node, %node) ?? %node) . \'" alt="\' . htmlspecialchars(%node) . \'">\' %line;',
                    $this->path,
                    $this->resize,
                    $this->resizeHeight,
                    $this->path,
                    $this->alt ?? new \Latte\Compiler\Nodes\Php\Scalar\StringNode(''),
                    $this->position,
                );
            }

            return $context->format(
                'echo \'<img src="\' . htmlspecialchars(Colibri\Support\Image::resize(%node, %node) ?? %node) . \'" alt="\' . htmlspecialchars(%node) . \'">\' %line;',
                $this->path,
                $this->resize,
                $this->path,
                $this->alt ?? new \Latte\Compiler\Nodes\Php\Scalar\StringNode(''),
                $this->position,
            );
        }

        if ($this->thumbnail !== null) {
            return $context->format(
                'echo \'<img src="\' . htmlspecialchars(Colibri\Support\Image::thumbnail(%node, %node) ?? %node) . \'" alt="\' . htmlspecialchars(%node) . \'">\' %line;',
                $this->path,
                $this->thumbnail,
                $this->path,
                $this->alt ?? new \Latte\Compiler\Nodes\Php\Scalar\StringNode(''),
                $this->position,
            );
        }

        if ($this->crop !== null && $this->cropHeight !== null) {
            return $context->format(
                'echo \'<img src="\' . htmlspecialchars(Colibri\Support\Image::crop(%node, %node, %node) ?? %node) . \'" alt="\' . htmlspecialchars(%node) . \'">\' %line;',
                $this->path,
                $this->crop,
                $this->cropHeight,
                $this->path,
                $this->alt ?? new \Latte\Compiler\Nodes\Php\Scalar\StringNode(''),
                $this->position,
            );
        }

        // No transform — output with leading /
        return $context->format(
            'echo \'<img src="/\' . ltrim(htmlspecialchars(%node), \'/\') . \'" alt="\' . htmlspecialchars(%node) . \'">\' %line;',
            $this->path,
            $this->alt ?? new \Latte\Compiler\Nodes\Php\Scalar\StringNode(''),
            $this->position,
        );
    }

    public function &getIterator(): \Generator
    {
        yield $this->path;
        if ($this->resize) {
            yield $this->resize;
        }
        if ($this->resizeHeight) {
            yield $this->resizeHeight;
        }
        if ($this->thumbnail) {
            yield $this->thumbnail;
        }
        if ($this->crop) {
            yield $this->crop;
        }
        if ($this->cropHeight) {
            yield $this->cropHeight;
        }
        if ($this->alt) {
            yield $this->alt;
        }
    }
}
