<?php

declare(strict_types=1);

namespace Colibri\Mail;

class Message
{
    /** @var list<string> */
    public array $to = [];

    /** @var list<string> */
    public array $cc = [];

    /** @var list<string> */
    public array $bcc = [];

    public ?string $replyTo = null;

    public string $subject = '';

    public string $body = '';

    /** @var list<array{path: string, name: string}> */
    public array $attachments = [];

    /**
     * @param string|list<string> $to
     */
    public static function create(string|array $to, string $subject, string $body): self
    {
        $message = new self();
        $message->to = is_array($to) ? $to : [$to];
        $message->subject = $subject;
        $message->body = $body;

        return $message;
    }

    public function cc(string ...$addresses): self
    {
        $this->cc = array_merge($this->cc, $addresses);

        return $this;
    }

    public function bcc(string ...$addresses): self
    {
        $this->bcc = array_merge($this->bcc, $addresses);

        return $this;
    }

    public function replyTo(string $address): self
    {
        $this->replyTo = $address;

        return $this;
    }

    public function attach(string $path, ?string $name = null): self
    {
        $this->attachments[] = [
            'path' => $path,
            'name' => $name ?? basename($path),
        ];

        return $this;
    }
}
