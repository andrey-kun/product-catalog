<?php

declare(strict_types=1);

namespace App\Contract\Elasticsearch;

final readonly class ElasticsearchSearchRequest
{
    public function __construct(
        public array  $query = [],
        public int    $from = 0,
        public int    $size = 20,
        public ?array $sort = null,
        public ?array $aggs = null,
        public ?array $highlight = null
    )
    {
    }

    public function toArray(): array
    {
        $body = [
            'query' => $this->query,
            'from' => $this->from,
            'size' => $this->size,
        ];

        if ($this->sort !== null) {
            $body['sort'] = $this->sort;
        }

        if ($this->aggs !== null) {
            $body['aggs'] = $this->aggs;
        }

        if ($this->highlight !== null) {
            $body['highlight'] = $this->highlight;
        }

        return $body;
    }
}
