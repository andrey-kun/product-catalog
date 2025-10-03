<?php

declare(strict_types=1);

namespace Tests;

use DI\Container;
use DI\ContainerBuilder;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Tests\TestDatabaseManager;


abstract class TestCase extends BaseTestCase
{
    protected Container $container;
    protected TestDatabaseManager $testDbManager;

    protected function setUp(): void
    {
        parent::setUp();
        $this->container = $this->createContainer();

        if (!$this->isUnitTest()) {
            $this->testDbManager = $this->container->get(TestDatabaseManager::class);
            $this->testDbManager->createTestDatabase();
        }
    }

    protected function tearDown(): void
    {
        // Truncation is handled by IntegrationTestCase for integration tests
        parent::tearDown();
    }

    public static function tearDownAfterClass(): void
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);
        $containerBuilder->addDefinitions(__DIR__ . '/../config/di.php');
        $container = $containerBuilder->build();
        
        $testDbManager = $container->get(TestDatabaseManager::class);
        $testDbManager->dropTestDatabase();
        
        parent::tearDownAfterClass();
    }

    protected function createContainer(): Container
    {
        $containerBuilder = new ContainerBuilder();
        $containerBuilder->useAutowiring(true);
        $containerBuilder->useAttributes(true);
        $containerBuilder->addDefinitions(__DIR__ . '/../config/di.php');
        
        return $containerBuilder->build();
    }

    private function isUnitTest(): bool
    {
        $className = get_class($this);
        return str_contains($className, 'Tests\\Unit\\');
    }
}
