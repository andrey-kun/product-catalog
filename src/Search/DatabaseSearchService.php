<?php

declare(strict_types=1);

namespace App\Search;

use App\Contract\ProductSearchFilters;
use App\Contract\SearchResult;
use App\Contract\SearchServiceInterface;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;
use Throwable;

final class DatabaseSearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly ProductRepository      $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly LoggerInterface        $logger = new NullLogger()
    )
    {
    }

    public function search(ProductSearchFilters $filters): array
    {
        try {
            $this->logger->debug('Database search fallback', [
                'query' => $filters->query,
                'category_id' => $filters->categoryId,
                'inn' => $filters->inn,
                'barcode' => $filters->barcode
            ]);

            $products = $this->productRepository->findAllWithFilters(
                $filters->query,
                $filters->categoryId,
                $filters->inn,
                $filters->barcode
            );

            $results = [];
            foreach ($products as $product) {
                $productData = $product->toArray();
                $results[] = new SearchResult(
                    id: $productData['id'],
                    name: $productData['name'],
                    inn: $productData['inn'],
                    barcode: $productData['barcode'],
                    description: $productData['description'] ?? null,
                    categories: $productData['categories'] ?? []
                );
            }

            $this->logger->debug('Database search completed', [
                'results_count' => count($results)
            ]);

            return $results;

        } catch (Throwable $e) {
            $this->logger->error('Database search failed', [
                'error' => $e->getMessage(),
                'query' => $filters->query,
                'category_id' => $filters->categoryId,
                'inn' => $filters->inn,
                'barcode' => $filters->barcode
            ]);

            return [];
        }
    }

    public function getById(int $id): ?SearchResult
    {
        try {
            $product = $this->productRepository->find($id);
            if (!$product) {
                return null;
            }

            $productData = $product->toArray();
            return new SearchResult(
                id: $productData['id'],
                name: $productData['name'],
                inn: $productData['inn'],
                barcode: $productData['barcode'],
                description: $productData['description'] ?? null,
                categories: $productData['categories'] ?? []
            );

        } catch (Throwable $e) {
            $this->logger->error('Database get by ID failed', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function index(array $productData): void
    {
        $this->logger->debug('Database index called (no-op)', ['id' => $productData['id'] ?? 'unknown']);
    }

    public function update(array $productData): void
    {
        $this->logger->debug('Database update called (no-op)', ['id' => $productData['id'] ?? 'unknown']);
    }

    public function remove(int $id): void
    {
        $this->logger->debug('Database remove called (no-op)', ['id' => $id]);
    }

    public function isAvailable(): bool
    {
        try {
            $this->entityManager->getConnection()->executeQuery('SELECT 1');
            return true;
        } catch (Throwable $e) {
            $this->logger->warning('Database not available', ['error' => $e->getMessage()]);
            return false;
        }
    }
}
