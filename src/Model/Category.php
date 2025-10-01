<?php

namespace App\Model;

class Category
{
    private ?string $id = null;
    private string $name;
    private ?string $createdAt = null;
    private ?string $updatedAt = null;

    public function __construct(string $name)
    {
        $this->name = $name;
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
            'created_at' => $this->createdAt,
            'updated_at' => $this->updatedAt
        ];
    }

    /**
     * Создание объекта из массива данных
     */
    public static function fromArray(array $data): self
    {
        $category = new self($data['name']);

        if (isset($data['id'])) {
            $category->setId($data['id']);
        }

        if (isset($data['created_at'])) {
            $category->setCreatedAt($data['created_at']);
        }

        if (isset($data['updated_at'])) {
            $category->setUpdatedAt($data['updated_at']);
        }

        return $category;
    }

    /**
     * Валидация данных категории
     */
    public function validate(): array
    {
        $errors = [];

        if (empty($this->name)) {
            $errors[] = 'Name is required';
        } elseif (strlen($this->name) > 255) {
            $errors[] = 'Name must be less than 255 characters';
        }

        return $errors;
    }
}