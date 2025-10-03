<?php

declare(strict_types=1);

namespace Tests;

use App\Entity\Category;
use App\Entity\Product;

class TestDataFactory
{
    public static function createCategory(?string $name = null): Category
    {
        $name = $name ?? 'Test Category ' . uniqid();
        return new Category($name);
    }

    public static function createProduct(
        string  $name = 'Test Product',
        ?string $inn = null,
        ?string $barcode = null,
        string  $description = 'Test description'
    ): Product
    {
        $inn = $inn ?? self::generateUniqueInn();
        $barcode = $barcode ?? self::generateUniqueBarcode();
        return new Product($name, $inn, $barcode, $description);
    }

    public static function createProductWithCategories(
        string $name = 'Test Product',
        array  $categories = []
    ): Product
    {
        $product = self::createProduct($name);

        foreach ($categories as $category) {
            $product->categories->add($category);
        }

        return $product;
    }

    public static function createElectronicsCategory(): Category
    {
        return new Category('Electronics ' . uniqid());
    }

    public static function createBooksCategory(): Category
    {
        return new Category('Books ' . uniqid());
    }

    public static function createClothingCategory(): Category
    {
        return new Category('Clothing ' . uniqid());
    }

    public static function createIphoneProduct(): Product
    {
        return new Product(
            'iPhone 15 Pro',
            self::generateUniqueInn(),
            self::generateUniqueBarcode(),
            'Latest iPhone with Pro features'
        );
    }

    public static function createSamsungProduct(): Product
    {
        return new Product(
            'Samsung Galaxy S24',
            self::generateUniqueInn(),
            self::generateUniqueBarcode(),
            'Samsung flagship smartphone'
        );
    }

    public static function createMacbookProduct(): Product
    {
        return new Product(
            'MacBook Pro',
            self::generateUniqueInn(),
            self::generateUniqueBarcode(),
            'Apple professional laptop'
        );
    }

    public static function createProgrammingBookProduct(): Product
    {
        return new Product(
            'Programming Book',
            self::generateUniqueInn(),
            self::generateUniqueBarcode(),
            'Learn programming fundamentals'
        );
    }

    public static function createMultipleProducts(int $count = 10): array
    {
        $products = [];

        for ($i = 1; $i <= $count; $i++) {
            $products[] = new Product(
                "Product {$i}",
                str_pad((string)$i, 10, '0', STR_PAD_LEFT),
                str_pad((string)$i, 13, '0', STR_PAD_LEFT),
                "Description for product {$i}"
            );
        }

        return $products;
    }

    public static function createProductData(
        string  $name = 'Test Product',
        ?string $inn = null,
        ?string $barcode = null,
        string  $description = 'Test description',
        array   $categoryIds = []
    ): array
    {
        $inn = $inn ?? self::generateUniqueInn();
        $barcode = $barcode ?? self::generateUniqueBarcode();
        return [
            'name' => $name,
            'inn' => $inn,
            'barcode' => $barcode,
            'description' => $description,
            'category_ids' => $categoryIds
        ];
    }

    public static function createUpdateProductData(
        string $name = 'Updated Product',
        string $description = 'Updated description',
        array  $categoryIds = []
    ): array
    {
        $data = [];

        $data['name'] = $name;
        $data['description'] = $description;

        if (!empty($categoryIds)) {
            $data['category_ids'] = $categoryIds;
        }

        return $data;
    }

    private static function generateUniqueInn(): string
    {
        return str_pad((string)random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    private static function generateUniqueBarcode(): string
    {
        return str_pad((string)random_int(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT);
    }
}
