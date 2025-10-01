<?php

namespace App\Repository;

use App\Infrastructure\Database\Connection;
use Exception;

class CategoryRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Получение всех категорий
     */
    public function findAll(): array
    {
        try {
            $sql = "SELECT * FROM categories ORDER BY name";
            return $this->connection->fetchAll($sql);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch categories: ' . $e->getMessage());
        }
    }

    /**
     * Получение категории по ID
     */
    public function findById(string $id): ?array
    {
        try {
            $sql = "SELECT * FROM categories WHERE id = :id";
            return $this->connection->fetchOne($sql, ['id' => $id]);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch category: ' . $e->getMessage());
        }
    }

    /**
     * Создание новой категории
     */
    public function create(array $data): string
    {
        try {
            // Проверяем уникальность названия категории
            $sql = "SELECT id FROM categories WHERE name = :name";
            $existing = $this->connection->fetchOne($sql, ['name' => $data['name']]);
            if ($existing) {
                throw new Exception('Category with this name already exists');
            }

            // Создаем новую категорию
            $sql = "INSERT INTO categories (name) VALUES (:name)";
            $params = ['name' => $data['name']];
            $this->connection->query($sql, $params);
            $categoryId = $this->connection->lastInsertId();

            return $categoryId;
        } catch (Exception $e) {
            throw new Exception('Failed to create category: ' . $e->getMessage());
        }
    }

    /**
     * Обновление категории
     */
    public function update(string $id, array $data): bool
    {
        try {
            // Проверяем уникальность названия категории (если имя изменено)
            if (isset($data['name'])) {
                $sql = "SELECT id FROM categories WHERE name = :name AND id != :id";
                $existing = $this->connection->fetchOne($sql, ['name' => $data['name'], 'id' => $id]);
                if ($existing) {
                    throw new Exception('Category with this name already exists');
                }
            }

            // Обновляем категорию
            $fields = array_keys($data);
            $setClauses = [];

            foreach ($fields as $field) {
                $setClauses[] = "{field} = :{field}";
            }

            $sql = "UPDATE categories SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $params = $data;
            $params['id'] = $id;

            return $this->connection->execute($sql, $params);
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
            // Проверяем, есть ли связанные товары
            $sql = "SELECT COUNT(*) as count FROM product_categories WHERE category_id = :id";
            $result = $this->connection->fetchOne($sql, ['id' => $id]);
            if ($result['count'] > 0) {
                throw new Exception('Cannot delete category that has associated products');
            }

            // Удаляем категорию
            $sql = "DELETE FROM categories WHERE id = :id";
            return $this->connection->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            throw new Exception('Failed to delete category: ' . $e->getMessage());
        }
    }

    /**
     * Поиск категории по названию
     */
    public function findByName(string $name): ?array
    {
        try {
            $sql = "SELECT * FROM categories WHERE name = :name";
            return $this->connection->fetchOne($sql, ['name' => $name]);
        } catch (Exception $e) {
            throw new Exception('Failed to find category: ' . $e->getMessage());
        }
    }

    /**
     * Получение всех категорий для конкретного товара
     */
    public function findByProductId(string $productId): array
    {
        try {
            $sql = "SELECT c.* FROM categories c 
                   JOIN product_categories pc ON c.id = pc.category_id 
                   WHERE pc.product_id = :product_id";
            return $this->connection->fetchAll($sql, ['product_id' => $productId]);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch categories for product: ' . $e->getMessage());
        }
    }
}