<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\Mail\Mail;
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

    // Clean mail log
    $logFile = base_path('storage/logs/mail.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }
});

afterEach(function () {
    $logFile = base_path('storage/logs/mail.log');
    if (file_exists($logFile)) {
        unlink($logFile);
    }
});

test('send with log driver writes to mail.log', function () {
    $result = Mail::send(
        to: 'user@test.com',
        subject: 'Test Email',
        template: base_path('templates/emails/welcome.latte'),
        data: ['name' => 'Alice'],
    );

    expect($result)->toBeTrue();

    $log = file_get_contents(base_path('storage/logs/mail.log'));
    expect($log)->toContain('To: user@test.com');
    expect($log)->toContain('Subject: Test Email');
    expect($log)->toContain('Welcome, Alice!');
});

test('send with multiple recipients', function () {
    $result = Mail::send(
        to: ['user1@test.com', 'user2@test.com'],
        subject: 'Newsletter',
        template: base_path('templates/emails/welcome.latte'),
        data: ['name' => 'Everyone'],
    );

    expect($result)->toBeTrue();

    $log = file_get_contents(base_path('storage/logs/mail.log'));
    expect($log)->toContain('To: user1@test.com, user2@test.com');
});

test('send renders Latte template', function () {
    Mail::send(
        to: 'test@test.com',
        subject: 'Rendered',
        template: base_path('templates/emails/welcome.latte'),
        data: ['name' => 'Bob'],
    );

    $log = file_get_contents(base_path('storage/logs/mail.log'));
    expect($log)->toContain('Welcome, Bob!');
    expect($log)->toContain('<h1>');
});
