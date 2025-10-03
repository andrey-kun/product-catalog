<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\BatchLoadingTrait;
use App\DataFixtures\Traits\DataFileLoaderTrait;
use App\Entity\Category;
use App\Entity\Product;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\DependentFixtureInterface;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use function assert;
use function count;
use function sprintf;

final class ProductFixtures extends AbstractFixture implements DependentFixtureInterface
{
    use BatchLoadingTrait;
    use DataFileLoaderTrait;

    public const string PRODUCT_REFERENCE_PREFIX = 'product_';

    public function load(ObjectManager $manager): void
    {
        $productsData = $this->loadDataFromCsvFile('products.csv');

        if (empty($productsData)) {
            throw new RuntimeException('Products CSV file is empty or not found');
        }

        $this->loadFromCsvData($manager, $productsData);
    }

    private function loadFromCsvData(ObjectManager $manager, array $productsData): void
    {
        $products = [];

        foreach ($productsData as $data) {
            $product = new Product(
                $data['name'],
                $data['inn'],
                $data['barcode'],
                $data['description'] ?? null
            );

            if (!empty($data['category_ids'])) {
                foreach (explode(',', $data['category_ids']) as $categoryId) {
                    $categoryId = trim($categoryId);
                    if (!empty($categoryId)) {
                        $category = $this->getReference(CategoryFixtures::CATEGORY_REFERENCE_PREFIX . $categoryId);
                        assert($category instanceof Category);

                        $product->categories->add($category);
                    }
                }
            }

            $this->addReference(self::PRODUCT_REFERENCE_PREFIX . $data['id'], $product);

            $products[] = $product;
        }

        $this->loadInBatches($manager, $products);

        echo sprintf("Loaded %d products with categories from CSV\n", count($products));
    }

    public function getDependencies(): array
    {
        return [
            CategoryFixtures::class,
        ];
    }
}