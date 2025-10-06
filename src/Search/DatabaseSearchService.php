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

            $qb = $this->productRepository->createQueryBuilder('p');

            if (!empty($filters->query)) {
                $qb->andWhere('p.name LIKE :search OR p.description LIKE :search')
                    ->setParameter('search', '%' . $filters->query . '%');
            }

            if ($filters->categoryId !== null) {
                $qb->innerJoin('p.categories', 'c')
                    ->andWhere('c.id = :categoryId')
                    ->setParameter('categoryId', $filters->categoryId);
            }

            if (!empty($filters->inn)) {
                $qb->andWhere('p.inn = :inn')
                    ->setParameter('inn', $filters->inn);
            }

            if (!empty($filters->barcode)) {
                $qb->andWhere('p.barcode = :barcode')
                    ->setParameter('barcode', $filters->barcode);
            }

            $products = $qb->orderBy('p.name', 'ASC')
                ->setMaxResults($filters->limit)
                ->setFirstResult($filters->offset)
                ->getQuery()
                ->getResult();

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
