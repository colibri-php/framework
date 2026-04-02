<?php

declare(strict_types=1);

namespace Colibri\Exceptions;

class HttpException extends \RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message ?: $this->defaultMessage($statusCode), $statusCode);
    }

    private function defaultMessage(int $code): string
    {
        return match ($code) {
            400 => 'Bad Request',
            401 => 'Unauthorized',
            403 => 'Forbidden',
            404 => 'Not Found',
            405 => 'Method Not Allowed',
            429 => 'Too Many Requests',
            500 => 'Internal Server Error',
            default => 'HTTP Error',
        };
    }
}
