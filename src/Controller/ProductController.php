<?php

namespace App\Controller;

use App\Service\ProductService;
use Exception;

class ProductController
{
    private ProductService $productService;

    public function __construct(ProductService $productService)
    {
        $this->productService = $productService;
    }

    /**
     * Получение списка всех товаров
     */
    public function index(): string
    {
        try {
            // Получение параметров поиска из запроса
            $search = $_GET['search'] ?? '';
            $category = $_GET['category'] ?? '';
            $inn = $_GET['inn'] ?? '';
            $barcode = $_GET['barcode'] ?? '';
            $products = $this->productService->getAll($search, $category, $inn, $barcode);
            return json_encode([
                'status' => 'success',
                'data' => $products
            ], JSON_THROW_ON_ERROR);
        } catch (Exception $e) {
            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ], JSON_THROW_ON_ERROR);
        }
    }

    /**
     * Получение конкретного товара по ID
     */
    public function show(string $id): string
    {
        try {
            $product = $this->productService->getById($id);
            if ($product === null) {
                http_response_code(404);
                return json_encode([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }
            return json_encode([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (Exception $e) {
            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Создание нового товара
     */
    public function store(): string
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                return json_encode([
                    'status' => 'error',
                    'message' => 'Invalid JSON data'
                ]);
            }
            $product = $this->productService->create($input);
            http_response_code(201);
            return json_encode([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Обновление существующего товара
     */
    public function update(string $id): string
    {
        try {
            $input = json_decode(file_get_contents('php://input'), true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                http_response_code(400);
                return json_encode([
                    'status' => 'error',
                    'message' => 'Invalid JSON data'
                ]);
            }
            $product = $this->productService->update($id, $input);
            if ($product === null) {
                http_response_code(404);
                return json_encode([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }
            return json_encode([
                'status' => 'success',
                'data' => $product
            ]);
        } catch (Exception $e) {
            http_response_code(400);
            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }

    /**
     * Удаление товара
     */
    public function destroy(string $id): string
    {
        try {
            $deleted = $this->productService->delete($id);
            if (!$deleted) {
                http_response_code(404);
                return json_encode([
                    'status' => 'error',
                    'message' => 'Product not found'
                ]);
            }
            return json_encode([
                'status' => 'success',
                'message' => 'Product deleted successfully'
            ]);
        } catch (Exception $e) {
            http_response_code(500);
            return json_encode([
                'status' => 'error',
                'message' => $e->getMessage()
            ]);
        }
    }
}