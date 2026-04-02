<?php

declare(strict_types=1);

namespace Colibri\Mail\Interfaces;

use Colibri\Mail\Message;

interface MailDriverInterface
{
    /**
     * Send an email message.
     */
    public function send(Message $message): bool;
}
