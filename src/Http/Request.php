<?php

declare(strict_types=1);

namespace Colibri\Http;

use Valitron\Validator;
use Colibri\Support\Str;

class Request
{
    /** @var array<string, mixed>|null */
    private ?array $jsonBody = null;

    private bool $jsonParsed = false;

    public string $method {
        get => strtoupper($_SERVER['REQUEST_METHOD'] ?? 'GET');
    }

    public string $path {
        get {
            $uri = $_SERVER['REQUEST_URI'] ?? '/';

            return '/' . trim(parse_url($uri, PHP_URL_PATH) ?: '/', '/');
        }
    }

    /**
     * Get all query string parameters, or a single one.
     */
    public function query(?string $key = null, mixed $default = null): mixed
    {
        if ($key === null) {
            return $_GET;
        }

        return $_GET[$key] ?? $default;
    }

    /**
     * Get the raw parsed body (POST data or decoded JSON).
     *
     * @return array<string, mixed>
     */
    public function body(): array
    {
        if ($this->isJson()) {
            return $this->json();
        }

        return $_POST;
    }

    /**
     * Get an input value from query, body, or JSON (in that priority).
     */
    public function input(string $key, mixed $default = null): mixed
    {
        return $this->body()[$key] ?? $this->query($key) ?? $default;
    }

    /**
     * Get a request header value.
     */
    public function header(string $name, ?string $default = null): ?string
    {
        $normalized = 'HTTP_' . strtoupper(str_replace('-', '_', $name));

        if ($normalized === 'HTTP_CONTENT_TYPE') {
            return $_SERVER['CONTENT_TYPE'] ?? $default;
        }

        if ($normalized === 'HTTP_CONTENT_LENGTH') {
            return $_SERVER['CONTENT_LENGTH'] ?? $default;
        }

        return $_SERVER[$normalized] ?? $default;
    }

    /**
     * Check if the request has a JSON content type.
     */
    public function isJson(): bool
    {
        return Str::contains($this->header('Content-Type', ''), 'application/json');
    }

    /**
     * Validate the request data against the given rules.
     *
     * Returns null if validation passes, or an array of errors keyed by field.
     *
     * @param array<string, array<string|array<mixed>>> $rules e.g. ['email' => ['required', 'email'], 'name' => ['required', ['lengthMin', 3]]]
     * @return array<string, list<string>>|null Errors keyed by field, or null if valid.
     */
    public function validate(array $rules): ?array
    {
        $data = array_merge($this->query() ?? [], $this->body());
        $v = new Validator($data);

        foreach ($rules as $field => $fieldRules) {
            foreach ($fieldRules as $rule) {
                if (is_array($rule)) {
                    $ruleName = array_shift($rule);
                    $v->rule($ruleName, $field, ...$rule);
                } else {
                    $v->rule($rule, $field);
                }
            }
        }

        if ($v->validate()) {
            return null;
        }

        return $v->errors();
    }

    // --- HTMX ---

    /**
     * Check if this is an HTMX request.
     */
    public function isHtmx(): bool
    {
        return $this->header('HX-Request') === 'true';
    }

    /**
     * Get the HTMX target element ID.
     */
    public function htmxTarget(): ?string
    {
        return $this->header('HX-Target');
    }

    /**
     * Get the HTMX trigger element ID.
     */
    public function htmxTrigger(): ?string
    {
        return $this->header('HX-Trigger');
    }

    /**
     * Check if this is an HTMX boosted request.
     */
    public function htmxBoosted(): bool
    {
        return $this->header('HX-Boosted') === 'true';
    }

    /**
     * Parse and return the JSON body.
     *
     * @return array<string, mixed>
     */
    private function json(): array
    {
        if (! $this->jsonParsed) {
            $raw = file_get_contents('php://input') ?: '';
            $decoded = json_decode($raw, true);
            $this->jsonBody = is_array($decoded) ? $decoded : [];
            $this->jsonParsed = true;
        }

        return $this->jsonBody ?? [];
    }
}
