<?php

namespace App\Infrastructure\Elasticsearch;

use Elasticsearch\ClientBuilder;
use Exception;

class ElasticsearchClient
{
    private ?\Elasticsearch\Client $client = null;
    private array $config;

    public function __construct(array $config)
    {
        $this->config = $config;
        $this->initializeClient();
    }

    /**
     * Инициализация Elasticsearch клиента
     */
    private function initializeClient(): void
    {
        try {
            $hosts = [];
            if (isset($this->config['hosts'])) {
                $hosts = $this->config['hosts'];
            } else {
                $hosts = [
                    [
                        'host' => $this->config['host'] ?? 'localhost',
                        'port' => $this->config['port'] ?? 9200,
                        'scheme' => $this->config['scheme'] ?? 'http',
                        'user' => $this->config['username'] ?? null,
                        'pass' => $this->config['password'] ?? null
                    ]
                ];
            }

            $this->client = ClientBuilder::create()
                ->setHosts($hosts)
                ->build();
        } catch (Exception $e) {
            throw new Exception('Failed to initialize Elasticsearch client: ' . $e->getMessage());
        }
    }

    /**
     * Проверка соединения с Elasticsearch
     */
    public function ping(): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }
            $response = $this->client->ping();
            return isset($response['cluster_name']);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Создание индекса
     */
    public function createIndex(string $indexName, array $mapping = []): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }

            // Проверяем существование индекса
            if ($this->indexExists($indexName)) {
                return true; // Индекс уже существует
            }

            // Создаем маппинг по умолчанию если не указан
            if (empty($mapping)) {
                $mapping = [
                    'mappings' => [
                        'properties' => [
                            'id' => ['type' => 'keyword'],
                            'name' => ['type' => 'text', 'analyzer' => 'standard'],
                            'inn' => ['type' => 'keyword'],
                            'barcode' => ['type' => 'keyword'],
                            'description' => ['type' => 'text', 'analyzer' => 'standard'],
                            'categories' => ['type' => 'keyword'],
                            'created_at' => ['type' => 'date'],
                            'updated_at' => ['type' => 'date']
                        ]
                    ]
                ];
            }

            $params = [
                'index' => $indexName,
                'body' => $mapping
            ];

            $response = $this->client->indices()->create($params);
            return isset($response['acknowledged']) && $response['acknowledged'];
        } catch (Exception $e) {
            throw new Exception('Failed to create index: ' . $e->getMessage());
        }
    }

    /**
     * Проверка существования индекса
     */
    public function indexExists(string $indexName): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }
            return $this->client->indices()->exists(['index' => $indexName]);
        } catch (Exception $e) {
            return false;
        }
    }

    /**
     * Добавление документа в индекс
     */
    public function indexDocument(string $indexName, string $id, array $document): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }

            $params = [
                'index' => $indexName,
                'id' => $id,
                'body' => $document
            ];

            $response = $this->client->index($params);
            return isset($response['result']) && $response['result'] === 'created';
        } catch (Exception $e) {
            throw new Exception('Failed to index document: ' . $e->getMessage());
        }
    }

    /**
     * Обновление документа в индексе
     */
    public function updateDocument(string $indexName, string $id, array $document): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }

            $params = [
                'index' => $indexName,
                'id' => $id,
                'body' => ['doc' => $document]
            ];

            $response = $this->client->update($params);
            return isset($response['result']) && in_array($response['result'], ['updated', 'created']);
        } catch (Exception $e) {
            throw new Exception('Failed to update document: ' . $e->getMessage());
        }
    }

    /**
     * Удаление документа из индекса
     */
    public function deleteDocument(string $indexName, string $id): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }

            $params = [
                'index' => $indexName,
                'id' => $id
            ];

            $response = $this->client->delete($params);
            return isset($response['result']) && $response['result'] === 'deleted';
        } catch (Exception $e) {
            throw new Exception('Failed to delete document: ' . $e->getMessage());
        }
    }

    /**
     * Поиск документов
     */
    public function search(string $indexName, array $query): array
    {
        try {
            if ($this->client === null) {
                return [];
            }

            $params = [
                'index' => $indexName,
                'body' => $query
            ];

            $response = $this->client->search($params);
            return [
                'hits' => $response['hits']['hits'] ?? [],
                'total' => $response['hits']['total']['value'] ?? 0
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to search documents: ' . $e->getMessage());
        }
    }

    /**
     * Получение документа по ID
     */
    public function getDocument(string $indexName, string $id): ?array
    {
        try {
            if ($this->client === null) {
                return null;
            }

            $params = [
                'index' => $indexName,
                'id' => $id
            ];

            $response = $this->client->get($params);
            return $response['_source'] ?? null;
        } catch (Exception $e) {
            return null;
        }
    }

    /**
     * Получение клиента для прямого использования
     */
    public function getClient(): ?\Elasticsearch\Client
    {
        return $this->client;
    }

    /**
     * Удаление индекса
     */
    public function deleteIndex(string $indexName): bool
    {
        try {
            if ($this->client === null) {
                return false;
            }

            if (!$this->indexExists($indexName)) {
                return true; // Индекс уже удален
            }

            $params = [
                'index' => $indexName
            ];

            $response = $this->client->indices()->delete($params);
            return isset($response['acknowledged']) && $response['acknowledged'];
        } catch (Exception $e) {
            throw new Exception('Failed to delete index: ' . $e->getMessage());
        }
    }
}