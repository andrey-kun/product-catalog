<?php

declare(strict_types=1);

namespace App\Search;

use App\Client\ElasticsearchClient;
use App\Client\ElasticsearchClientInterface;
use App\Contract\Elasticsearch\ElasticsearchRequest;
use App\Contract\ProductSearchFilters;
use App\Contract\SearchResult;
use App\Contract\SearchServiceInterface;
use App\Exception\ExternalServiceException;
use App\Factory\ElasticsearchSearchRequestFactory;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class ElasticsearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly ElasticsearchClientInterface $client,
        private readonly string                       $indexName
    )
    {
    }

    public static function create(string $host, string $port, string $indexName, LoggerInterface $logger = new NullLogger()): self
    {
        $client = ElasticsearchClient::create($host, $port, $logger);
        return new self($client, $indexName);
    }

    public function search(ProductSearchFilters $filters): array
    {
        $searchRequest = ElasticsearchSearchRequestFactory::fromProductSearchFilters($filters);

        $request = new ElasticsearchRequest(
            index: $this->indexName,
            operation: 'search',
            body: $searchRequest->toArray()
        );

        $response = $this->client->execute($request);

        if (!$response->success) {
            throw new ExternalServiceException("Elasticsearch search failed: {$response->error}");
        }

        return $this->mapSearchResults($response->data);
    }

    public function getById(int $id): ?SearchResult
    {
        $request = new ElasticsearchRequest(
            index: $this->indexName,
            operation: 'get',
            id: (string)$id
        );

        $response = $this->client->execute($request);

        if (!$response->success) {
            return null;
        }

        $data = $response->data;
        return $data['found'] ?? false ? $this->mapDocumentToSearchResult($data['_source']) : null;
    }

    public function index(array $productData): void
    {
        $request = new ElasticsearchRequest(
            index: $this->indexName,
            operation: 'index',
            id: (string)$productData['id'],
            body: $this->prepareDocumentData($productData)
        );

        $response = $this->client->execute($request);

        if (!$response->success) {
            throw new ExternalServiceException("Elasticsearch indexing failed: {$response->error}");
        }
    }

    public function update(array $productData): void
    {
        $this->index($productData);
    }

    public function remove(int $id): void
    {
        $request = new ElasticsearchRequest(
            index: $this->indexName,
            operation: 'delete',
            id: (string)$id
        );

        $response = $this->client->execute($request);

        if (!$response->success) {
            throw new ExternalServiceException("Elasticsearch removal failed: {$response->error}");
        }
    }

    public function isAvailable(): bool
    {
        return $this->client->isAvailable();
    }

    private function mapSearchResults(array $response): array
    {
        $results = [];
        foreach (($response['hits']['hits'] ?? []) as $hit) {
            $source = $hit['_source'];
            $results[] = $this->mapDocumentToSearchResult($source);
        }
        return $results;
    }

    private function mapDocumentToSearchResult(array $source): SearchResult
    {
        return new SearchResult(
            id: (int)$source['id'],
            name: $source['name'],
            inn: $source['inn'],
            barcode: $source['barcode'],
            description: $source['description'] ?? null,
            categories: $source['categories'] ?? [],
            score: $source['_score'] ?? null
        );
    }

    private function prepareDocumentData(array $productData): array
    {
        return [
            'id' => $productData['id'],
            'name' => $productData['name'],
            'inn' => $productData['inn'],
            'barcode' => $productData['barcode'],
            'description' => $productData['description'] ?? null,
            'categories' => array_map(static fn(array $cat) => ['id' => $cat['id'], 'name' => $cat['name']], $productData['categories'] ?? []),
        ];
    }
}
