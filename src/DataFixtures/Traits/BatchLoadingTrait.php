<?php

namespace App\DataFixtures\Traits;

use Doctrine\Persistence\ObjectManager;

trait BatchLoadingTrait
{
    /**
     * Load entities in batches for better performance
     *
     * @param ObjectManager $manager The entity manager
     * @param array $entities Array of entities to persist
     * @param int $batchSize Number of entities to process before flushing
     */
    private function loadInBatches(ObjectManager $manager, array $entities, int $batchSize = 100): void
    {
        $count = 0;

        foreach ($entities as $entity) {
            $manager->persist($entity);

            if (++$count % $batchSize === 0) {
                $manager->flush();
            }
        }

        if ($count % $batchSize !== 0) {
            $manager->flush();
        }
    }
}
