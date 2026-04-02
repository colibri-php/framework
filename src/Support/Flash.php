<?php

declare(strict_types=1);

namespace Colibri\Support;

class Flash
{
    /**
     * Set a flash message.
     */
    public static function set(string $type, string $message): void
    {
        self::ensureSession();
        $_SESSION['_flash'][] = ['type' => $type, 'message' => $message];
    }

    /**
     * Get and remove the first flash message of a given type.
     */
    public static function get(string $type): ?string
    {
        self::ensureSession();

        if (! isset($_SESSION['_flash'])) {
            return null;
        }

        foreach ($_SESSION['_flash'] as $i => $flash) {
            if ($flash['type'] === $type) {
                unset($_SESSION['_flash'][$i]);
                $_SESSION['_flash'] = array_values($_SESSION['_flash']);

                return $flash['message'];
            }
        }

        return null;
    }

    /**
     * Check if a flash message of a given type exists.
     */
    public static function has(string $type): bool
    {
        self::ensureSession();

        foreach ($_SESSION['_flash'] ?? [] as $flash) {
            if ($flash['type'] === $type) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get all flash messages and clear them.
     *
     * @return array<string, string> Keyed by type.
     */
    public static function all(): array
    {
        self::ensureSession();

        $messages = [];
        foreach ($_SESSION['_flash'] ?? [] as $flash) {
            $messages[$flash['type']] = $flash['message'];
        }

        $_SESSION['_flash'] = [];

        return $messages;
    }

    /**
     * Store old input values for form repopulation.
     *
     * @param array<string, mixed> $input
     */
    public static function setOldInput(array $input): void
    {
        self::ensureSession();
        $_SESSION['_old_input'] = $input;
    }

    /**
     * Get an old input value and clear it.
     */
    public static function getOldInput(string $key, mixed $default = null): mixed
    {
        self::ensureSession();

        $value = $_SESSION['_old_input'][$key] ?? $default;

        return $value;
    }

    /**
     * Clear all old input.
     */
    public static function clearOldInput(): void
    {
        self::ensureSession();
        unset($_SESSION['_old_input']);
    }

    private static function ensureSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }
}
