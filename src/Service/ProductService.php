<?php

namespace App\Service;

use App\Repository\ProductRepository;
use App\Repository\CategoryRepository;
use App\Adapter\DaDataAdapter;
use App\Strategy\DaDataStrategy;
use Exception;

class ProductService
{
    private ProductRepository $productRepository;
    private CategoryRepository $categoryRepository;
    private DaDataAdapter $daDataAdapter;
    private DaDataStrategy $dadataStrategy;

    public function __construct(
        ProductRepository $productRepository,
        CategoryRepository $categoryRepository,
        DaDataAdapter $daDataAdapter,
        DaDataStrategy $dadataStrategy
    ) {
        $this->productRepository = $productRepository;
        $this->categoryRepository = $categoryRepository;
        $this->daDataAdapter = $daDataAdapter;
        $this->dadataStrategy = $dadataStrategy;
    }

    /**
     * Получение всех товаров с возможностью фильтрации
     */
    public function getAll(
        string $search = '',
        string $category = '',
        string $inn = '',
        string $barcode = ''
    ): array {
        return $this->productRepository->findAll($search, $category, $inn, $barcode);
    }

    /**
     * Получение товара по ID
     */
    public function getById(string $id): ?array
    {
        return $this->productRepository->findById($id);
    }

    /**
     * Создание нового товара
     */
    public function create(array $data): array
    {
        // Валидация ИНН через DaData
        if (!empty($data['inn'])) {
            $this->validateInn($data['inn']);
        }

        // Проверка уникальности ИНН + Штрих-код
        if (isset($data['inn']) && isset($data['barcode'])) {
            $existing = $this->productRepository->findByInnAndBarcode(
                $data['inn'],
                $data['barcode']
            );
            if ($existing) {
                throw new Exception('Product with this INN and barcode already exists');
            }
        }

        // Создание товара
        $productId = $this->productRepository->create($data);

        // Получение созданного товара с категориями
        $product = $this->productRepository->findById($productId);
        // Обновление индекса Elasticsearch
        $this->updateElasticsearchIndex($product, 'create');
        return $product;
    }

    /**
     * Обновление существующего товара
     */
    public function update(string $id, array $data): ?array
    {
        // Проверка существования товара
        $existingProduct = $this->productRepository->findById($id);
        if (!$existingProduct) {
            return null;
        }

        // Валидация ИНН через DaData (если ИНН изменен)
        if (isset($data['inn']) && $data['inn'] !== $existingProduct['inn']) {
            $this->validateInn($data['inn']);
        }

        // Проверка уникальности ИНН + Штрих-код при изменении
        if (isset($data['inn']) || isset($data['barcode'])) {
            $inn = $data['inn'] ?? $existingProduct['inn'];
            $barcode = $data['barcode'] ?? $existingProduct['barcode'];
            $existing = $this->productRepository->findByInnAndBarcode($inn, $barcode);
            if ($existing && $existing['id'] !== $id) {
                throw new Exception('Product with this INN and barcode already exists');
            }
        }

        // Обновление товара
        $this->productRepository->update($id, $data);

        // Получение обновленного товара
        $updatedProduct = $this->productRepository->findById($id);
        // Обновление индекса Elasticsearch
        $this->updateElasticsearchIndex($updatedProduct, 'update');
        return $updatedProduct;
    }

    /**
     * Удаление товара
     */
    public function delete(string $id): bool
    {
        $product = $this->productRepository->findById($id);
        if (!$product) {
            return false;
        }

        // Удаление товара
        $result = $this->productRepository->delete($id);
        // Удаление из индекса Elasticsearch
        if ($result) {
            $this->updateElasticsearchIndex($product, 'delete');
        }
        return $result;
    }

    /**
     * Валидация ИНН через DaData API
     */
    private function validateInn(string $inn): void
    {
        try {
            // Проверяем кэш перед запросом к API
            $cacheKey = 'dadata_inn_' . md5($inn);
            $cachedResult = $this->getFromCache($cacheKey);
            if ($cachedResult !== null) {
                if (!$cachedResult['valid']) {
                    throw new Exception('Invalid INN: ' . $inn);
                }
                return;
            }

            // Получаем результат из DaData API
            $result = $this->dadataStrategy->findParty($inn);
            // Проверяем валидность
            if (!$result['valid']) {
                throw new Exception('Invalid INN: ' . $inn);
            }

            // Кэшируем результат
            $this->setCache($cacheKey, $result);
        } catch (Exception $e) {
            throw new Exception('Failed to validate INN: ' . $e->getMessage());
        }
    }

    /**
     * Обновление индекса Elasticsearch
     */
    private function updateElasticsearchIndex(array $product, string $action): void
    {
        try {
            // Здесь должна быть логика работы с Elasticsearch
            // Это может быть вызов через Elasticsearch client
            $indexName = 'products';
            switch ($action) {
                case 'create':
                case 'update':
                    // Добавление/обновление документа в индексе
                    break;
                case 'delete':
                    // Удаление документа из индекса
                    break;
            }
        } catch (Exception $e) {
            // Если Elasticsearch недоступен, используем fallback на базу данных
            // Логирование ошибки и продолжение работы
        }
    }

    /**
     * Получение данных из кэша
     */
    private function getFromCache(string $key)
    {
        // Реализация кэширования (например, через Redis или файлы)
        return null;
    }

    /**
     * Установка данных в кэш
     */
    private function setCache(string $key, array $data, int $ttl = 3600): void
    {
        // Реализация кэширования
    }
}