<?php

declare(strict_types=1);

namespace Tests\Unit\Controller;

use App\Controller\ProductController;
use App\Service\ProductService;
use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Http\Message\ResponseInterface;
use Tests\Unit\UnitTestCase;

class ProductControllerUnitTest extends UnitTestCase
{
    private ProductController $controller;
    private MockObject|ProductService $productService;

    protected function setUp(): void
    {
        parent::setUp();
        
        $this->productService = $this->createMock(ProductService::class);
        $this->controller = new ProductController($this->productService);
    }

    /**
     * @covers \App\Controller\ProductController::listProducts
     */
    public function testListProducts(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Product 1', 'inn' => '1234567890', 'barcode' => '1234567890123'],
            ['id' => 2, 'name' => 'Product 2', 'inn' => '0987654321', 'barcode' => '0987654321098'],
        ];

        $this->productService
            ->expects($this->once())
            ->method('getAll')
            ->with('', null, '', '')
            ->willReturn($products);

        $request = new ServerRequest([], [], new Uri('/api/v1/products'));
        $response = $this->controller->listProducts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertCount(2, $body['data']);
    }

    /**
     * @covers \App\Controller\ProductController::listProducts
     */
    public function testListProductsWithFilters(): void
    {
        $products = [
            ['id' => 1, 'name' => 'iPhone', 'inn' => '1234567890', 'barcode' => '1234567890123'],
        ];

        $this->productService
            ->expects($this->once())
            ->method('search')
            ->with('iPhone', 1, '1234567890', '1234567890123', 20, 0)
            ->willReturn($products);

        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products?query=iPhone&category_id=1&inn=1234567890&barcode=1234567890123')
        );
        $request = $request->withQueryParams([
            'query' => 'iPhone',
            'category_id' => '1',
            'inn' => '1234567890',
            'barcode' => '1234567890123'
        ]);
        
        $response = $this->controller->listProducts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertCount(1, $body['data']);
        $this->assertEquals('iPhone', $body['data'][0]['name']);
    }

    /**
     * @covers \App\Controller\ProductController::listProducts
     */
    public function testListProductsWithPagination(): void
    {
        $products = [
            ['id' => 1, 'name' => 'Product 1', 'inn' => '1234567890', 'barcode' => '1234567890123'],
        ];

        $this->productService
            ->expects($this->once())
            ->method('search')
            ->with('', null, '', '', 10, 20)
            ->willReturn($products);

        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products?limit=10&offset=20')
        );
        $request = $request->withQueryParams([
            'limit' => '10',
            'offset' => '20'
        ]);
        
        $response = $this->controller->listProducts($request);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertCount(1, $body['data']);
    }

    /**
     * @covers \App\Controller\ProductController::getProduct
     */
    public function testGetProduct(): void
    {
        $product = [
            'id' => 1,
            'name' => 'Test Product',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
            'description' => 'Test description'
        ];

        $this->productService
            ->expects($this->once())
            ->method('getById')
            ->with(1)
            ->willReturn($product);

        $request = new ServerRequest([], [], new Uri('/api/v1/products/1'));
        $args = ['id' => '1'];
        
        $response = $this->controller->getProduct($request, $args);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Test Product', $body['data']['name']);
    }

    /**
     * @covers \App\Controller\ProductController::getProduct
     */
    public function testGetProductNotFound(): void
    {
        $this->productService
            ->expects($this->once())
            ->method('getById')
            ->with(999)
            ->willReturn(null);

        $request = new ServerRequest([], [], new Uri('/api/v1/products/999'));
        $args = ['id' => '999'];
        
        $response = $this->controller->getProduct($request, $args);

        $this->assertEquals(404, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
        $this->assertStringContainsString('not found', $body['message']);
    }

    /**
     * @covers \App\Controller\ProductController::createProduct
     */
    public function testCreateProduct(): void
    {
        $productData = [
            'name' => 'New Product',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
            'description' => 'Test description'
        ];

        $createdProduct = array_merge(['id' => 1], $productData);

        $this->productService
            ->expects($this->once())
            ->method('create')
            ->with($productData)
            ->willReturn($createdProduct);

        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products'),
            'POST',
            new \Laminas\Diactoros\Stream('php://temp', 'w+'),
            ['Content-Type' => 'application/json'],
            [],
            [],
            json_encode($productData)
        );
        $request->getBody()->write(json_encode($productData));
        $request->getBody()->rewind();
        
        $response = $this->controller->createProduct($request);

        $this->assertEquals(201, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('New Product', $body['data']['name']);
    }

    /**
     * @covers \App\Controller\ProductController::createProduct
     */
    public function testCreateProductWithInvalidJson(): void
    {
        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products'),
            'POST',
            new \Laminas\Diactoros\Stream('php://temp', 'w+'),
            ['Content-Type' => 'application/json'],
            [],
            [],
            '{invalid json}'
        );
        $request->getBody()->write('{invalid json}');
        $request->getBody()->rewind();
        
        $response = $this->controller->createProduct($request);

        $this->assertEquals(400, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
        $this->assertEquals('Invalid JSON data', $body['message']);
    }

    /**
     * @covers \App\Controller\ProductController::updateProduct
     */
    public function testUpdateProduct(): void
    {
        $productData = [
            'name' => 'Updated Product',
            'description' => 'Updated description'
        ];

        $updatedProduct = [
            'id' => 1,
            'name' => 'Updated Product',
            'inn' => '1234567890',
            'barcode' => '1234567890123',
            'description' => 'Updated description'
        ];

        $this->productService
            ->expects($this->once())
            ->method('update')
            ->with(1, $productData)
            ->willReturn($updatedProduct);

        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products/1'),
            'PUT',
            new \Laminas\Diactoros\Stream('php://temp', 'w+'),
            ['Content-Type' => 'application/json'],
            [],
            [],
            json_encode($productData)
        );
        $request->getBody()->write(json_encode($productData));
        $request->getBody()->rewind();
        
        $args = ['id' => '1'];
        $response = $this->controller->updateProduct($request, $args);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Updated Product', $body['data']['name']);
    }

    /**
     * @covers \App\Controller\ProductController::updateProduct
     */
    public function testUpdateProductNotFound(): void
    {
        $updateData = ['name' => 'Updated Product'];

        $this->productService
            ->expects($this->once())
            ->method('update')
            ->with(999, [])
            ->willThrowException(new \App\Exception\ResourceNotFoundException('Product', 999));

        $request = new ServerRequest(
            [],
            [],
            new Uri('/api/v1/products/999'),
            'PUT',
            'php://input',
            ['Content-Type' => 'application/json'],
            [],
            [],
            json_encode($updateData)
        );
        
        $args = ['id' => '999'];
        $response = $this->controller->updateProduct($request, $args);

        $this->assertEquals(404, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }

    /**
     * @covers \App\Controller\ProductController::deleteProduct
     */
    public function testDeleteProduct(): void
    {
        $this->productService
            ->expects($this->once())
            ->method('delete')
            ->with(1);

        $request = new ServerRequest([], [], new Uri('/api/v1/products/1'));
        $args = ['id' => '1'];
        
        $response = $this->controller->deleteProduct($request, $args);

        $this->assertEquals(200, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('success', $body['status']);
        $this->assertEquals('Product deleted successfully', $body['data']['message']);
    }

    /**
     * @covers \App\Controller\ProductController::deleteProduct
     */
    public function testDeleteProductNotFound(): void
    {
        $this->productService
            ->expects($this->once())
            ->method('delete')
            ->with(999)
            ->willThrowException(new \App\Exception\ResourceNotFoundException('Product', 999));

        $request = new ServerRequest([], [], new Uri('/api/v1/products/999'));
        $args = ['id' => '999'];
        
        $response = $this->controller->deleteProduct($request, $args);

        $this->assertEquals(404, $response->getStatusCode());
        
        $body = json_decode($response->getBody()->getContents(), true);
        $this->assertEquals('error', $body['status']);
    }
}
