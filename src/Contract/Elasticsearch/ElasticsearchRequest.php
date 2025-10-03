<?php

declare(strict_types=1);

namespace App\Contract\Elasticsearch;

final readonly class ElasticsearchRequest
{
    public function __construct(
        public string  $index,
        public string  $operation,
        public array   $body = [],
        public ?string $id = null
    )
    {
    }
}
