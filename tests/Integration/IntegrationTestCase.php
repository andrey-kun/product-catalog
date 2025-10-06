<?php

declare(strict_types=1);

namespace Tests\Integration;

use Doctrine\ORM\EntityManagerInterface;
use Tests\TestCase as BaseTestCase;

abstract class IntegrationTestCase extends BaseTestCase
{
    protected EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->entityManager = $this->testDbManager->getTestEntityManager();
        
        $connection = $this->entityManager->getConnection();
        while ($connection->isTransactionActive()) {
            try {
                $connection->commit();
            } catch (\Exception $e) {
                try {
                    $connection->rollBack();
                } catch (\Exception $e2) {
                    break;
                }
            }
        }
    }

    protected function tearDown(): void
    {
        $connection = $this->entityManager->getConnection();
        while ($connection->isTransactionActive()) {
            try {
                $connection->commit();
            } catch (\Exception $e) {
                try {
                    $connection->rollBack();
                } catch (\Exception $e2) {
                    break;
                }
            }
        }
        
        $this->entityManager->clear();

        $this->testDbManager->truncateTables();

        parent::tearDown();
    }
}
