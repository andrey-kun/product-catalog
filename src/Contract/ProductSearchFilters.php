<?php

declare(strict_types=1);

namespace App\Contract;

final readonly class ProductSearchFilters
{
    public function __construct(
        public string $query = '',
        public ?int $categoryId = null,
        public string $inn = '',
        public string $barcode = '',
        public int $limit = 20,
        public int $offset = 0
    )
    {
    }
}
