<?php

declare(strict_types=1);

use Colibri\Support\Flash;

beforeEach(function () {
    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    $_SESSION = [];
});

// --- Flash messages ---

test('set stores a flash message', function () {
    Flash::set('success', 'Saved!');

    expect($_SESSION['_flash'])->toHaveCount(1);
    expect($_SESSION['_flash'][0]['type'])->toBe('success');
    expect($_SESSION['_flash'][0]['message'])->toBe('Saved!');
});

test('get returns and removes the message', function () {
    Flash::set('success', 'Saved!');

    $message = Flash::get('success');

    expect($message)->toBe('Saved!');
    expect(Flash::has('success'))->toBeFalse();
});

test('get returns null when no message', function () {
    expect(Flash::get('success'))->toBeNull();
});

test('has checks without removing', function () {
    Flash::set('error', 'Failed!');

    expect(Flash::has('error'))->toBeTrue();
    expect(Flash::has('error'))->toBeTrue(); // Still there
});

test('has returns false when no message', function () {
    expect(Flash::has('info'))->toBeFalse();
});

test('all returns all messages and clears', function () {
    Flash::set('success', 'Saved!');
    Flash::set('error', 'Failed!');

    $all = Flash::all();

    expect($all)->toBe(['success' => 'Saved!', 'error' => 'Failed!']);
    expect(Flash::all())->toBe([]);
});

// --- Old input ---

test('setOldInput stores input values', function () {
    Flash::setOldInput(['email' => 'alice@test.com', 'name' => 'Alice']);

    expect(Flash::getOldInput('email'))->toBe('alice@test.com');
    expect(Flash::getOldInput('name'))->toBe('Alice');
});

test('getOldInput returns default when missing', function () {
    expect(Flash::getOldInput('email', 'default'))->toBe('default');
});

test('clearOldInput removes all old input', function () {
    Flash::setOldInput(['email' => 'alice@test.com']);
    Flash::clearOldInput();

    expect(Flash::getOldInput('email'))->toBeNull();
});

// --- Helpers ---

test('flash() helper returns all messages', function () {
    Flash::set('success', 'Done!');
    Flash::set('info', 'Note');

    $all = flash();

    expect($all)->toBe(['success' => 'Done!', 'info' => 'Note']);
});

test('flash(type) helper returns single message', function () {
    Flash::set('success', 'Done!');

    expect(flash('success'))->toBe('Done!');
});

test('old() helper returns old input', function () {
    Flash::setOldInput(['email' => 'alice@test.com']);

    expect(old('email'))->toBe('alice@test.com');
    expect(old('missing', 'default'))->toBe('default');
});
