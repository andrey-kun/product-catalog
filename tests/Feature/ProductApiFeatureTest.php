<?php

declare(strict_types=1);

namespace Tests\Feature;

use App\Entity\Category;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Stream;
use App\Http\ExceptionHandlingRouter;

class ProductApiFeatureTest extends FeatureTestCase
{
    private ExceptionHandlingRouter $router;
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();

        $this->router = $this->container->get(ExceptionHandlingRouter::class);
        $this->entityManager = $this->testDbManager->getTestEntityManager();
    }

    public function testGetProductsList(): void
    {
        $this->createTestProducts();

        $request = new ServerRequest(
            [],
            [],
            '/api/v1/products',
            'GET'
        );

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertGreaterThan(0, count($body['data']));
    }

    public function testGetProductsWithFilters(): void
    {
        $category = new Category('Electronics');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $product = new Product('iPhone 15', '1234567890', '1234567890123', 'Latest iPhone');
        $product->categories->add($category);
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $request = new ServerRequest(
            [],
            [],
            '/api/v1/products?query=iPhone&category_id=' . $category->id,
            'GET'
        );

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertIsArray($body['data']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('iPhone 15', $body['data'][0]['name']);
    }

    public function testGetProductById(): void
    {
        $product = new Product('Test Product', '1234567890', '1234567890123', 'Test description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $request = new ServerRequest([], [], '/api/v1/products/' . $product->id, 'GET');

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Test Product', $body['data']['name']);
        $this->assertEquals('1234567890', $body['data']['inn']);
        $this->assertEquals('1234567890123', $body['data']['barcode']);
    }

    public function testGetProductByIdNotFound(): void
    {
        $request = new ServerRequest([], [], '/api/v1/products/999', 'GET');

        $response = $this->router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testCreateProduct(): void
    {
        $category = new Category('Electronics');
        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $productData = [
            'name' => 'New Product',
            'inn' => '5555555555',
            'barcode' => '5555555555555',
            'description' => 'New product description',
            'category_ids' => [$category->id]
        ];

        $request = new ServerRequest([], [], '/api/v1/products', 'POST');
        $request = $request->withBody(new Stream('php://temp', 'w+'));
        $request->getBody()->write(json_encode($productData));
        $request->getBody()->rewind();

        $response = $this->router->dispatch($request);

        $this->assertEquals(201, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('New Product', $body['data']['name']);
        $this->assertEquals('5555555555', $body['data']['inn']);
        $this->assertEquals('5555555555555', $body['data']['barcode']);
        $this->assertArrayHasKey('categories', $body['data']);
        $this->assertCount(1, $body['data']['categories']);

        $savedProduct = $this->entityManager->getRepository(Product::class)->find($body['data']['id']);
        $this->assertNotNull($savedProduct);
        $this->assertEquals('New Product', $savedProduct->name);
    }

    public function testCreateProductWithInvalidData(): void
    {
        $request = new ServerRequest([], [], '/api/v1/products', 'POST');

        $response = $this->router->dispatch($request);

        $this->assertEquals(422, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testCreateProductWithDuplicateInn(): void
    {
        $product1 = new Product('Product 1', '4444444444', '4444444444444', 'Description 1');
        $this->entityManager->persist($product1);
        $this->entityManager->flush();

        $productData = [
            'name' => 'Product 2',
            'inn' => '4444444444', // Duplicate INN
            'barcode' => '4444444444445',
            'description' => 'Description 2'
        ];

        $request = new ServerRequest([], [], '/api/v1/products', 'POST');
        $request = $request->withBody(new Stream('php://temp', 'w+'));
        $request->getBody()->write(json_encode($productData));
        $request->getBody()->rewind();

        $response = $this->router->dispatch($request);

        $this->assertEquals(409, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testUpdateProduct(): void
    {
        $product = new Product('Original Name', '3333333333', '3333333333333', 'Original description');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $updateData = [
            'name' => 'Updated Name',
            'description' => 'Updated description'
        ];

        $request = new ServerRequest([], [], '/api/v1/products/' . $product->id, 'PUT');
        $request = $request->withBody(new Stream('php://temp', 'w+'));
        $request->getBody()->write(json_encode($updateData));
        $request->getBody()->rewind();

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Updated Name', $body['data']['name']);
        $this->assertEquals('Updated description', $body['data']['description']);
        $this->assertEquals('3333333333', $body['data']['inn']);

        $this->entityManager->refresh($product);
        $this->assertEquals('Updated Name', $product->name);
        $this->assertEquals('Updated description', $product->description);
    }

    public function testUpdateProductNotFound(): void
    {
        $request = new ServerRequest([], [], '/api/v1/products/999', 'PUT');

        $response = $this->router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testDeleteProduct(): void
    {
        $product = new Product('To Delete', '2222222222', '2222222222222', 'Will be deleted');
        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $productId = $product->id;

        $request = new ServerRequest([], [], '/api/v1/products/' . $productId, 'DELETE');

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Product deleted successfully', $body['data']['message']);

        $deletedProduct = $this->entityManager->getRepository(Product::class)->find($productId);
        $this->assertNull($deletedProduct);
    }

    public function testDeleteProductNotFound(): void
    {
        $request = new ServerRequest([], [], '/api/v1/products/999', 'DELETE');

        $response = $this->router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testHealthCheck(): void
    {
        $request = new ServerRequest([], [], '/health', 'GET');

        $response = $this->router->dispatch($request);

        $this->assertEquals(200, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('ok', $body['status']);
        $this->assertArrayHasKey('timestamp', $body);
        $this->assertArrayHasKey('version', $body);
    }

    public function testNotFoundRoute(): void
    {
        $request = new ServerRequest([], [], '/api/v1/nonexistent', 'GET');

        $response = $this->router->dispatch($request);

        $this->assertEquals(404, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    public function testMethodNotAllowed(): void
    {
        $request = new ServerRequest([], [], '/api/v1/products/1', 'PATCH');

        $response = $this->router->dispatch($request);

        $this->assertEquals(405, $response->getStatusCode());

        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    private function createTestProducts(): void
    {
        $products = [
            new Product('iPhone 15', '1234567890', '1234567890123', 'Latest iPhone'),
            new Product('Samsung Galaxy', '0987654321', '0987654321098', 'Samsung flagship'),
            new Product('MacBook Pro', '1122334455', '1122334455667', 'Apple laptop'),
        ];

        foreach ($products as $product) {
            $this->entityManager->persist($product);
        }

        $this->entityManager->flush();
    }
}
