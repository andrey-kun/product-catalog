<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Contract\InnValidationResult;
use App\Contract\InnValidatorInterface;
use App\Contract\SearchServiceInterface;
use App\Entity\Product;
use App\Exception\DuplicateResourceException;
use App\Exception\ResourceNotFoundException;
use App\Repository\ProductRepository;
use App\Service\ProductService;
use Doctrine\ORM\EntityManagerInterface;
use Psr\SimpleCache\CacheInterface;
use Tests\TestDataFactory;

class ProductServiceIntegrationIntegrationTest extends IntegrationTestCase
{
    private ProductService $productService;
    private ProductRepository $productRepository;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $innValidatorMock = $this->createMock(InnValidatorInterface::class);
        $innValidatorMock->method('validate')->willReturn(new InnValidationResult(true, 'Test Company'));

        $this->container->set(InnValidatorInterface::class, $innValidatorMock);

        $this->entityManager = $this->testDbManager->getTestEntityManager();
        $this->productRepository = $this->entityManager->getRepository(Product::class);

        $this->productService = new ProductService(
            $this->productRepository,
            $this->entityManager,
            $innValidatorMock,
            $this->createMock(CacheInterface::class),
            $this->createMock(SearchServiceInterface::class)
        );
    }

    public function testCreateProductWithCategories(): void
    {
        $category1 = TestDataFactory::createElectronicsCategory();
        $category2 = TestDataFactory::createCategory('Smartphones');

        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->flush();

        $productData = [
            'name' => 'iPhone 15 Pro',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
            'description' => 'Latest iPhone model',
            'category_ids' => [$category1->id, $category2->id]
        ];

        $result = $this->productService->create($productData);

        $this->assertIsArray($result);
        $this->assertEquals('iPhone 15 Pro', $result['name']);
        $this->assertEquals('1234567890', $result['inn']);
        $this->assertEquals('1234567890123', $result['barcode']);
        $this->assertArrayHasKey('categories', $result);
        $this->assertCount(2, $result['categories']);

        // Verify product was saved to database
        $savedProduct = $this->productRepository->find($result['id']);
        $this->assertNotNull($savedProduct);
        $this->assertEquals('iPhone 15 Pro', $savedProduct->name);
        $this->assertCount(2, $savedProduct->categories);
    }

    public function testUpdateProductCategories(): void
    {
        $category1 = TestDataFactory::createElectronicsCategory();
        $category2 = TestDataFactory::createCategory('Smartphones');
        $category3 = TestDataFactory::createCategory('Accessories');

        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->persist($category3);
        $this->entityManager->flush();

        $product = TestDataFactory::createProduct('Test Product', null, null, 'Test description');
        $product->categories->add($category1);
        $product->categories->add($category2);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $updateData = [
            'name' => 'Updated Product',
            'category_ids' => [$category2->id, $category3->id]
        ];

        $result = $this->productService->update($product->id, $updateData);

        $this->assertEquals('Updated Product', $result['name']);
        $this->assertCount(2, $result['categories']);

        $this->entityManager->refresh($product);
        $this->assertEquals('Updated Product', $product->name);
        $this->assertCount(2, $product->categories);

        $categoryIds = $product->categories->map(fn($cat) => $cat->id)->toArray();
        $this->assertContains($category2->id, $categoryIds);
        $this->assertContains($category3->id, $categoryIds);
        $this->assertNotContains($category1->id, $categoryIds);
    }

    public function testGetAllProductsWithFilters(): void
    {
        $category1 = TestDataFactory::createElectronicsCategory();
        $category2 = TestDataFactory::createBooksCategory();

        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->flush();

        $product1 = TestDataFactory::createProduct('iPhone 15', '1234567890', '1234567890123', 'Smartphone');
        $product1->categories->add($category1);

        $product2 = TestDataFactory::createProduct('Programming Book', '0987654321', '0987654321098', 'Technical book');
        $product2->categories->add($category2);

        $product3 = TestDataFactory::createProduct('Samsung Galaxy', '5555555555', '5555555555555', 'Another smartphone');
        $product3->categories->add($category1);

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->persist($product3);
        $this->entityManager->flush();

        $results = $this->productService->getAll('', $category1->id);
        $this->assertCount(2, $results);

        $productNames = array_column($results, 'name');
        $this->assertContains('iPhone 15', $productNames);
        $this->assertContains('Samsung Galaxy', $productNames);
        $this->assertNotContains('Programming Book', $productNames);

        $results = $this->productService->getAll('iPhone');
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15', $results[0]['name']);

        $results = $this->productService->getAll('', null, '1234567890');
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15', $results[0]['name']);

        $results = $this->productService->getAll('', null, '', '0987654321098');
        $this->assertCount(1, $results);
        $this->assertEquals('Programming Book', $results[0]['name']);
    }

    public function testDeleteProduct(): void
    {
        $product = TestDataFactory::createProduct('Test Product', null, null, 'Test description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->id;

        $this->productService->delete($productId);

        $deletedProduct = $this->productRepository->find($productId);
        $this->assertNull($deletedProduct);
    }

    public function testProductUniquenessConstraints(): void
    {
        $product1 = TestDataFactory::createProduct('Product 1', '1234567890', '1111111111111', 'Description 1');
        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $this->expectException(DuplicateResourceException::class);

        $productData = [
            'name' => 'Product 2',
            'inn' => '1234567890',
            'barcode' => '9876543210987',
            'description' => 'Description 2'
        ];

        $this->productService->create($productData);
    }

    public function testProductBarcodeUniqueness(): void
    {
        $product1 = TestDataFactory::createProduct('Product 1', '1111111111', '1234567890123', 'Description 1');
        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $this->expectException(DuplicateResourceException::class);

        $productData = [
            'name' => 'Product 2',
            'inn' => '9876543210',
            'barcode' => '1234567890123',
            'description' => 'Description 2'
        ];

        $this->productService->create($productData);
    }

    public function testUpdateProductWithDuplicateInn(): void
    {
        $product1 = TestDataFactory::createProduct('Product 1', '1234567890', '1111111111111', 'Description 1');
        $product2 = TestDataFactory::createProduct('Product 2', '0987654321', '2222222222222', 'Description 2');

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->flush();

        $this->expectException(DuplicateResourceException::class);

        $updateData = [
            'inn' => '1234567890'
        ];

        $this->productService->update($product2->id, $updateData);
    }

    /**
     * @covers \App\Service\ProductService::update
     */
    public function testUpdateNonExistentProduct(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $updateData = ['name' => 'Updated Name'];
        $this->productService->update(999, $updateData);
    }

    /**
     * @covers \App\Service\ProductService::delete
     */
    public function testDeleteNonExistentProduct(): void
    {
        $this->expectException(ResourceNotFoundException::class);

        $this->productService->delete(999);
    }
}
