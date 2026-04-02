<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Support\Log;
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

    $logRef = new ReflectionClass(Log::class);
    $logRef->setStaticPropertyValue('purged', false);

    App::boot(dirname(__DIR__, 2));

    // Clean today's log before each test
    $todayLog = base_path('storage/logs/' . date('Y-m-d') . '.log');
    if (file_exists($todayLog)) {
        unlink($todayLog);
    }
});

test('info writes to daily log file', function () {
    Log::info('Test message');

    $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
    expect(file_exists($file))->toBeTrue();

    $content = file_get_contents($file);
    expect($content)->toContain('INFO: Test message');
});

test('error writes with ERROR level', function () {
    Log::error('Something broke');

    $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
    $content = file_get_contents($file);
    expect($content)->toContain('ERROR: Something broke');
});

test('context is appended as JSON', function () {
    Log::info('User action', ['user_id' => 42, 'action' => 'login']);

    $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
    $content = file_get_contents($file);
    expect($content)->toContain('"user_id":42');
    expect($content)->toContain('"action":"login"');
});

test('log level filtering skips lower levels', function () {
    // Reset everything and re-boot with error-only level
    $appRef = new ReflectionClass(App::class);
    $appRef->setStaticPropertyValue('instance', null);
    $configRef = new ReflectionClass(Config::class);
    $configRef->setStaticPropertyValue('instance', null);

    $_ENV['LOG_LEVEL'] = 'error';
    App::boot(dirname(__DIR__, 2));

    Log::debug('Should not appear');
    Log::info('Should not appear');
    Log::warning('Should not appear');
    Log::error('Should appear');

    $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
    $content = file_get_contents($file);
    expect($content)->not->toContain('DEBUG:');
    expect($content)->not->toContain('INFO:');
    expect($content)->not->toContain('WARNING:');
    expect($content)->toContain('ERROR: Should appear');

    $_ENV['LOG_LEVEL'] = 'debug';
});

test('purge removes old daily log files', function () {
    $logDir = base_path('storage/logs');

    // Create fake old log files
    $old1 = $logDir . DIRECTORY_SEPARATOR . '2020-01-01.log';
    $old2 = $logDir . DIRECTORY_SEPARATOR . '2020-01-15.log';
    file_put_contents($old1, 'old');
    file_put_contents($old2, 'old');

    // Reset purge flag
    $logRef = new ReflectionClass(Log::class);
    $logRef->setStaticPropertyValue('purged', false);

    Log::info('Trigger purge');

    expect(file_exists($old1))->toBeFalse();
    expect(file_exists($old2))->toBeFalse();
});

test('multiple log entries append to same file', function () {
    Log::info('First');
    Log::warning('Second');
    Log::error('Third');

    $file = base_path('storage/logs/' . date('Y-m-d') . '.log');
    $content = file_get_contents($file);
    $lines = array_filter(explode(PHP_EOL, $content));

    expect($lines)->toHaveCount(3);
});
