<?php

declare(strict_types=1);

namespace Colibri\Support;

class Image
{
    /**
     * Resize an image to fit within the given dimensions.
     *
     * @return string|null Path to the cached resized image, or null on failure.
     */
    public static function resize(string $path, int $width, ?int $height = null): ?string
    {
        $sourcePath = self::resolveSource($path);
        if ($sourcePath === null) {
            return null;
        }

        $cacheKey = md5($path . "resize_{$width}x" . ($height ?? 'auto'));
        $cached = self::cachePath($cacheKey, $sourcePath);

        if (file_exists($cached)) {
            return self::toUrl($cached);
        }

        $image = self::load($sourcePath);
        if ($image === null) {
            return null;
        }

        [$origW, $origH] = [imagesx($image), imagesy($image)];

        if ($height === null) {
            $height = (int) round($origH * ($width / $origW));
        }

        $resized = imagecreatetruecolor($width, $height);
        self::preserveTransparency($resized);
        imagecopyresampled($resized, $image, 0, 0, 0, 0, $width, $height, $origW, $origH);

        self::save($resized, $cached, $sourcePath);
        imagedestroy($image);
        imagedestroy($resized);

        return self::toUrl($cached);
    }

    /**
     * Crop an image from the center.
     *
     * @return string|null Path to the cached cropped image, or null on failure.
     */
    public static function crop(string $path, int $width, int $height): ?string
    {
        $sourcePath = self::resolveSource($path);
        if ($sourcePath === null) {
            return null;
        }

        $cacheKey = md5($path . "crop_{$width}x{$height}");
        $cached = self::cachePath($cacheKey, $sourcePath);

        if (file_exists($cached)) {
            return self::toUrl($cached);
        }

        $image = self::load($sourcePath);
        if ($image === null) {
            return null;
        }

        [$origW, $origH] = [imagesx($image), imagesy($image)];

        // Calculate crop position (center)
        $srcX = max(0, (int) (($origW - $width) / 2));
        $srcY = max(0, (int) (($origH - $height) / 2));
        $srcW = min($width, $origW);
        $srcH = min($height, $origH);

        $cropped = imagecreatetruecolor($width, $height);
        self::preserveTransparency($cropped);
        imagecopyresampled($cropped, $image, 0, 0, $srcX, $srcY, $width, $height, $srcW, $srcH);

        self::save($cropped, $cached, $sourcePath);
        imagedestroy($image);
        imagedestroy($cropped);

        return self::toUrl($cached);
    }

    /**
     * Generate a square thumbnail (resize + crop).
     *
     * @return string|null Path to the cached thumbnail, or null on failure.
     */
    public static function thumbnail(string $path, int $size): ?string
    {
        $sourcePath = self::resolveSource($path);
        if ($sourcePath === null) {
            return null;
        }

        $cacheKey = md5($path . "thumb_{$size}");
        $cached = self::cachePath($cacheKey, $sourcePath);

        if (file_exists($cached)) {
            return self::toUrl($cached);
        }

        $image = self::load($sourcePath);
        if ($image === null) {
            return null;
        }

        [$origW, $origH] = [imagesx($image), imagesy($image)];

        // Scale to fit the smallest dimension, then crop center
        if ($origW > $origH) {
            $newH = $size;
            $newW = (int) round($origW * ($size / $origH));
        } else {
            $newW = $size;
            $newH = (int) round($origH * ($size / $origW));
        }

        $scaled = imagecreatetruecolor($newW, $newH);
        self::preserveTransparency($scaled);
        imagecopyresampled($scaled, $image, 0, 0, 0, 0, $newW, $newH, $origW, $origH);

        // Crop center to exact square
        $thumb = imagecreatetruecolor($size, $size);
        self::preserveTransparency($thumb);
        $srcX = (int) (($newW - $size) / 2);
        $srcY = (int) (($newH - $size) / 2);
        imagecopy($thumb, $scaled, 0, 0, $srcX, $srcY, $size, $size);

        self::save($thumb, $cached, $sourcePath);
        imagedestroy($image);
        imagedestroy($scaled);
        imagedestroy($thumb);

        return self::toUrl($cached);
    }

    /**
     * Resolve the source image path.
     */
    private static function resolveSource(string $path): ?string
    {
        // Absolute path
        if (file_exists($path)) {
            return $path;
        }

        // Relative to storage/uploads
        $uploaded = base_path('storage/uploads' . DIRECTORY_SEPARATOR . $path);
        if (file_exists($uploaded)) {
            return $uploaded;
        }

        // Relative to public
        $public = base_path('public' . DIRECTORY_SEPARATOR . $path);
        if (file_exists($public)) {
            return $public;
        }

        return null;
    }

    /**
     * Convert an absolute file path to a URL path relative to public/.
     */
    private static function toUrl(string $absolutePath): string
    {
        $publicPath = base_path('public');

        return '/' . ltrim(str_replace([$publicPath, '\\'], ['', '/'], $absolutePath), '/');
    }

    /**
     * Get the cache file path for a processed image.
     */
    private static function cachePath(string $key, string $sourcePath): string
    {
        $dir = base_path('public/cache/images');
        if (! is_dir($dir)) {
            mkdir($dir, 0777, true);
        }

        $ext = Str::lower(pathinfo($sourcePath, PATHINFO_EXTENSION));

        return $dir . DIRECTORY_SEPARATOR . $key . '.' . $ext;
    }

    /**
     * Load an image from file using GD.
     *
     * @return \GdImage|null
     */
    private static function load(string $path): ?\GdImage
    {
        $mime = mime_content_type($path);

        return match ($mime) {
            'image/jpeg' => imagecreatefromjpeg($path) ?: null,
            'image/png' => imagecreatefrompng($path) ?: null,
            'image/gif' => imagecreatefromgif($path) ?: null,
            'image/webp' => function_exists('imagecreatefromwebp') ? (imagecreatefromwebp($path) ?: null) : null,
            default => null,
        };
    }

    /**
     * Save a GD image to file, matching the source format.
     */
    private static function save(\GdImage $image, string $path, string $sourcePath): void
    {
        $mime = mime_content_type($sourcePath);

        match ($mime) {
            'image/jpeg' => imagejpeg($image, $path, 85),
            'image/png' => imagepng($image, $path, 6),
            'image/gif' => imagegif($image, $path),
            'image/webp' => imagewebp($image, $path, 85),
            default => imagejpeg($image, $path, 85),
        };
    }

    /**
     * Preserve transparency for PNG and GIF images.
     */
    private static function preserveTransparency(\GdImage $image): void
    {
        imagealphablending($image, false);
        imagesavealpha($image, true);
        $transparent = imagecolorallocatealpha($image, 0, 0, 0, 127);
        imagefilledrectangle($image, 0, 0, imagesx($image), imagesy($image), $transparent);
    }
}
