<?php

declare(strict_types=1);

namespace Colibri\Http;

class Response
{
    private bool $sent = false;

    /** @var array<string, string> */
    private array $headers = [];

    /** @var array<array{type: string, message: string}> */
    private array $flashMessages = [];

    private function __construct(
        private string $body = '',
        private int $statusCode = 200,
    ) {}

    /**
     * Create a JSON response.
     */
    public static function json(mixed $data, int $status = 200): self
    {
        $response = new self((string) json_encode($data, JSON_THROW_ON_ERROR), $status);
        $response->headers['Content-Type'] = 'application/json';

        return $response;
    }

    /**
     * Create an HTML response.
     */
    public static function html(string $html, int $status = 200): self
    {
        $response = new self($html, $status);
        $response->headers['Content-Type'] = 'text/html; charset=utf-8';

        return $response;
    }

    /**
     * Create a redirect response.
     */
    public static function redirect(string $url, int $status = 302): self
    {
        $response = new self('', $status);
        $response->headers['Location'] = $url;

        return $response;
    }

    /**
     * Redirect to the previous page.
     */
    public static function back(int $status = 302): self
    {
        $referer = $_SERVER['HTTP_REFERER'] ?? '/';

        return self::redirect($referer, $status);
    }

    // --- HTMX ---

    /**
     * Tell HTMX to perform a client-side redirect.
     */
    public static function htmxRedirect(string $url): self
    {
        return (new self())->header('HX-Redirect', $url);
    }

    /**
     * Tell HTMX to refresh the page.
     */
    public static function htmxRefresh(): self
    {
        return (new self())->header('HX-Refresh', 'true');
    }

    /**
     * Trigger a client-side event via HTMX.
     */
    public function htmxTrigger(string $event): self
    {
        return $this->header('HX-Trigger', $event);
    }

    /**
     * Return an HTML fragment for HTMX swap.
     */
    public static function htmxSwap(string $html): self
    {
        return new self($html, 200);
    }

    /**
     * Set the HTTP status code.
     */
    public function status(int $code): self
    {
        $this->statusCode = $code;

        return $this;
    }

    /**
     * Set a response header.
     */
    public function header(string $name, string $value): self
    {
        $this->headers[$name] = $value;

        return $this;
    }

    /**
     * Attach a flash message to the response (sent on redirect).
     */
    public function with(string $type, string $message): self
    {
        $this->flashMessages[] = ['type' => $type, 'message' => $message];

        return $this;
    }

    private bool $saveInput = false;

    /**
     * Save the current request input for form repopulation via old().
     */
    public function withInput(): self
    {
        $this->saveInput = true;

        return $this;
    }

    /**
     * Send the response to the client.
     */
    public function send(): void
    {
        if ($this->sent) {
            return;
        }

        $this->sent = true;

        if ($this->flashMessages !== [] || $this->saveInput) {
            if (session_status() === PHP_SESSION_NONE) {
                session_start();
            }

            if ($this->flashMessages !== []) {
                $_SESSION['_flash'] = array_merge($_SESSION['_flash'] ?? [], $this->flashMessages);
            }

            if ($this->saveInput) {
                $_SESSION['_old_input'] = array_merge($_POST, $_GET);
            }
        }

        http_response_code($this->statusCode);

        foreach ($this->headers as $name => $value) {
            header("$name: $value");
        }

        if ($this->body !== '') {
            echo $this->body;
        }
    }

    /**
     * Get the status code.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }

    /**
     * Check if this is a redirect response.
     */
    public function isRedirect(): bool
    {
        return $this->statusCode >= 300 && $this->statusCode < 400;
    }

    /**
     * Get the body.
     */
    public function getBody(): string
    {
        return $this->body;
    }

    /**
     * Get all headers.
     *
     * @return array<string, string>
     */
    public function getHeaders(): array
    {
        return $this->headers;
    }

    /**
     * Check if the response has been sent.
     */
    public function isSent(): bool
    {
        return $this->sent;
    }
}
