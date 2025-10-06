<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\Diactoros\ServerRequest;
use App\Http\ExceptionHandlingRouter;

class ProductSearchTest extends FeatureTestCase
{
    private ExceptionHandlingRouter $router;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->container->get(ExceptionHandlingRouter::class);
        $this->entityManager = $this->testDbManager->getTestEntityManager();
    }

    public function testSearchProductsByQuery(): void
    {
        $this->createSearchTestProducts();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['query' => 'iPhone']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);

        $productNames = array_column($body['data'], 'name');
        $this->assertContains('iPhone 15 Pro', $productNames);
        $this->assertContains('iPhone 14', $productNames);
    }

    public function testSearchProductsByCategory(): void
    {
        $categoryId = $this->createSearchTestProductsWithCategories();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['category_id' => $categoryId]);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);

        $productNames = array_column($body['data'], 'name');
        $this->assertContains('iPhone 15 Pro', $productNames);
        $this->assertContains('Samsung Galaxy', $productNames);
        $this->assertNotContains('Programming Book', $productNames);
    }

    public function testSearchProductsByInn(): void
    {
        $this->createSearchTestProducts();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['inn' => '1234567890']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('iPhone 15 Pro', $body['data'][0]['name']);
    }

    public function testSearchProductsByBarcode(): void
    {
        $this->createSearchTestProducts();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['barcode' => '0987654321098']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('Samsung Galaxy', $body['data'][0]['name']);
    }

    public function testSearchProductsWithPagination(): void
    {
        $this->createManyTestProducts();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['limit' => '5', 'offset' => '0']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertLessThanOrEqual(5, count($body['data']));

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['limit' => '5', 'offset' => '5']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
    }

    public function testSearchProductsWithMultipleFilters(): void
    {
        $categoryId = $this->createSearchTestProductsWithCategories();

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['query' => 'phone', 'category_id' => $categoryId, 'limit' => '10']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);

        $productNames = array_column($body['data'], 'name');
        $this->assertContains('iPhone 15 Pro', $productNames);
        $this->assertContains('Samsung Galaxy', $productNames);
    }

    public function testSearchProductsNoResults(): void
    {
        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['query' => 'nonexistentproduct']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertCount(0, $body['data']);
    }

    public function testSearchProductsWithInvalidPagination(): void
    {
        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['limit' => '0', 'offset' => '-1']);

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
    }

    public function testSearchProductsPerformance(): void
    {
        $this->createManyTestProducts();

        $startTime = microtime(true);

        $request = (new ServerRequest([], [], '/api/v1/products', 'GET'))
            ->withQueryParams(['query' => 'Product', 'limit' => '20']);

        $response = $this->router->dispatch($request);

        $endTime = microtime(true);
        $executionTime = $endTime - $startTime;

        $this->assertEquals(200, $response->getStatusCode());

        $this->assertLessThan(1.0, $executionTime, 'Search should complete within 1 second');

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
    }

    private function createSearchTestProducts(): void
    {
        $products = [
            new Product('iPhone 15 Pro', '1234567890', '1234567890123', 'Latest iPhone with Pro features'),
            new Product('iPhone 14', '2345678901', '2345678901234', 'Previous generation iPhone'),
            new Product('Samsung Galaxy', '0987654321', '0987654321098', 'Samsung flagship smartphone'),
            new Product('MacBook Pro', '1122334455', '1122334455667', 'Apple professional laptop'),
            new Product('Programming Book', '5566778899', '5566778899001', 'Learn programming fundamentals'),
        ];

        foreach ($products as $product) {
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
    }

    private function createSearchTestProductsWithCategories(): int
    {
        $electronics = new Category('Electronics');
        $books = new Category('Books');

        $this->entityManager->persist($electronics);
        $this->entityManager->persist($books);
        $this->entityManager->flush();

        $iphone = new Product('iPhone 15 Pro', '1234567890', '1234567890123', 'Latest iPhone');
        $iphone->categories->add($electronics);

        $samsung = new Product('Samsung Galaxy', '0987654321', '0987654321098', 'Samsung phone');
        $samsung->categories->add($electronics);

        $book = new Product('Programming Book', '5566778899', '5566778899001', 'Learn programming');
        $book->categories->add($books);

        $this->entityManager->persist($iphone);
        $this->entityManager->persist($samsung);
        $this->entityManager->persist($book);
        $this->entityManager->flush();

        return $electronics->id;
    }

    private function createManyTestProducts(): void
    {
        $products = [];

        for ($i = 1; $i <= 50; $i++) {
            $products[] = new Product(
                "Product {$i}",
                str_pad((string)$i, 10, '0', STR_PAD_LEFT),
                str_pad((string)$i, 13, '0', STR_PAD_LEFT),
                "Description for product {$i}"
            );
        }

        foreach ($products as $product) {
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
    }
}
