<?php

declare(strict_types=1);

use Colibri\App;
use Colibri\Config;
use Colibri\Database\DB;
use Colibri\View\Lang;
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

    $langRef = new ReflectionClass(Lang::class);
    $langRef->setStaticPropertyValue('currentLocale', null);
    $langRef->setStaticPropertyValue('translations', []);

    App::boot(dirname(__DIR__, 2));

    if (session_status() === PHP_SESSION_NONE) {
        @session_start();
    }
    unset($_SESSION['_locale']);
});

// --- Lang::locale ---

test('locale returns default locale', function () {
    expect(Lang::locale())->toBe('en');
});

test('setLocale changes the active locale', function () {
    Lang::setLocale('fr');

    expect(Lang::locale())->toBe('fr');
});

test('isLocale checks against active locale', function () {
    expect(Lang::isLocale('en'))->toBeTrue();
    expect(Lang::isLocale('fr'))->toBeFalse();
});

test('locales returns configured prefixes', function () {
    $locales = Lang::locales();

    expect($locales)->toHaveKey('en');
    expect($locales)->toHaveKey('fr');
    expect($locales['en'])->toBe('/');
    expect($locales['fr'])->toBe('/fr');
});

// --- Translations ---

test('translate returns translation for active locale', function () {
    expect(Lang::translate('welcome'))->toBe('Welcome to Colibri');
});

test('translate supports dot notation', function () {
    expect(Lang::translate('cart.empty'))->toBe('Your cart is empty');
});

test('translate supports interpolation', function () {
    expect(Lang::translate('cart.items', ['count' => 3]))->toBe('3 item(s) in your cart');
});

test('translate falls back to fallback locale', function () {
    Lang::setLocale('fr');

    // fr.json has 'welcome' so this should return French
    expect(Lang::translate('welcome'))->toBe('Bienvenue sur Colibri');
});

test('translate returns key if not found in any locale', function () {
    expect(Lang::translate('nonexistent.key'))->toBe('nonexistent.key');
});

test('translate works with explicit locale parameter', function () {
    expect(Lang::translate('welcome', locale: 'fr'))->toBe('Bienvenue sur Colibri');
    expect(Lang::translate('welcome', locale: 'en'))->toBe('Welcome to Colibri');
});

// --- t() helper ---

test('t helper translates', function () {
    expect(t('welcome'))->toBe('Welcome to Colibri');
});

test('t helper with interpolation', function () {
    expect(t('cart.items', ['count' => 5]))->toBe('5 item(s) in your cart');
});

// --- url() helper ---

test('url with path builds URL for active locale', function () {
    expect(url(path: 'about'))->toBe('/about');
});

test('url with path and locale adds prefix', function () {
    expect(url(path: 'about', locale: 'fr'))->toBe('/fr/about');
});

test('url with query params', function () {
    $result = url(path: 'search', query: ['q' => 'test']);

    expect($result)->toBe('/search?q=test');
});

test('url with anchor', function () {
    expect(url(path: 'about', anchor: 'team'))->toBe('/about#team');
});

test('url with everything combined', function () {
    $result = url(path: 'faq', locale: 'fr', query: ['cat' => 'billing'], anchor: 'refunds');

    expect($result)->toBe('/fr/faq?cat=billing#refunds');
});

test('url with no args returns current path', function () {
    $_SERVER['REQUEST_URI'] = '/about';

    expect(url())->toBe('/about');
});

test('url switches locale for current page', function () {
    $_SERVER['REQUEST_URI'] = '/about';

    expect(url(locale: 'fr'))->toBe('/fr/about');
});
