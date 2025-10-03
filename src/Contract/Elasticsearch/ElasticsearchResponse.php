<?php

declare(strict_types=1);

namespace App\Contract\Elasticsearch;

final readonly class ElasticsearchResponse
{
    public function __construct(
        public bool    $success,
        public ?array  $data = null,
        public ?string $error = null,
        public int     $statusCode = 200
    )
    {
    }

    public static function success(array $data, int $statusCode = 200): self
    {
        return new self(true, $data, null, $statusCode);
    }

    public static function error(string $error, int $statusCode = 500): self
    {
        return new self(false, null, $error, $statusCode);
    }
}
