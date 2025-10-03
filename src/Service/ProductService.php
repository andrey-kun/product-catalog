<?php

declare(strict_types=1);

namespace App\Service;

use App\Contract\DaData\PartyType;
use App\Contract\InnValidatorInterface;
use App\Contract\ProductSearchFilters;
use App\Contract\SearchResult;
use App\Contract\SearchServiceInterface;
use App\Entity\Category;
use App\Entity\Product;
use App\Exception\DuplicateResourceException;
use App\Exception\ExternalServiceException;
use App\Exception\ProductException;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Repository\ProductRepository;
use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Throwable;
use function array_map;
use function array_unique;
use function in_array;
use function preg_match;
use function trim;

class ProductService
{
    private const string CACHE_KEY_INN_VALIDATION = 'inn_validation_';
    private const int CACHE_TTL = 3600;

    public function __construct(
        private readonly ProductRepository      $productRepository,
        private readonly EntityManagerInterface $entityManager,
        private readonly InnValidatorInterface  $innValidator,
        private readonly CacheInterface         $cache,
        private readonly SearchServiceInterface $searchService
    )
    {
    }

    /**
     * Get all products with optional filtering
     *
     * @throws ProductException
     */
    public function getAll(
        string $search = '',
        ?int   $categoryId = null,
        string $inn = '',
        string $barcode = ''
    ): array
    {
        try {
            $products = $this->productRepository->findAllWithFilters($search, $categoryId, $inn, $barcode);
            return array_map(static fn(Product $product) => $product->toArray(), $products);
        } catch (Throwable $e) {
            throw new ProductException("Failed to fetch products: {$e->getMessage()}");
        }
    }

    /**
     * Search products using search service (Elasticsearch with fallback)
     *
     * @throws ProductException
     */
    public function search(
        string $query = '',
        ?int   $categoryId = null,
        string $inn = '',
        string $barcode = '',
        int    $limit = 20,
        int    $offset = 0
    ): array
    {
        try {
            $filters = new ProductSearchFilters(
                query: $query,
                categoryId: $categoryId,
                inn: $inn,
                barcode: $barcode,
                limit: $limit,
                offset: $offset
            );

            $searchResults = $this->searchService->search($filters);
            return array_map(static fn(SearchResult $result) => (array)$result, $searchResults);
        } catch (Throwable $e) {
            throw new ProductException("Search failed: {$e->getMessage()}");
        }
    }

    /**
     * Get product by ID
     *
     * @throws ProductException
     */
    public function getById(int $id): ?array
    {
        try {
            $product = $this->productRepository->find($id);
            return $product ? $product->toArray() : null;
        } catch (Throwable $e) {
            throw new ProductException("Failed to fetch product: {$e->getMessage()}");
        }
    }

    /**
     * Create new product
     *
     * @throws ValidationException
     * @throws DuplicateResourceException
     * @throws ExternalServiceException
     * @throws ProductException
     */
    public function create(array $data): array
    {
        try {
            $this->validateProductData($data);
            $this->validateInn($data['inn'], PartyType::LEGAL);
            $this->checkUniqueness($data['inn'], $data['barcode']);

            $product = new Product(
                $data['name'],
                $data['inn'],
                $data['barcode'],
                $data['description'] ?? null
            );

            $this->validateProduct($product);
            $this->productRepository->save($product);

            if (!empty($data['category_ids'])) {
                $this->addCategoriesToProduct($product, $data['category_ids']);
            }

            $this->indexProduct($product);

            return $product->toArray();
        } catch (ValidationException|DuplicateResourceException|ExternalServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProductException("Failed to create product: {$e->getMessage()}");
        }
    }

    /**
     * Update existing product
     *
     * @throws ResourceNotFoundException
     * @throws ValidationException
     * @throws DuplicateResourceException
     * @throws ExternalServiceException
     * @throws ProductException
     */
    public function update(int $id, array $data): array
    {
        try {
            $product = $this->findProductOrFail($id);

            $this->updateProductFields($product, $data, $id);
            $this->validateProduct($product);
            $this->productRepository->save($product);

            if (isset($data['category_ids'])) {
                $this->updateCategoriesForProduct($product, $data['category_ids']);
            }

            $this->indexProduct($product);

            return $product->toArray();
        } catch (ResourceNotFoundException|ValidationException|DuplicateResourceException|ExternalServiceException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProductException("Failed to update product: {$e->getMessage()}");
        }
    }

    /**
     * Delete product
     *
     * @throws ResourceNotFoundException
     * @throws ProductException
     */
    public function delete(int $id): void
    {
        try {
            $product = $this->findProductOrFail($id);

            $this->searchService->remove($id);

            $this->productRepository->remove($product);
        } catch (ResourceNotFoundException $e) {
            throw $e;
        } catch (Throwable $e) {
            throw new ProductException("Failed to delete product: {$e->getMessage()}");
        }
    }

    /**
     * Find product or throw exception
     *
     * @throws ResourceNotFoundException
     */
    private function findProductOrFail(int $id): Product
    {
        $product = $this->productRepository->find($id);
        if (!$product) {
            throw new ResourceNotFoundException('Product', $id);
        }
        return $product;
    }

    /**
     * Validate product data
     *
     * @throws ValidationException
     */
    private function validateProductData(array $data): void
    {
        $errors = [];

        if (!isset($data['name']) || empty(trim($data['name']))) {
            $errors[] = 'Product name is required';
        }

        if (!isset($data['inn']) || empty(trim($data['inn']))) {
            $errors[] = 'INN is required';
        } elseif (!preg_match('/^\d{10}$|^\d{12}$/', $data['inn'])) {
            $errors[] = 'INN must be 10 or 12 digits';
        }

        if (!isset($data['barcode']) || empty(trim($data['barcode']))) {
            $errors[] = 'Barcode is required';
        } elseif (!preg_match('/^\d{13}$/', $data['barcode'])) {
            $errors[] = 'Barcode must be 13 digits (EAN-13)';
        }

        if (!empty($errors)) {
            throw new ValidationException($errors);
        }
    }

    /**
     * Validate product entity
     *
     * @throws ValidationException
     */
    private function validateProduct(Product $product): void
    {
        $errors = $product->validate();
        if (!empty($errors)) {
            throw new ValidationException($errors, 'Product validation failed');
        }
    }

    /**
     * Check INN and barcode uniqueness
     *
     * @throws DuplicateResourceException
     */
    private function checkUniqueness(string $inn, string $barcode, ?int $excludeId = null): void
    {
        if ($this->productRepository->existsByInn($inn, $excludeId)) {
            throw new DuplicateResourceException("Product with INN '{$inn}' already exists");
        }

        if ($this->productRepository->existsByBarcode($barcode, $excludeId)) {
            throw new DuplicateResourceException("Product with barcode '{$barcode}' already exists");
        }
    }

    /**
     * Update product fields
     *
     * @throws ValidationException
     * @throws DuplicateResourceException
     * @throws ExternalServiceException
     */
    private function updateProductFields(Product $product, array $data, int $id): void
    {
        if (isset($data['name'])) {
            $product->name = $data['name'];
        }

        if (isset($data['inn']) && $data['inn'] !== $product->inn) {
            $this->validateInn($data['inn'], PartyType::LEGAL);
            $this->checkUniqueness($data['inn'], $product->barcode, $id);
            $product->inn = $data['inn'];
        }

        if (isset($data['barcode']) && $data['barcode'] !== $product->barcode) {
            if (!preg_match('/^\d{13}$/', $data['barcode'])) {
                throw new ValidationException(['Barcode must be 13 digits (EAN-13)']);
            }
            $this->checkUniqueness($product->inn, $data['barcode'], $id);
            $product->barcode = $data['barcode'];
        }

        if (isset($data['description'])) {
            $product->description = $data['description'];
        }
    }

    /**
     * @throws ExternalServiceException
     */
    private function validateInn(string $inn, PartyType $partyType): void
    {
        $cacheKey = self::CACHE_KEY_INN_VALIDATION . $inn;
        $cachedResult = $this->cache->get($cacheKey);

        if ($cachedResult !== null) {
            if (!$cachedResult) {
                throw ExternalServiceException::invalidInn($inn);
            }
            return;
        }

        try {
            $result = $this->innValidator->validate($inn, $partyType);

            if (!$result->isValid) {
                $errorMessage = $result->errorMessage ?? 'INN validation failed';
                throw new ExternalServiceException("Invalid INN: {$errorMessage}");
            }

            $this->cache->set($cacheKey, true, self::CACHE_TTL);
        } catch (ExternalServiceException $e) {
            $this->cache->set($cacheKey, false, self::CACHE_TTL);
            throw $e;
        } catch (Throwable $e) {
            throw new ExternalServiceException("INN validation failed for '{$inn}': {$e->getMessage()}");
        }
    }

    /**
     * Add categories to a product
     */
    private function addCategoriesToProduct(Product $product, array $categoryIds): void
    {
        $categoryIds = array_unique($categoryIds);
        foreach ($categoryIds as $categoryId) {
            $category = $this->entityManager->find(Category::class, $categoryId);
            if ($category && !$product->categories->contains($category)) {
                $product->categories->add($category);
            }
        }
        $this->productRepository->save($product);
    }

    /**
     * Update categories for a product (replace existing ones)
     */
    private function updateCategoriesForProduct(Product $product, array $newCategoryIds): void
    {
        $newCategoryIds = array_unique($newCategoryIds);
        $currentCategoryIds = array_map(fn(Category $c) => $c->id, $product->categories->toArray());

        foreach ($product->categories as $category) {
            if (!in_array($category->id, $newCategoryIds)) {
                $product->categories->removeElement($category);
            }
        }
        foreach ($newCategoryIds as $newCategoryId) {
            if (!in_array($newCategoryId, $currentCategoryIds)) {
                $category = $this->entityManager->find(Category::class, $newCategoryId);
                if ($category) {
                    $product->categories->add($category);
                }
            }
        }
        $this->productRepository->save($product);
    }

    /**
     * Index product in search service
     */
    private function indexProduct(Product $product): void
    {
        try {
            $productData = $product->toArray();
            $this->searchService->index($productData);
        } catch (Throwable $e) {
        }
    }
}