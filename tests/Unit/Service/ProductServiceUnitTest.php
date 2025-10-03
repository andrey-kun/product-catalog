<?php

declare(strict_types=1);

namespace Tests\Unit\Service;

use App\Entity\Product;
use App\Exception\DuplicateResourceException;
use App\Exception\ProductException;
use App\Exception\ResourceNotFoundException;
use App\Exception\ValidationException;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use App\Contract\InnValidatorInterface;
use App\Contract\SearchServiceInterface;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\SimpleCache\CacheInterface;
use Tests\Unit\UnitTestCase;

class ProductServiceUnitTest extends UnitTestCase
{
    private ProductService $productService;
    private MockObject|ProductRepository $productRepository;
    private MockObject|EntityManagerInterface $entityManager;
    private MockObject|InnValidatorInterface $innValidator;
    private MockObject|CacheInterface $cache;
    private MockObject|SearchServiceInterface $searchService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productRepository = $this->createMock(ProductRepository::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->innValidator = $this->createMock(InnValidatorInterface::class);
        $this->cache = $this->createMock(CacheInterface::class);
        $this->searchService = $this->createMock(SearchServiceInterface::class);

        $this->productService = new ProductService(
            $this->productRepository,
            $this->entityManager,
            $this->innValidator,
            $this->cache,
            $this->searchService
        );
    }

    /**
     * @covers \App\Service\ProductService::getAllProducts
     */
    public function testGetAllProducts(): void
    {
        $products = [
            $this->createProduct(1, 'Product 1', '1234567890', '1234567890123'),
            $this->createProduct(2, 'Product 2', '0987654321', '0987654321098'),
        ];

        $this->productRepository
            ->expects($this->once())
            ->method('findAllWithFilters')
            ->with('', null, '', '')
            ->willReturn($products);

        $result = $this->productService->getAll();

        $this->assertCount(2, $result);
        $this->assertEquals('Product 1', $result[0]['name']);
        $this->assertEquals('Product 2', $result[1]['name']);
    }

    /**
     * @covers \App\Service\ProductService::getAllProducts
     */
    public function testGetAllProductsWithFilters(): void
    {
        $products = [
            $this->createProduct(1, 'iPhone', '1234567890', '1234567890123'),
        ];

        $this->productRepository
            ->expects($this->once())
            ->method('findAllWithFilters')
            ->with('iPhone', 1, '1234567890', '1234567890123')
            ->willReturn($products);

        $result = $this->productService->getAll('iPhone', 1, '1234567890', '1234567890123');

        $this->assertCount(1, $result);
        $this->assertEquals('iPhone', $result[0]['name']);
    }

    /**
     * @covers \App\Service\ProductService::getProductById
     */
    public function testGetProductById(): void
    {
        $product = $this->createProduct(1, 'Test Product', '1234567890', '1234567890123');

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $result = $this->productService->getById(1);

        $this->assertNotNull($result);
        $this->assertEquals('Test Product', $result['name']);
    }

    /**
     * @covers \App\Service\ProductService::getProductById
     */
    public function testGetProductByIdNotFound(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $result = $this->productService->getById(999);

        $this->assertNull($result);
    }

    /**
     * @covers \App\Service\ProductService::createProduct
     */
    public function testCreateProductSuccess(): void
    {
        $data = [
            'name' => 'New Product',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
            'description' => 'Test description',
            'category_ids' => [1, 2]
        ];

        $validationResult = new \App\Contract\InnValidationResult(true, 'Test Company');
        
        $this->innValidator
            ->expects($this->once())
            ->method('validate')
            ->with('1234567890')
            ->willReturn($validationResult);

        $this->productRepository
            ->expects($this->once())
            ->method('existsByInn')
            ->with('1234567890', null)
            ->willReturn(false);

        $this->productRepository
            ->expects($this->once())
            ->method('existsByBarcode')
            ->with('1234567890123', null)
            ->willReturn(false);

        $this->productRepository
            ->expects($this->once())
            ->method('save')
            ->willThrowException(new \Exception('Database error'));

        $this->searchService
            ->expects($this->never())
            ->method('index');

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Failed to create product: Database error');

        $this->productService->create($data);
    }

    /**
     * @covers \App\Service\ProductService::createProduct
     */
    public function testCreateProductWithInvalidInn(): void
    {
        $data = [
            'name' => 'New Product',
            'inn' => 'invalid',
            'barcode' => '1234567890123',
        ];

        $this->expectException(ValidationException::class);
        $this->expectExceptionMessage('Validation failed');

        $this->productService->create($data);
    }

    /**
     * @covers \App\Service\ProductService::createProduct
     */
    public function testCreateProductWithDuplicateInn(): void
    {
        $data = [
            'name' => 'New Product',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
        ];

        $validationResult = new \App\Contract\InnValidationResult(true, 'Test Company');
        
        $this->innValidator
            ->expects($this->once())
            ->method('validate')
            ->with('1234567890')
            ->willReturn($validationResult);

        $this->productRepository
            ->expects($this->once())
            ->method('existsByInn')
            ->with('1234567890', null)
            ->willReturn(true);

        $this->expectException(DuplicateResourceException::class);
        $this->expectExceptionMessage('Product with INN \'1234567890\' already exists');

        $this->productService->create($data);
    }

    /**
     * @covers \App\Service\ProductService::updateProduct
     */
    public function testUpdateProductSuccess(): void
    {
        $product = $this->createProduct(1, 'Old Name', '1234567890', '1234567890123');
        
        $data = [
            'name' => 'New Name',
            'description' => 'New description',
        ];

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->productRepository
            ->expects($this->once())
            ->method('save');

        $this->searchService
            ->expects($this->once())
            ->method('index');

        $result = $this->productService->update(1, $data);

        $this->assertEquals('New Name', $result['name']);
        $this->assertEquals('New description', $result['description']);
    }

    /**
     * @covers \App\Service\ProductService::updateProduct
     */
    public function testUpdateProductNotFound(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Product with ID \'999\' not found');

        $this->productService->update(999, ['name' => 'New Name']);
    }

    /**
     * @covers \App\Service\ProductService::deleteProduct
     */
    public function testDeleteProductSuccess(): void
    {
        $product = $this->createProduct(1, 'Test Product', '1234567890', '1234567890123');

        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(1)
            ->willReturn($product);

        $this->searchService
            ->expects($this->once())
            ->method('remove')
            ->with(1);

        $this->productRepository
            ->expects($this->once())
            ->method('remove')
            ->with($product);

        $this->productService->delete(1);
    }

    /**
     * @covers \App\Service\ProductService::deleteProduct
     */
    public function testDeleteProductNotFound(): void
    {
        $this->productRepository
            ->expects($this->once())
            ->method('find')
            ->with(999)
            ->willReturn(null);

        $this->expectException(ResourceNotFoundException::class);
        $this->expectExceptionMessage('Product with ID \'999\' not found');

        $this->productService->delete(999);
    }

    /**
     * @covers \App\Service\ProductService::searchProducts
     */
    public function testSearchProducts(): void
    {
        $searchResult1 = new \App\Contract\SearchResult(1, 'iPhone', '1234567890', '1234567890123');
        $searchResult2 = new \App\Contract\SearchResult(2, 'Samsung', '0987654321', '0987654321098');

        $searchResults = [$searchResult1, $searchResult2];

        $this->searchService
            ->expects($this->once())
            ->method('search')
            ->willReturn($searchResults);

        $result = $this->productService->search('phone', null, '', '', 10, 0);

        $this->assertCount(2, $result);
        $this->assertEquals('iPhone', $result[0]['name']);
        $this->assertEquals('Samsung', $result[1]['name']);
    }

    /**
     * @covers \App\Service\ProductService::searchProducts
     */
    public function testSearchProductsWithException(): void
    {
        $this->searchService
            ->expects($this->once())
            ->method('search')
            ->willThrowException(new \Exception('Search failed'));

        $this->expectException(ProductException::class);
        $this->expectExceptionMessage('Search failed: Search failed');

        $this->productService->search('test');
    }

    private function createProduct(int $id, string $name, string $inn, string $barcode): Product
    {
        $product = new Product($name, $inn, $barcode, 'Test description');

        $reflection = new \ReflectionClass($product);
        $idProperty = $reflection->getProperty('id');
        $idProperty->setAccessible(true);
        $idProperty->setValue($product, $id);

        return $product;
    }
}
