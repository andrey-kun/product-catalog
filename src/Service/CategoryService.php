<?php

namespace App\Service;

use App\Repository\CategoryRepository;
use Exception;

class CategoryService
{
    private CategoryRepository $categoryRepository;

    public function __construct(CategoryRepository $categoryRepository)
    {
        $this->categoryRepository = $categoryRepository;
    }

    /**
     * Получение всех категорий
     */
    public function getAll(): array
    {
        try {
            return $this->categoryRepository->findAll();
        } catch (Exception $e) {
            throw new Exception('Failed to fetch categories: ' . $e->getMessage());
        }
    }

    /**
     * Получение категории по ID
     */
    public function getById(string $id): ?array
    {
        try {
            return $this->categoryRepository->findById($id);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch category: ' . $e->getMessage());
        }
    }

    /**
     * Создание новой категории
     */
    public function create(array $data): array
    {
        try {
            if (!isset($data['name']) || empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            $categoryId = $this->categoryRepository->create($data);
            // Получаем созданную категорию
            return $this->categoryRepository->findById($categoryId);
        } catch (Exception $e) {
            throw new Exception('Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Обновление категории
     */
    public function update(string $id, array $data): ?array
    {
        try {
            if (!isset($data['name']) || empty($data['name'])) {
                throw new Exception('Category name is required');
            }

            $result = $this->categoryRepository->update($id, $data);
            if (!$result) {
                return null;
            }
            // Получаем обновленную категорию
            return $this->categoryRepository->findById($id);
        } catch (Exception $e) {
            throw new Exception('Failed to update category: ' . $e->getMessage());
        }
    }

    /**
     * Удаление категории
     */
    public function delete(string $id): bool
    {
        try {
            return $this->categoryRepository->delete($id);
        } catch (Exception $e) {
            throw new Exception('Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Поиск категории по названию
     */
    public function getByName(string $name): ?array
    {
        try {
            return $this->categoryRepository->findByName($name);
        } catch (Exception $e) {
            throw new Exception('Failed to find category: ' . $e->getMessage());
        }
    }

    /**
     * Получение категорий для конкретного товара
     */
    public function getByProductId(string $productId): array
    {
        try {
            return $this->categoryRepository->findByProductId($productId);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch categories for product: ' . $e->getMessage());
        }
    }
}