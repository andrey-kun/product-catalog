<?php

declare(strict_types=1);

namespace App\DataFixtures;

use App\DataFixtures\Traits\BatchLoadingTrait;
use App\DataFixtures\Traits\DataFileLoaderTrait;
use App\Entity\Category;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Persistence\ObjectManager;
use RuntimeException;
use function count;
use function sprintf;

final class CategoryFixtures extends AbstractFixture
{
    use BatchLoadingTrait;
    use DataFileLoaderTrait;

    public const string CATEGORY_REFERENCE_PREFIX = 'category_';

    public function load(ObjectManager $manager): void
    {
        $categoriesData = $this->loadDataFromCsvFile('categories.csv');

        if (empty($categoriesData)) {
            throw new RuntimeException('Categories CSV file is empty or not found');
        }

        $this->loadFromCsvData($manager, $categoriesData);
    }

    private function loadFromCsvData(ObjectManager $manager, array $categoriesData): void
    {
        $categories = [];

        foreach ($categoriesData as $data) {
            $category = new Category(
                $data['name']
            );

            $this->addReference(self::CATEGORY_REFERENCE_PREFIX . $data['id'], $category);

            $categories[] = $category;
        }

        $this->loadInBatches($manager, $categories);

        echo sprintf("Loaded %d categories from CSV\n", count($categories));
    }
}