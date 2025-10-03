<?php

declare(strict_types=1);

namespace App\Controller;

use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Exception\ProductException;
use App\Exception\DuplicateResourceException;
use App\Exception\ExternalServiceException;
use App\Service\ProductService;
use JsonException;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ProductController extends AbstractController
{
    public function __construct(
        private readonly ProductService $productService
    ) {}

    public function listProducts(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $filters = $this->extractFilters($request);
            $limit = (int) ($request->getQueryParams()['limit'] ?? 20);
            $offset = (int) ($request->getQueryParams()['offset'] ?? 0);
            

            $useSearch = $this->shouldUseSearch($filters, $limit, $offset);
            
            if ($useSearch) {
                $products = $this->productService->search(
                    $filters['search'],
                    $filters['categoryId'],
                    $filters['inn'],
                    $filters['barcode'],
                    $limit,
                    $offset
                );
            } else {
                $products = $this->productService->getAll(
                    $filters['search'],
                    $filters['categoryId'],
                    $filters['inn'],
                    $filters['barcode']
                );
            }

            return $this->success($products);
        } catch (ProductException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function getProduct(ServerRequestInterface $request, array $args): ResponseInterface
    {
        try {
            $productId = $this->extractId($args);
            $product = $this->productService->getById($productId);

            if ($product === null) {
                return $this->notFound('Product');
            }

            return $this->success($product);
        } catch (ProductException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function createProduct(ServerRequestInterface $request): ResponseInterface
    {
        try {
            $productData = $this->parseJsonBody($request);
            $product = $this->productService->create($productData);

            return $this->success($product, 201);
        } catch (JsonException $e) {
            return $this->error('Invalid JSON data', 400);
        } catch (ValidationException $e) {
            return $this->validationError($e->getErrors(), $e->getMessage());
        } catch (DuplicateResourceException | ExternalServiceException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (ProductException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function updateProduct(ServerRequestInterface $request, array $args): ResponseInterface
    {
        try {
            $productId = $this->extractId($args);
            $productData = $this->parseJsonBody($request);
            $product = $this->productService->update($productId, $productData);

            return $this->success($product);
        } catch (JsonException $e) {
            return $this->error('Invalid JSON data', 400);
        } catch (ValidationException $e) {
            return $this->validationError($e->getErrors(), $e->getMessage());
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (DuplicateResourceException | ExternalServiceException $e) {
            return $this->error($e->getMessage(), 409);
        } catch (ProductException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    public function deleteProduct(ServerRequestInterface $request, array $args): ResponseInterface
    {
        try {
            $productId = $this->extractId($args);
            $this->productService->delete($productId);

            return $this->success(['message' => 'Product deleted successfully']);
        } catch (ResourceNotFoundException $e) {
            return $this->notFound();
        } catch (ProductException $e) {
            return $this->error($e->getMessage(), 500);
        }
    }

    private function extractFilters(ServerRequestInterface $request): array
    {
        $queryParams = $request->getQueryParams();

        return [
            'search' => $queryParams['query'] ?? $queryParams['search'] ?? '',
            'categoryId' => isset($queryParams['category_id']) ? (int)$queryParams['category_id'] : null,
            'inn' => $queryParams['inn'] ?? '',
            'barcode' => $queryParams['barcode'] ?? '',
        ];
    }

    private function shouldUseSearch(array $filters, int $limit, int $offset): bool
    {
        if (!empty($filters['search']) || !empty($filters['inn']) || !empty($filters['barcode'])) {
            return true;
        }

        if ($filters['categoryId'] !== null) {
            return true;
        }

        if ($limit !== 20 || $offset !== 0) {
            return true;
        }

        return false;
    }
}