<?php

declare(strict_types=1);

namespace Colibri\Mail;

use Colibri\Mail\Interfaces\MailDriverInterface;
use Colibri\Mail\Drivers\LogMailDriver;
use Colibri\Mail\Drivers\SendmailMailDriver;
use Colibri\Mail\Drivers\SmtpMailDriver;
use Colibri\Config;
use Colibri\View\View;

class Mail
{
    /** @var array<string, class-string<MailDriverInterface>> */
    private static array $drivers = [
        'log' => LogMailDriver::class,
        'smtp' => SmtpMailDriver::class,
        'sendmail' => SendmailMailDriver::class,
    ];

    /**
     * Register a custom mail driver.
     *
     * @param class-string<MailDriverInterface> $class
     */
    public static function registerDriver(string $name, string $class): void
    {
        self::$drivers[$name] = $class;
    }

    /**
     * Send a simple email.
     *
     * @param string|list<string> $to
     * @param array<string, mixed> $data
     */
    public static function send(
        string|array $to,
        string $subject,
        string $template,
        array $data = [],
    ): bool {
        $body = View::render($template, $data);
        $message = Message::create($to, $subject, $body);

        return self::driver()->send($message);
    }

    /**
     * Build a message with full control (cc, bcc, attachments, etc.).
     *
     * @param string|list<string> $to
     * @param array<string, mixed> $data
     */
    public static function message(
        string|array $to,
        string $subject,
        string $template,
        array $data = [],
    ): Message {
        $body = View::render($template, $data);

        return Message::create($to, $subject, $body);
    }

    /**
     * Send a pre-built message.
     */
    public static function deliver(Message $message): bool
    {
        return self::driver()->send($message);
    }

    /**
     * Resolve the configured mail driver.
     */
    private static function driver(): MailDriverInterface
    {
        $name = Config::get('mail.driver', 'log');

        if (! isset(self::$drivers[$name])) {
            throw new \RuntimeException("Mail driver '$name' not found.");
        }

        $class = self::$drivers[$name];

        return new $class();
    }
}
