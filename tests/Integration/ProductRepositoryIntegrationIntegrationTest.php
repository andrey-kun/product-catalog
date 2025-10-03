<?php

declare(strict_types=1);

namespace Tests\Integration;

use App\Entity\Product;
use App\Repository\ProductRepository;
use DateTime;
use Doctrine\ORM\EntityManagerInterface;
use Tests\TestDataFactory;

class ProductRepositoryIntegrationIntegrationTest extends IntegrationTestCase
{
    private ProductRepository $productRepository;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->entityManager = $this->testDbManager->getTestEntityManager();
        $this->productRepository = $this->entityManager->getRepository(Product::class);
    }

    public function testSearchProducts(): void
    {
        $product1 = TestDataFactory::createIphoneProduct();
        $product2 = TestDataFactory::createSamsungProduct();
        $product3 = TestDataFactory::createMacbookProduct();

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->persist($product3);
        $this->entityManager->flush();

        $results = $this->productRepository->search('iPhone');
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15 Pro', $results[0]->name);

        $results = $this->productRepository->search('laptop');
        $this->assertCount(1, $results);
        $this->assertEquals('MacBook Pro', $results[0]->name);

        $results = $this->productRepository->search($product1->inn);
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15 Pro', $results[0]->name);

        $results = $this->productRepository->search($product2->barcode);
        $this->assertCount(1, $results);
        $this->assertEquals('Samsung Galaxy S24', $results[0]->name);

        $results = $this->productRepository->search('nonexistent');
        $this->assertCount(0, $results);
    }

    public function testFindAllWithFilters(): void
    {
        $category1 = TestDataFactory::createElectronicsCategory();
        $category2 = TestDataFactory::createBooksCategory();

        $this->entityManager->persist($category1);
        $this->entityManager->persist($category2);
        $this->entityManager->flush();

        $product1 = TestDataFactory::createProduct('iPhone 15', null, null, 'Smartphone');
        $product1->categories->add($category1);

        $product2 = TestDataFactory::createProduct('Programming Book', null, null, 'Technical book');
        $product2->categories->add($category2);

        $product3 = TestDataFactory::createProduct('Samsung Galaxy', null, null, 'Another smartphone');
        $product3->categories->add($category1);

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->persist($product3);
        $this->entityManager->flush();

        $results = $this->productRepository->findAllWithFilters();
        $this->assertCount(3, $results);

        $results = $this->productRepository->findAllWithFilters('iPhone');
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15', $results[0]->name);

        $results = $this->productRepository->findAllWithFilters('', $category1->id);
        $this->assertCount(2, $results);

        $productNames = array_map(fn($p) => $p->name, $results);
        $this->assertContains('iPhone 15', $productNames);
        $this->assertContains('Samsung Galaxy', $productNames);
        $this->assertNotContains('Programming Book', $productNames);

        $results = $this->productRepository->findAllWithFilters('', null, '1234567890');
        $this->assertCount(1, $results);
        $this->assertEquals('iPhone 15', $results[0]->name);

        $results = $this->productRepository->findAllWithFilters('', null, '', '0987654321098');
        $this->assertCount(1, $results);
        $this->assertEquals('Programming Book', $results[0]->name);

        $results = $this->productRepository->findAllWithFilters('smartphone', $category1->id);
        $this->assertCount(2, $results);
    }

    public function testExistsByInn(): void
    {
        $product = TestDataFactory::createProduct('Test Product', null, null, 'Test description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertTrue($this->productRepository->existsByInn($product->inn));

        $this->assertFalse($this->productRepository->existsByInn('9999999999'));

        $this->assertFalse($this->productRepository->existsByInn($product->inn, $product->id));
        $this->assertTrue($this->productRepository->existsByInn($product->inn, 999));
    }

    public function testExistsByBarcode(): void
    {
        $product = TestDataFactory::createProduct('Test Product', null, null, 'Test description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertTrue($this->productRepository->existsByBarcode($product->barcode));

        $this->assertFalse($this->productRepository->existsByBarcode('9999999999999'));

        $this->assertFalse($this->productRepository->existsByBarcode($product->barcode, $product->id));
        $this->assertTrue($this->productRepository->existsByBarcode($product->barcode, 999));
    }

    public function testSaveProduct(): void
    {
        $product = TestDataFactory::createProduct('New Product', null, null, 'New description');

        $this->productRepository->save($product);

        $this->assertNotNull($product->id);
        $this->assertInstanceOf(DateTime::class, $product->createdAt);
        $this->assertInstanceOf(DateTime::class, $product->updatedAt);

        $savedProduct = $this->productRepository->find($product->id);
        $this->assertNotNull($savedProduct);
        $this->assertEquals('New Product', $savedProduct->name);
    }

    public function testRemoveProduct(): void
    {
        $product = TestDataFactory::createProduct('To Delete', null, null, 'Will be deleted');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->id;

        $this->productRepository->remove($product);

        $deletedProduct = $this->productRepository->find($productId);
        $this->assertNull($deletedProduct);
    }

    public function testFindProduct(): void
    {
        $product = TestDataFactory::createProduct('Find Me', null, null, 'Find this product');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $foundProduct = $this->productRepository->find($product->id);
        $this->assertNotNull($foundProduct);
        $this->assertEquals('Find Me', $foundProduct->name);

        $notFoundProduct = $this->productRepository->find(999);
        $this->assertNull($notFoundProduct);
    }

    public function testFindAllProducts(): void
    {
        $product1 = TestDataFactory::createProduct('Product 1', null, null, 'Description 1');
        $product2 = TestDataFactory::createProduct('Product 2', null, null, 'Description 2');
        $product3 = TestDataFactory::createProduct('Product 3', null, null, 'Description 3');

        $this->entityManager->persist($product1);
        $this->entityManager->persist($product2);
        $this->entityManager->persist($product3);
        $this->entityManager->flush();

        $allProducts = $this->productRepository->findAll();
        $this->assertCount(3, $allProducts);

        $productNames = array_map(fn($p) => $p->name, $allProducts);
        $this->assertContains('Product 1', $productNames);
        $this->assertContains('Product 2', $productNames);
        $this->assertContains('Product 3', $productNames);
    }
}
