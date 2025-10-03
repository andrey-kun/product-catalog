<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\ProductSearchFilters;
use App\Contract\SearchResult;
use App\Contract\SearchServiceInterface;
use App\Exception\ExternalServiceException;
use Psr\Log\LoggerInterface;
use Throwable;
use function get_class;

final class SearchService implements SearchServiceInterface
{
    public function __construct(
        private readonly SearchServiceInterface $primarySearchService,
        private readonly SearchServiceInterface $fallbackSearchService,
        private readonly LoggerInterface        $logger
    )
    {
    }

    public function search(ProductSearchFilters $filters): array
    {
        if ($this->primarySearchService->isAvailable()) {
            try {
                $this->logger->debug('Using primary search service', [
                    'service' => get_class($this->primarySearchService)
                ]);

                return $this->primarySearchService->search($filters);

            } catch (ExternalServiceException $e) {
                $this->logger->warning('Primary search service failed, falling back', [
                    'error' => $e->getMessage(),
                    'fallback_service' => get_class($this->fallbackSearchService)
                ]);
            }
        } else {
            $this->logger->info('Primary search service not available, using fallback', [
                'fallback_service' => get_class($this->fallbackSearchService)
            ]);
        }

        try {
            return $this->fallbackSearchService->search($filters);
        } catch (Throwable $e) {
            $this->logger->error('Both search services failed', [
                'primary_error' => $e->getMessage(),
                'fallback_error' => $e->getMessage()
            ]);

            throw new ExternalServiceException("Search failed: {$e->getMessage()}");
        }
    }

    public function getById(int $id): ?SearchResult
    {
        if ($this->primarySearchService->isAvailable()) {
            try {
                $result = $this->primarySearchService->getById($id);
                if ($result !== null) {
                    return $result;
                }
            } catch (ExternalServiceException $e) {
                $this->logger->warning('Primary search service failed for getById, falling back', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            return $this->fallbackSearchService->getById($id);
        } catch (Throwable $e) {
            $this->logger->error('Both search services failed for getById', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);

            return null;
        }
    }

    public function index(array $productData): void
    {
        if ($this->primarySearchService->isAvailable()) {
            try {
                $this->primarySearchService->index($productData);
                $this->logger->debug('Product indexed in primary service', [
                    'id' => $productData['id'] ?? 'unknown'
                ]);
            } catch (ExternalServiceException $e) {
                $this->logger->warning('Failed to index in primary service', [
                    'id' => $productData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            $this->fallbackSearchService->index($productData);
        } catch (Throwable $e) {
            $this->logger->error('Failed to index in fallback service', [
                'id' => $productData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function update(array $productData): void
    {
        if ($this->primarySearchService->isAvailable()) {
            try {
                $this->primarySearchService->update($productData);
                $this->logger->debug('Product updated in primary service', [
                    'id' => $productData['id'] ?? 'unknown'
                ]);
            } catch (ExternalServiceException $e) {
                $this->logger->warning('Failed to update in primary service', [
                    'id' => $productData['id'] ?? 'unknown',
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            $this->fallbackSearchService->update($productData);
        } catch (Throwable $e) {
            $this->logger->error('Failed to update in fallback service', [
                'id' => $productData['id'] ?? 'unknown',
                'error' => $e->getMessage()
            ]);
        }
    }

    public function remove(int $id): void
    {
        if ($this->primarySearchService->isAvailable()) {
            try {
                $this->primarySearchService->remove($id);
                $this->logger->debug('Product removed from primary service', ['id' => $id]);
            } catch (ExternalServiceException $e) {
                $this->logger->warning('Failed to remove from primary service', [
                    'id' => $id,
                    'error' => $e->getMessage()
                ]);
            }
        }

        try {
            $this->fallbackSearchService->remove($id);
        } catch (Throwable $e) {
            $this->logger->error('Failed to remove from fallback service', [
                'id' => $id,
                'error' => $e->getMessage()
            ]);
        }
    }

    public function isAvailable(): bool
    {
        return $this->primarySearchService->isAvailable() || $this->fallbackSearchService->isAvailable();
    }
}
