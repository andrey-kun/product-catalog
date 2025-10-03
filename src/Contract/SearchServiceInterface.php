<?php

declare(strict_types=1);

namespace App\Contract;

interface SearchServiceInterface
{
    public function search(ProductSearchFilters $filters): array;

    public function getById(int $id): ?SearchResult;

    public function index(array $productData): void;

    public function update(array $productData): void;

    public function remove(int $id): void;

    public function isAvailable(): bool;
}
