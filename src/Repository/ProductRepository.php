<?php

namespace App\Repository;

use App\Infrastructure\Database\Connection;
use Exception;

class ProductRepository
{
    private Connection $connection;

    public function __construct(Connection $connection)
    {
        $this->connection = $connection;
    }

    /**
     * Получение всех товаров с возможностью фильтрации
     */
    public function findAll(
        string $search = '',
        string $category = '',
        string $inn = '',
        string $barcode = ''
    ): array {
        try {
            // Формируем SQL запрос с фильтрами
            $sql = "SELECT p.*, GROUP_CONCAT(c.name) as categories 
                   FROM products p 
                   LEFT JOIN product_categories pc ON p.id = pc.product_id 
                   LEFT JOIN categories c ON pc.category_id = c.id";

            // Добавляем условия поиска
            $whereConditions = [];
            $params = [];

            if (!empty($search)) {
                $whereConditions[] = "(p.name LIKE :search OR p.description LIKE :search)";
                $params['search'] = "%{$search}%";
            }

            if (!empty($category)) {
                $sql .= " JOIN product_categories pc2 ON p.id = pc2.product_id 
                         JOIN categories c2 ON pc2.category_id = c2.id";
                $whereConditions[] = "c2.name = :category";
                $params['category'] = $category;
            }

            if (!empty($inn)) {
                $whereConditions[] = "p.inn = :inn";
                $params['inn'] = $inn;
            }

            if (!empty($barcode)) {
                $whereConditions[] = "p.barcode = :barcode";
                $params['barcode'] = $barcode;
            }

            if (!empty($whereConditions)) {
                $sql .= " WHERE " . implode(" AND ", $whereConditions);
            }

            $sql .= " GROUP BY p.id 
                     ORDER BY p.created_at DESC";

            return $this->connection->fetchAll($sql, $params);
        } catch (Exception $e) {
            throw new Exception('Failed to fetch products: ' . $e->getMessage());
        }
    }

    /**
     * Получение товара по ID
     */
    public function findById(string $id): ?array
    {
        try {
            $sql = "SELECT p.*, GROUP_CONCAT(c.name) as categories 
                   FROM products p 
                   LEFT JOIN product_categories pc ON p.id = pc.product_id 
                   LEFT JOIN categories c ON pc.category_id = c.id 
                   WHERE p.id = :id 
                   GROUP BY p.id";
            $result = $this->connection->fetchOne($sql, ['id' => $id]);
            return $result ?: null;
        } catch (Exception $e) {
            throw new Exception('Failed to fetch product: ' . $e->getMessage());
        }
    }

    /**
     * Создание нового товара
     */
    public function create(array $data): string
    {
        try {
            // Подготовка полей для вставки
            $fields = array_keys($data);
            $values = array_values($data);

            // Исключаем id, так как он автоинкремент
            if (in_array('id', $fields)) {
                $key = array_search('id', $fields);
                unset($fields[key]);
                unset($values[key]);
            }

            // Отдельно обрабатываем категории
            $categories = [];
            if (isset($data['categories'])) {
                $categories = $data['categories'];
                unset($data['categories']);
            }

            // Формируем SQL для вставки
            $fieldNames = implode(', ', $fields);
            $placeholders = ':' . implode(', :', $fields);

            $sql = "INSERT INTO products ({fieldNames}) VALUES ({placeholders})";
            $params = array_combine($fields, $values);

            $this->connection->query(sql, $params);
            $productId = $this->connection->lastInsertId();

            // Сохраняем категории
            if (!empty($categories)) {
                $this->saveCategories($productId, $categories);
            }

            return $productId;
        } catch (Exception $e) {
            throw new Exception('Failed to create product: ' . $e->getMessage());
        }
    }

    /**
     * Обновление товара
     */
    public function update(string $id, array $data): bool
    {
        try {
            // Подготовка полей для обновления
            $fields = array_keys($data);
            $setClauses = [];

            foreach ($fields as $field) {
                $setClauses[] = "{field} = :{field}";
            }

            // Отдельно обрабатываем категории
            $categories = [];
            if (isset($data['categories'])) {
                $categories = $data['categories'];
                unset($data['categories']);
            }

            $sql = "UPDATE products SET " . implode(', ', $setClauses) . " WHERE id = :id";
            $params = $data;
            $params['id'] = $id;

            $result = $this->connection->execute(sql, $params);

            // Обновляем категории
            if (!empty($categories)) {
                $this->updateCategories($id, $categories);
            }

            return $result;
        } catch (Exception $e) {
            throw new Exception('Failed to update product: ' . $e->getMessage());
        }
    }

    /**
     * Удаление товара
     */
    public function delete(string $id): bool
    {
        try {
            // Сначала удаляем связи с категориями
            $sql = "DELETE FROM product_categories WHERE product_id = :id";
            $this->connection->execute($sql, ['id' => $id]);

            // Затем удаляем сам товар
            $sql = "DELETE FROM products WHERE id = :id";
            return $this->connection->execute($sql, ['id' => $id]);
        } catch (Exception $e) {
            throw new Exception('Failed to delete product: ' . $e->getMessage());
        }
    }

    /**
     * Поиск товара по ИНН и штрих-коду
     */
    public function findByInnAndBarcode(string $inn, string $barcode): ?array
    {
        try {
            $sql = "SELECT * FROM products WHERE inn = :inn AND barcode = :barcode";
            return $this->connection->fetchOne($sql, ['inn' => $inn, 'barcode' => $barcode]);
        } catch (Exception $e) {
            throw new Exception('Failed to find product: ' . $e->getMessage());
        }
    }

    /**
     * Сохранение категорий для товара
     */
    private function saveCategories(string $productId, array $categories): void
    {
        // Сначала удаляем старые связи
        $sql = "DELETE FROM product_categories WHERE product_id = :product_id";
        $this->connection->execute($sql, ['product_id' => $productId]);

        // Добавляем новые связи
        foreach ($categories as $categoryName) {
        // Проверяем существование категории или создаем новую
        $category = $this->getOrCreateCategory($categoryName);
        // Создаем связь между товаром и категорией
        $sql = "INSERT INTO product_categories (product_id, category_id) VALUES (:product_id, :category_id)";
        $this->connection->execute($sql, [
            'product_id' => $productId,
            'category_id' => $category->id
        ]);
    }
    }

    /**
     * Обновление категорий для товара
     */
    private function updateCategories(string $productId, array $categories): void
    {
        // Удаляем старые связи и добавляем новые
        $this->saveCategories($productId, $categories);
    }

    /**
     * Получение или создание категории
     */
    private function getOrCreateCategory(string $categoryName): object
    {
        try {
            // Проверяем существование категории
            $sql = "SELECT id FROM categories WHERE name = :name";
            $category = $this->connection->fetchOne($sql, ['name' => $categoryName]);

            if ($category) {
                return (object)['id' => $category['id']];
            }

            // Создаем новую категорию
            $sql = "INSERT INTO categories (name) VALUES (:name)";
            $this->connection->execute($sql, ['name' => $categoryName]);
            $categoryId = $this->connection->lastInsertId();

            return (object)['id' => $categoryId];
        } catch (Exception $e) {
            throw new Exception('Failed to create or find category: ' . $e->getMessage());
        }
    }
}