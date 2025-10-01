<?php

namespace App\Model;

class Product
{
    private ?string $id = null;
    private string $name;
    private string $inn;
    private string $barcode;
    private ?string $description = null;
    private array $categories = [];
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(
        string $name,
        string $inn,
        string $barcode,
        ?string $description = null
    ) {
        $this->name = $name;
        $this->inn = $inn;
        $this->barcode = $barcode;
        $this->description = $description;
    }

    // Геттеры
    public function getId(): ?string
    {
        return $this->id;
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function getInn(): string
    {
        return $this->inn;
    }

    public function getBarcode(): string
    {
        return $this->barcode;
    }

    public function getDescription(): ?string
    {
        return $this->description;
    }

    public function getCategories(): array
    {
        return $this->categories;
    }

    public function getCreatedAt(): ?string
    {
        return $this->createdAt;
    }

    public function getUpdatedAt(): ?string
    {
        return $this->updatedAt;
    }

    // Сеттеры
    public function setId(string $id): void
    {
        $this->id = $id;
    }

    public function setName(string $name): void
    {
        $this->name = $name;
    }

    public function setInn(string $inn): void
    {
        $this->inn = $inn;
    }

    public function setBarcode(string $barcode): void
    {
        $this->barcode = $barcode;
    }

    public function setDescription(?string $description): void
    {
        $this->description = $description;
    }

    public function setCategories(array $categories): void
    {
        $this->categories = $categories;
    }

    public function setCreatedAt(string $createdAt): void
    {
        $this->createdAt = $createdAt;
    }

    public function setUpdatedAt(string $updatedAt): void
    {
        $this->updatedAt = $updatedAt;
    }

    /**
     * Преобразование объекта в массив для сохранения в БД
     */
    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'inn' => $this->inn,
            'barcode' => $this->barcode,
            'description' => $this->description,
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Создание объекта из массива данных
     */
    public static function fromArray(array $data): self
    {
        $product = new self(
            $data['name'],
            $data['inn'],
            $data['barcode'],
            $data['description'] ?? null
        );

        if (isset($data['id'])) {
            $product->setId($data['id']);
        }

        if (isset($data['created_at'])) {
            $product->setCreatedAt($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $product->setUpdatedAt($data['updated_at']);
        }

        if (isset($data['categories'])) {
            $product->setCategories($data['categories']);
        }

        return $product;
    }

    /**
     * Валидация данных продукта
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        }

        if (empty($this->inn)) {
            $errors[] = 'INN is required';
        } elseif (!$this->isValidInn($this->inn)) {
            $errors[] = 'Invalid INN format';
        }

        if (empty($this->barcode)) {
            $errors[] = 'Barcode is required';
        } elseif (!$this->isValidBarcode($this->barcode)) {
            $errors[] = 'Invalid barcode format';
        }

        return $errors;
    }

    /**
     * Проверка валидности ИНН
     */
    private function isValidInn(string $inn): bool
    {
        // Простая проверка длины ИНН (10 или 12 цифр)/', $inn);
        return false; // TODO реализовать
    }

    /**
 * Проверка валидности штрих-кода EAN-13
 */
    private function isValidBarcode(string $barcode): bool
{
    // EAN-13 должен содержать ровно 13 цифр
    return preg_match('/^\d{13}/', $barcode);
}
}