<?php

declare(strict_types=1);

namespace Colibri\Mail\Drivers;

use Colibri\Mail\Interfaces\MailDriverInterface;
use Colibri\Mail\Message;

class LogMailDriver implements MailDriverInterface
{
    public function send(Message $message): bool
    {
        $logDir = base_path('storage/logs');

        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        $entry = "=== EMAIL " . date('Y-m-d H:i:s') . " ===\n";
        $entry .= "To: " . implode(', ', $message->to) . "\n";
        if ($message->cc !== []) {
            $entry .= "Cc: " . implode(', ', $message->cc) . "\n";
        }
        if ($message->bcc !== []) {
            $entry .= "Bcc: " . implode(', ', $message->bcc) . "\n";
        }
        if ($message->replyTo !== null) {
            $entry .= "Reply-To: $message->replyTo\n";
        }
        if ($message->attachments !== []) {
            $entry .= "Attachments: " . implode(', ', array_column($message->attachments, 'name')) . "\n";
        }
        $entry .= "Subject: $message->subject\n";
        $entry .= "Body:\n$message->body\n\n";

        file_put_contents($logDir . DIRECTORY_SEPARATOR . 'mail.log', $entry, FILE_APPEND | LOCK_EX);

        return true;
    }
}
