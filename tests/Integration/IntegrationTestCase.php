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
        
        // Start transaction for this test
        $this->entityManager->getConnection()->beginTransaction();
    }

    protected function tearDown(): void
    {
        // Rollback transaction to undo all changes
        if ($this->entityManager->getConnection()->isTransactionActive()) {
            $this->entityManager->getConnection()->rollback();
        }
        
        $this->entityManager->clear();
        
        parent::tearDown();
    }
}
