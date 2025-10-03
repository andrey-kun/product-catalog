<?php

declare(strict_types=1);

namespace App\Client;

use App\Contract\Elasticsearch\ElasticsearchRequest;
use App\Contract\Elasticsearch\ElasticsearchResponse;
use Elastic\Elasticsearch\Client;
use Elastic\Elasticsearch\ClientBuilder;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

final class ElasticsearchClient implements ElasticsearchClientInterface
{
    public function __construct(
        private readonly Client          $client,
        private readonly LoggerInterface $logger = new NullLogger()
    )
    {
    }

    public static function create(string $host, string $port, LoggerInterface $logger = new NullLogger()): self
    {
        $client = ClientBuilder::create()
            ->setHosts(["$host:$port"])
            ->build();

        return new self($client, $logger);
    }

    public function execute(ElasticsearchRequest $request): ElasticsearchResponse
    {
        try {
            $params = $this->buildParams($request);
            $response = $this->client->{$request->operation}($params);

            return ElasticsearchResponse::success($response->asArray());

        } catch (Throwable $e) {
            $this->logger->error('Elasticsearch operation failed', [
                'operation' => $request->operation,
                'index' => $request->index,
                'error' => $e->getMessage()
            ]);

            return ElasticsearchResponse::error($e->getMessage());
        }
    }

    public function isAvailable(): bool
    {
        try {
            return $this->client->ping()->getStatusCode() === 200;
        } catch (Throwable $e) {
            $this->logger->error('Elasticsearch ping failed', ['error' => $e->getMessage()]);
            return false;
        }
    }

    private function buildParams(ElasticsearchRequest $request): array
    {
        $params = ['index' => $request->index];

        if ($request->id !== null) {
            $params['id'] = $request->id;
        }

        if (!empty($request->body)) {
            $params['body'] = $request->body;
        }

        return $params;
    }
}
