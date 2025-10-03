<?php

declare(strict_types=1);

namespace App\Client;

use App\Contract\Elasticsearch\ElasticsearchRequest;
use App\Contract\Elasticsearch\ElasticsearchResponse;

interface ElasticsearchClientInterface
{
    public function execute(ElasticsearchRequest $request): ElasticsearchResponse;
    public function isAvailable(): bool;
}
