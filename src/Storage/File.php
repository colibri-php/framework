<?php

declare(strict_types=1);

namespace Colibri\Storage;

use Colibri\Support\Str;
use Colibri\Config;

class File
{
    /**
     * Upload a file to a disk.
     *
     * @param array{name: string, type: string, tmp_name: string, error: int, size: int} $file From $_FILES
     * @param string $destination Subdirectory within the disk
     * @return string|null The relative path within the disk, or null on failure.
     */
    public static function upload(array $file, string $destination = '', ?string $disk = null): ?string
    {
        if ($file['error'] !== UPLOAD_ERR_OK) {
            return null;
        }

        $maxSize = Config::get('upload.max_size', 10 * 1024 * 1024);
        if ($file['size'] > $maxSize) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);
        $mimeType = $finfo->file($file['tmp_name']);

        $allowedTypes = Config::get('upload.allowed_types', []);
        if ($allowedTypes !== [] && ! in_array($mimeType, $allowedTypes, true)) {
            return null;
        }

        $extension = Str::lower(pathinfo($file['name'], PATHINFO_EXTENSION));
        $blockedExtensions = Config::get('upload.blocked_extensions', []);
        if (in_array($extension, $blockedExtensions, true)) {
            return null;
        }

        $uniqueName = bin2hex(random_bytes(16)) . '.' . $extension;

        $uploadDir = self::diskPath($disk);
        if ($destination !== '') {
            $uploadDir .= DIRECTORY_SEPARATOR . trim($destination, '/\\');
        }

        if (! is_dir($uploadDir)) {
            mkdir($uploadDir, 0777, true);
        }

        $fullPath = $uploadDir . DIRECTORY_SEPARATOR . $uniqueName;

        if (! move_uploaded_file($file['tmp_name'], $fullPath)) {
            return null;
        }

        return $destination !== ''
            ? $destination . '/' . $uniqueName
            : $uniqueName;
    }

    /**
     * Check if a file exists on a disk.
     */
    public static function exists(string $path, ?string $disk = null): bool
    {
        return file_exists(self::resolve($path, $disk));
    }

    /**
     * Delete a file from a disk.
     */
    public static function delete(string $path, ?string $disk = null): bool
    {
        $fullPath = self::resolve($path, $disk);

        if (! file_exists($fullPath)) {
            return false;
        }

        return unlink($fullPath);
    }

    /**
     * Get the size of a file in bytes.
     */
    public static function size(string $path, ?string $disk = null): int
    {
        $fullPath = self::resolve($path, $disk);

        return file_exists($fullPath) ? (int) filesize($fullPath) : 0;
    }

    /**
     * Get the extension of a file.
     */
    public static function extension(string $path): string
    {
        return Str::lower(pathinfo($path, PATHINFO_EXTENSION));
    }

    /**
     * Get the MIME type of a file.
     */
    public static function mimeType(string $path, ?string $disk = null): ?string
    {
        $fullPath = self::resolve($path, $disk);

        if (! file_exists($fullPath)) {
            return null;
        }

        $finfo = new \finfo(FILEINFO_MIME_TYPE);

        return $finfo->file($fullPath) ?: null;
    }

    /**
     * Get the public URL for a file.
     *
     * Public disk: direct URL (/uploads/path).
     * Private disk: secured route (/files/path).
     */
    public static function url(string $path, ?string $disk = null): string
    {
        $diskName = $disk ?? Config::get('storage.default', 'private');

        if ($diskName === 'public') {
            return '/uploads/' . ltrim($path, '/');
        }

        return '/files/' . ltrim($path, '/');
    }

    /**
     * Serve a file with proper headers (for private disk).
     */
    public static function serve(string $path, ?string $disk = null): never
    {
        $fullPath = self::resolve($path, $disk);

        if (! file_exists($fullPath)) {
            http_response_code(404);
            echo 'File not found.';
            exit;
        }

        $mime = self::mimeType($path, $disk) ?? 'application/octet-stream';
        $size = (int) filesize($fullPath);
        $filename = basename($path);

        http_response_code(200);
        header("Content-Type: $mime");
        header("Content-Length: $size");
        header("Content-Disposition: inline; filename=\"$filename\"");
        header('Cache-Control: private, max-age=3600');

        readfile($fullPath);
        exit;
    }

    /**
     * Resolve a relative path to an absolute path on a disk.
     */
    private static function resolve(string $path, ?string $disk = null): string
    {
        return self::diskPath($disk) . DIRECTORY_SEPARATOR . ltrim($path, '/\\');
    }

    /**
     * Get the absolute path for a disk.
     */
    private static function diskPath(?string $disk = null): string
    {
        $diskName = $disk ?? Config::get('storage.default', 'private');
        $disks = Config::get('storage.disks', []);

        if (! isset($disks[$diskName])) {
            throw new \RuntimeException("Storage disk '$diskName' not found.");
        }

        return base_path($disks[$diskName]['path']);
    }
}
