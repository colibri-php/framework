<?php

declare(strict_types=1);

use Colibri\Support\Pagination;

test('items returns the data', function () {
    $p = new Pagination([['id' => 1], ['id' => 2]], 10, 1, 5, 2);

    expect($p->items())->toHaveCount(2);
});

test('currentPage returns the current page', function () {
    $p = new Pagination([], 50, 3, 10, 5);

    expect($p->currentPage())->toBe(3);
});

test('totalPages returns the last page', function () {
    $p = new Pagination([], 50, 1, 10, 5);

    expect($p->totalPages())->toBe(5);
});

test('hasPages returns true when multiple pages', function () {
    $p = new Pagination([], 50, 1, 10, 5);

    expect($p->hasPages())->toBeTrue();
});

test('hasPages returns false when single page', function () {
    $p = new Pagination([], 5, 1, 10, 1);

    expect($p->hasPages())->toBeFalse();
});

test('hasPrev and hasNext', function () {
    $first = new Pagination([], 50, 1, 10, 5);
    expect($first->hasPrev())->toBeFalse();
    expect($first->hasNext())->toBeTrue();

    $middle = new Pagination([], 50, 3, 10, 5);
    expect($middle->hasPrev())->toBeTrue();
    expect($middle->hasNext())->toBeTrue();

    $last = new Pagination([], 50, 5, 10, 5);
    expect($last->hasPrev())->toBeTrue();
    expect($last->hasNext())->toBeFalse();
});

test('prevPage and nextPage', function () {
    $p = new Pagination([], 50, 3, 10, 5);

    expect($p->prevPage())->toBe(2);
    expect($p->nextPage())->toBe(4);
});

test('prevPage clamps to 1', function () {
    $p = new Pagination([], 50, 1, 10, 5);

    expect($p->prevPage())->toBe(1);
});

test('nextPage clamps to last page', function () {
    $p = new Pagination([], 50, 5, 10, 5);

    expect($p->nextPage())->toBe(5);
});

test('pages generates list with ellipsis', function () {
    $p = new Pagination([], 200, 10, 10, 20);

    $pages = $p->pages();

    expect($pages[0])->toBe(1);
    expect($pages)->toContain('...');
    expect($pages)->toContain(10);
    expect(end($pages))->toBe(20);
});

test('pages returns all for small page count', function () {
    $p = new Pagination([], 30, 1, 10, 3);

    expect($p->pages())->toBe([1, 2, 3]);
});

test('pages returns empty for single page', function () {
    $p = new Pagination([], 5, 1, 10, 1);

    expect($p->pages())->toBe([]);
});

test('fromArray creates from DB::paginate format', function () {
    $p = Pagination::fromArray([
        'data' => [['id' => 1]],
        'total' => 50,
        'page' => 2,
        'per_page' => 10,
        'last_page' => 5,
    ]);

    expect($p->currentPage())->toBe(2);
    expect($p->total())->toBe(50);
    expect($p->items())->toHaveCount(1);
});
