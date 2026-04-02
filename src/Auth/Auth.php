<?php

declare(strict_types=1);

namespace Colibri\Auth;

use Colibri\Auth\Drivers\DbAuthDriver;
use Colibri\Auth\Drivers\FileAuthDriver;
use Colibri\Auth\Interfaces\AuthDriverInterface;
use Colibri\Config;

class Auth
{
    /** @var array<string, mixed>|null|false */
    private static array|false|null $currentUser = false;

    private static ?AuthDriverInterface $driverInstance = null;

    /** @var array<string, class-string<AuthDriverInterface>> */
    private static array $drivers = [
        'db' => DbAuthDriver::class,
        'file' => FileAuthDriver::class,
    ];

    /**
     * Register a custom auth driver.
     *
     * @param class-string<AuthDriverInterface> $class
     */
    public static function registerDriver(string $name, string $class): void
    {
        self::$drivers[$name] = $class;
    }

    /**
     * Register a new user.
     *
     * @param array<string, mixed> $data Must contain 'email' and 'password'.
     * @return array<string, mixed>|null The created user, or null on failure.
     */
    public static function register(array $data): ?array
    {
        $data['password'] = password_hash($data['password'], PASSWORD_BCRYPT);
        $data['role'] ??= 'user';

        return self::driver()->create($data);
    }

    /**
     * Attempt to authenticate a user with email and password.
     */
    public static function attempt(string $email, string $password): bool
    {
        $user = self::driver()->findByEmail($email);

        if ($user === null || ! password_verify($password, $user['password'])) {
            return false;
        }

        self::startSession();
        session_regenerate_id(true);
        $_SESSION['_user_id'] = $user['id'];
        self::$currentUser = $user;

        return true;
    }

    /**
     * Log out the current user.
     */
    public static function logout(): void
    {
        self::startSession();
        unset($_SESSION['_user_id']);
        self::$currentUser = false;
        session_regenerate_id(true);
    }

    /**
     * Get the currently authenticated user.
     *
     * @return array<string, mixed>|null
     */
    public static function user(): ?array
    {
        if (self::$currentUser !== false) {
            return self::$currentUser;
        }

        self::startSession();

        if (! isset($_SESSION['_user_id'])) {
            self::$currentUser = null;

            return null;
        }

        self::$currentUser = self::driver()->findById($_SESSION['_user_id']);

        return self::$currentUser;
    }

    /**
     * Check if a user is authenticated.
     */
    public static function check(): bool
    {
        return self::user() !== null;
    }

    /**
     * Check if the authenticated user has the given role.
     */
    public static function is(string $role): bool
    {
        $user = self::user();

        return $user !== null && ($user['role'] ?? '') === $role;
    }

    /**
     * Ensure a session is started.
     */
    private static function startSession(): void
    {
        if (session_status() === PHP_SESSION_NONE) {
            session_start();
        }
    }

    /**
     * Resolve the configured auth driver.
     */
    private static function driver(): AuthDriverInterface
    {
        if (self::$driverInstance !== null) {
            return self::$driverInstance;
        }

        $name = Config::get('auth.datasource', 'db');

        if (! isset(self::$drivers[$name])) {
            throw new \RuntimeException("Auth driver '$name' not found.");
        }

        $class = self::$drivers[$name];
        self::$driverInstance = new $class();

        return self::$driverInstance;
    }
}
