<?php

declare(strict_types=1);

namespace Colibri\Support;

class Pagination
{
    /**
     * @param list<array<string, mixed>> $items
     */
    public function __construct(
        private readonly array $items,
        private readonly int $total,
        private readonly int $currentPage,
        private readonly int $perPage,
        private readonly int $lastPage,
    ) {}

    /**
     * Create from a DB::paginate() result array.
     *
     * @param array{data: list<array<string, mixed>>, total: int, page: int, per_page: int, last_page: int} $result
     */
    public static function fromArray(array $result): self
    {
        return new self(
            items: $result['data'],
            total: $result['total'],
            currentPage: $result['page'],
            perPage: $result['per_page'],
            lastPage: $result['last_page'],
        );
    }

    /**
     * @return list<array<string, mixed>>
     */
    public function items(): array
    {
        return $this->items;
    }

    public function currentPage(): int
    {
        return $this->currentPage;
    }

    public function totalPages(): int
    {
        return $this->lastPage;
    }

    public function total(): int
    {
        return $this->total;
    }

    public function perPage(): int
    {
        return $this->perPage;
    }

    public function hasPages(): bool
    {
        return $this->lastPage > 1;
    }

    public function hasPrev(): bool
    {
        return $this->currentPage > 1;
    }

    public function hasNext(): bool
    {
        return $this->currentPage < $this->lastPage;
    }

    public function prevPage(): int
    {
        return max(1, $this->currentPage - 1);
    }

    public function nextPage(): int
    {
        return min($this->lastPage, $this->currentPage + 1);
    }

    /**
     * Generate a list of page numbers with ellipsis.
     *
     * @return list<int|string> e.g. [1, 2, '...', 5, 6, 7, '...', 10]
     */
    public function pages(int $window = 2): array
    {
        if ($this->lastPage <= 1) {
            return [];
        }

        if ($this->lastPage <= ($window * 2 + 5)) {
            return range(1, $this->lastPage);
        }

        $pages = [];
        $pages[] = 1;

        $start = max(2, $this->currentPage - $window);
        $end = min($this->lastPage - 1, $this->currentPage + $window);

        if ($start > 2) {
            $pages[] = '...';
        }

        for ($i = $start; $i <= $end; $i++) {
            $pages[] = $i;
        }

        if ($end < $this->lastPage - 1) {
            $pages[] = '...';
        }

        $pages[] = $this->lastPage;

        return $pages;
    }
}
