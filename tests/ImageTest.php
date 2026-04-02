<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Support\Image;
use Colibri\View\View;

beforeEach(function () {
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);

    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $dbRef = new ReflectionClass(DB::class);
    $dbRef->setStaticPropertyValue('medoo', null);

    $viewRef = new ReflectionClass(View::class);
    $viewRef->setStaticPropertyValue('engine', null);

    App::boot(dirname(__DIR__, 2));

    // Create a test image (200x100 red PNG)
    $this->testDir = base_path('storage/cache/test-images');
    if (! is_dir($this->testDir)) {
        mkdir($this->testDir, 0777, true);
    }

    $img = imagecreatetruecolor(200, 100);
    $red = imagecolorallocate($img, 255, 0, 0);
    imagefill($img, 0, 0, $red);
    $this->testImage = $this->testDir . DIRECTORY_SEPARATOR . 'test.png';
    imagepng($img, $this->testImage);
    imagedestroy($img);
});

afterEach(function () {
    // Clean up test images
    if (isset($this->testDir) && is_dir($this->testDir)) {
        $files = glob($this->testDir . '/*') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
        rmdir($this->testDir);
    }

    // Clean up cache
    $cacheDir = base_path('public/cache/images');
    if (is_dir($cacheDir)) {
        $files = glob($cacheDir . '/*.png') ?: [];
        foreach ($files as $file) {
            unlink($file);
        }
    }
});

/** Convert URL back to absolute path for assertions */
function urlToPath(string $url): string
{
    return base_path('public' . str_replace('/', DIRECTORY_SEPARATOR, $url));
}

test('resize creates a resized image', function () {
    $url = Image::resize($this->testImage, 100);

    expect($url)->not->toBeNull();
    expect($url)->toStartWith('/cache/images/');

    $path = urlToPath($url);
    expect(file_exists($path))->toBeTrue();

    [$w, $h] = getimagesize($path);
    expect($w)->toBe(100);
    expect($h)->toBe(50);
});

test('resize with explicit height', function () {
    $url = Image::resize($this->testImage, 100, 80);

    expect($url)->not->toBeNull();

    [$w, $h] = getimagesize(urlToPath($url));
    expect($w)->toBe(100);
    expect($h)->toBe(80);
});

test('resize uses cache on second call', function () {
    $first = Image::resize($this->testImage, 100);
    $second = Image::resize($this->testImage, 100);

    expect($second)->toBe($first);
});

test('crop creates a center-cropped image', function () {
    $url = Image::crop($this->testImage, 50, 50);

    expect($url)->not->toBeNull();

    [$w, $h] = getimagesize(urlToPath($url));
    expect($w)->toBe(50);
    expect($h)->toBe(50);
});

test('thumbnail creates a square thumbnail', function () {
    $url = Image::thumbnail($this->testImage, 80);

    expect($url)->not->toBeNull();

    [$w, $h] = getimagesize(urlToPath($url));
    expect($w)->toBe(80);
    expect($h)->toBe(80);
});

test('resize returns null for nonexistent file', function () {
    expect(Image::resize('nonexistent.png', 100))->toBeNull();
});
