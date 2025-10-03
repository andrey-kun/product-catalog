<?php

declare(strict_types=1);

namespace App\Contract;

readonly class SearchResult
{
    public function __construct(
        public int     $id,
        public string  $name,
        public string  $inn,
        public string  $barcode,
        public ?string $description = null,
        public array   $categories = [],
        public ?float  $score = null
    )
    {
    }
}
