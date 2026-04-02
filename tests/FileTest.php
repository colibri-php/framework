<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Storage\File;
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

    // Create a test file in storage/uploads/
    $this->testDir = base_path('storage/uploads/test-files');
    if (! is_dir($this->testDir)) {
        mkdir($this->testDir, 0777, true);
    }

    $this->testFile = $this->testDir . DIRECTORY_SEPARATOR . 'sample.txt';
    file_put_contents($this->testFile, 'Hello Colibri');
});

afterEach(function () {
    if (isset($this->testFile) && file_exists($this->testFile)) {
        unlink($this->testFile);
    }
    if (isset($this->testDir) && is_dir($this->testDir)) {
        @rmdir($this->testDir);
    }
});

test('exists returns true for existing file', function () {
    expect(File::exists('test-files/sample.txt'))->toBeTrue();
});

test('exists returns false for missing file', function () {
    expect(File::exists('nonexistent.txt'))->toBeFalse();
});

test('size returns file size in bytes', function () {
    expect(File::size('test-files/sample.txt'))->toBe(13); // "Hello Colibri"
});

test('size returns 0 for missing file', function () {
    expect(File::size('nonexistent.txt'))->toBe(0);
});

test('extension returns the file extension', function () {
    expect(File::extension('test-files/sample.txt'))->toBe('txt');
    expect(File::extension('image.JPG'))->toBe('jpg');
});

test('mimeType returns the MIME type', function () {
    expect(File::mimeType('test-files/sample.txt'))->toBe('text/plain');
});

test('mimeType returns null for missing file', function () {
    expect(File::mimeType('nonexistent.txt'))->toBeNull();
});

test('delete removes a file', function () {
    $path = 'test-files/to-delete.txt';
    file_put_contents(base_path('storage/uploads/' . $path), 'temp');

    expect(File::delete($path))->toBeTrue();
    expect(File::exists($path))->toBeFalse();
});

test('delete returns false for missing file', function () {
    expect(File::delete('nonexistent.txt'))->toBeFalse();
});

test('upload rejects file with upload error', function () {
    $file = [
        'name' => 'test.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => '/tmp/fake',
        'error' => UPLOAD_ERR_NO_FILE,
        'size' => 0,
    ];

    expect(File::upload($file))->toBeNull();
});

test('upload rejects file exceeding max size', function () {
    $file = [
        'name' => 'large.jpg',
        'type' => 'image/jpeg',
        'tmp_name' => $this->testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 999 * 1024 * 1024, // 999 MB
    ];

    expect(File::upload($file))->toBeNull();
});

test('upload rejects blocked extensions', function () {
    $file = [
        'name' => 'malicious.php',
        'type' => 'text/plain',
        'tmp_name' => $this->testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 100,
    ];

    expect(File::upload($file))->toBeNull();
});

test('upload rejects phtml extension', function () {
    $file = [
        'name' => 'sneaky.phtml',
        'type' => 'text/plain',
        'tmp_name' => $this->testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 100,
    ];

    expect(File::upload($file))->toBeNull();
});

test('upload rejects MIME type not in allowed list', function () {
    $file = [
        'name' => 'script.js',
        'type' => 'application/javascript',
        'tmp_name' => $this->testFile,
        'error' => UPLOAD_ERR_OK,
        'size' => 100,
    ];

    expect(File::upload($file))->toBeNull();
});

test('storage .htaccess exists and blocks PHP', function () {
    $htaccess = base_path('storage/.htaccess');
    expect(file_exists($htaccess))->toBeTrue();

    $content = file_get_contents($htaccess);
    expect($content)->toContain('php_flag engine off');
    expect($content)->toContain('Deny from all');
});

// --- Disks ---

test('url returns direct URL for public disk', function () {
    expect(File::url('photo.jpg', 'public'))->toBe('/uploads/photo.jpg');
});

test('url returns secured route for private disk', function () {
    expect(File::url('doc.pdf', 'private'))->toBe('/files/doc.pdf');
});

test('url defaults to private disk', function () {
    expect(File::url('doc.pdf'))->toBe('/files/doc.pdf');
});

test('exists works with explicit disk', function () {
    expect(File::exists('test-files/sample.txt', 'private'))->toBeTrue();
    expect(File::exists('test-files/sample.txt', 'public'))->toBeFalse();
});

test('unknown disk throws exception', function () {
    File::exists('test.txt', 'nonexistent');
})->throws(RuntimeException::class, "Storage disk 'nonexistent' not found.");
