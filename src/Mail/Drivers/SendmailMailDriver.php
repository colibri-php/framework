<?php

declare(strict_types=1);

namespace Colibri\Mail\Drivers;

use Colibri\Mail\Message;
use Colibri\Config;
use Colibri\Mail\Interfaces\MailDriverInterface;
use Colibri\Support\Log;
use PHPMailer\PHPMailer\PHPMailer;

class SendmailMailDriver implements MailDriverInterface
{
    public function send(Message $message): bool
    {
        $mail = new PHPMailer(true);

        try {
            $mail->isSendmail();

            $from = Config::get('mail.from', []);
            $mail->setFrom($from['address'] ?? 'noreply@example.com', $from['name'] ?? 'Colibri');

            foreach ($message->to as $address) {
                $mail->addAddress($address);
            }
            foreach ($message->cc as $address) {
                $mail->addCC($address);
            }
            foreach ($message->bcc as $address) {
                $mail->addBCC($address);
            }
            if ($message->replyTo !== null) {
                $mail->addReplyTo($message->replyTo);
            }
            foreach ($message->attachments as $attachment) {
                $mail->addAttachment($attachment['path'], $attachment['name']);
            }

            $mail->isHTML(true);
            $mail->Subject = $message->subject;
            $mail->Body = $message->body;
            $mail->CharSet = 'UTF-8';

            $mail->send();

            return true;
        } catch (\Exception $e) {
            Log::error('Mail send failed: ' . $e->getMessage(), [
                'to' => $message->to,
                'subject' => $message->subject,
            ]);

            return false;
        }
    }
}
