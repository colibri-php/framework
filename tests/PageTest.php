<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\View\Page;
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
});

// --- Basic properties ---

test('title falls back to app name', function () {
    $page = new Page();

    expect($page->title)->toBe('Colibri');
});

test('title can be set', function () {
    $page = new Page();
    $page->title = 'My Page';

    expect($page->title)->toBe('My Page');
});

// --- OpenGraph ---

test('og sets OpenGraph properties', function () {
    $page = new Page();
    $page->og('title', 'Article Title');
    $page->og('image', '/images/hero.jpg');

    $meta = $page->renderMeta();

    expect($meta)->toContain('property="og:title" content="Article Title"');
    expect($meta)->toContain('property="og:image" content="/images/hero.jpg"');
});

test('og inherits title and description from page', function () {
    $page = new Page();
    $page->title = 'My Page';
    $page->description = 'A description';
    $page->og('image', '/hero.jpg');

    $meta = $page->renderMeta();

    expect($meta)->toContain('og:title" content="My Page"');
    expect($meta)->toContain('og:description" content="A description"');
});

test('explicit og overrides defaults', function () {
    $page = new Page();
    $page->title = 'Page Title';
    $page->og('title', 'Custom OG Title');

    $meta = $page->renderMeta();

    expect($meta)->toContain('og:title" content="Custom OG Title"');
    expect($meta)->not->toContain('og:title" content="Page Title"');
});

// --- Twitter Cards ---

test('twitter sets Twitter Card properties', function () {
    $page = new Page();
    $page->twitter('card', 'summary_large_image');
    $page->twitter('site', '@colibri');

    $meta = $page->renderMeta();

    expect($meta)->toContain('name="twitter:card" content="summary_large_image"');
    expect($meta)->toContain('name="twitter:site" content="@colibri"');
});

test('twitter defaults to summary card', function () {
    $page = new Page();
    $page->og('title', 'Test'); // Triggers twitter defaults

    $meta = $page->renderMeta();

    expect($meta)->toContain('twitter:card" content="summary"');
});

test('no twitter tags when no og or twitter data', function () {
    $page = new Page();

    $meta = $page->renderMeta();

    expect($meta)->not->toContain('twitter:');
});

// --- Canonical ---

test('canonical sets the canonical URL', function () {
    $page = new Page();
    $page->canonical('/about');

    $meta = $page->renderMeta();

    expect($meta)->toContain('<link rel="canonical" href="/about">');
});

test('no canonical when not set', function () {
    $page = new Page();

    $meta = $page->renderMeta();

    expect($meta)->not->toContain('canonical');
});

// --- Description ---

test('description renders as meta tag', function () {
    $page = new Page();
    $page->description = 'A great page';

    $meta = $page->renderMeta();

    expect($meta)->toContain('name="description" content="A great page"');
});

test('no description meta when empty', function () {
    $page = new Page();

    $meta = $page->renderMeta();

    expect($meta)->not->toContain('name="description"');
});

// --- Custom meta ---

test('custom meta renders', function () {
    $page = new Page();
    $page->meta['robots'] = 'noindex, nofollow';

    $meta = $page->renderMeta();

    expect($meta)->toContain('name="robots" content="noindex, nofollow"');
});

// --- XSS protection ---

test('renderMeta escapes HTML', function () {
    $page = new Page();
    $page->description = '<script>alert("xss")</script>';

    $meta = $page->renderMeta();

    expect($meta)->not->toContain('<script>');
    expect($meta)->toContain('&lt;script&gt;');
});

// --- Fluent interface ---

test('og and twitter are chainable', function () {
    $page = new Page();
    $result = $page->og('title', 'Test')->twitter('card', 'summary')->canonical('/test');

    expect($result)->toBeInstanceOf(Page::class);
});
