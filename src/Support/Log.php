<?php

declare(strict_types=1);

namespace Colibri\Support;

use Colibri\Config;

class Log
{
    private const LEVELS = [
        'debug' => 0,
        'info' => 1,
        'warning' => 2,
        'error' => 3,
    ];

    private static bool $purged = false;

    /**
     * Log a debug message.
     *
     * @param array<string, mixed> $context
     */
    public static function debug(string $message, array $context = []): void
    {
        self::write('debug', $message, $context);
    }

    /**
     * Log an info message.
     *
     * @param array<string, mixed> $context
     */
    public static function info(string $message, array $context = []): void
    {
        self::write('info', $message, $context);
    }

    /**
     * Log a warning message.
     *
     * @param array<string, mixed> $context
     */
    public static function warning(string $message, array $context = []): void
    {
        self::write('warning', $message, $context);
    }

    /**
     * Log an error message.
     *
     * @param array<string, mixed> $context
     */
    public static function error(string $message, array $context = []): void
    {
        self::write('error', $message, $context);
    }

    /**
     * Write a log entry.
     *
     * @param array<string, mixed> $context
     */
    private static function write(string $level, string $message, array $context = []): void
    {
        $minLevel = Config::get('app.log.level', 'debug');

        if (self::LEVELS[$level] < (self::LEVELS[$minLevel] ?? 0)) {
            return;
        }

        $logDir = base_path('storage/logs');
        if (! is_dir($logDir)) {
            mkdir($logDir, 0777, true);
        }

        self::purgeOnce($logDir);

        $file = $logDir . DIRECTORY_SEPARATOR . self::filename();
        $timestamp = date('Y-m-d H:i:s');
        $levelUpper = strtoupper($level);

        $line = "[$timestamp] $levelUpper: $message";

        if ($context !== []) {
            $line .= ' ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
        }

        file_put_contents($file, $line . PHP_EOL, FILE_APPEND | LOCK_EX);
    }

    /**
     * Get the log filename based on the channel config.
     */
    private static function filename(): string
    {
        $channel = Config::get('app.log.channel', 'daily');

        return match ($channel) {
            'single' => 'colibri.log',
            default => date('Y-m-d') . '.log',
        };
    }

    /**
     * Purge old log files once per request (daily channel only).
     */
    private static function purgeOnce(string $logDir): void
    {
        if (self::$purged) {
            return;
        }

        self::$purged = true;

        $channel = Config::get('app.log.channel', 'daily');
        if ($channel !== 'daily') {
            return;
        }

        $maxFiles = (int) Config::get('app.log.max_files', 30);
        $cutoff = strtotime("-$maxFiles days");

        $files = glob($logDir . DIRECTORY_SEPARATOR . '*.log') ?: [];

        foreach ($files as $file) {
            $basename = basename($file, '.log');

            // Only purge date-named files (YYYY-MM-DD)
            if (preg_match('/^\d{4}-\d{2}-\d{2}$/', $basename)) {
                $fileDate = strtotime($basename);
                if ($fileDate !== false && $fileDate < $cutoff) {
                    unlink($file);
                }
            }
        }
    }
}
