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
        }
    }

    protected function tearDown(): void
    {
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
        
        $container = $containerBuilder->build();
        
        if (!$this->isUnitTest()) {
            $testDbManager = $container->get(\Tests\TestDatabaseManager::class);
            $testDbManager->createTestDatabase();
            $container->set(\Doctrine\ORM\EntityManagerInterface::class, $testDbManager->getTestEntityManager());
            
            $container->set(
                \Psr\SimpleCache\CacheInterface::class,
                new \Symfony\Component\Cache\Psr16Cache(new \Symfony\Component\Cache\Adapter\ArrayAdapter())
            );
            
            $testEntityManager = $testDbManager->getTestEntityManager();
            
            $productRepository = $testEntityManager->getRepository(\App\Entity\Product::class);
            $container->set(\App\Repository\ProductRepository::class, $productRepository);
            
            $databaseSearchService = new \App\Search\DatabaseSearchService(
                $productRepository,
                $testEntityManager
            );
            $container->set(
                \App\Contract\SearchServiceInterface::class,
                $databaseSearchService
            );
        }
        
        return $container;
    }

    private function isUnitTest(): bool
    {
        $className = get_class($this);
        return str_contains($className, 'Tests\\Unit\\');
    }
}
