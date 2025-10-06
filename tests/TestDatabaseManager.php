<?php

declare(strict_types=1);

namespace Tests;

use Doctrine\DBAL\Connection;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

class TestDatabaseManager
{
    private EntityManagerInterface $entityManager;
    private Connection $connection;
    private DependencyFactory $migrationFactory;
    private ?Connection $testConnection = null;
    private ?EntityManagerInterface $testEntityManager = null;

    public function __construct(EntityManagerInterface $entityManager, DependencyFactory $migrationFactory)
    {
        $this->entityManager = $entityManager;
        $this->connection = $entityManager->getConnection();
        $this->migrationFactory = $migrationFactory;

    }

    public function createTestDatabase(): void
    {
        if ($this->testConnection !== null && $this->testEntityManager !== null) {
            return;
        }

        $dbName = $this->getTestDatabaseName();

        $this->connection->executeStatement("DROP DATABASE IF EXISTS `{$dbName}`");

        $this->connection->executeStatement("CREATE DATABASE `{$dbName}`");

        $this->testConnection = $this->createTestConnection($dbName);

        $this->testEntityManager = $this->createTestEntityManager($this->testConnection);

        $this->runMigrationsOnConnection($this->testConnection);
    }

    public function dropTestDatabase(): void
    {
        $dbName = $this->getTestDatabaseName();
        $this->connection->executeStatement("DROP DATABASE IF EXISTS `{$dbName}`");
    }

    public function truncateTables(): void
    {
        if ($this->testConnection === null) {
            throw new \RuntimeException('Test database not created. Call createTestDatabase() first.');
        }

        while ($this->testConnection->isTransactionActive()) {
            try {
                $this->testConnection->rollBack();
            } catch (\Exception $e) {
                break;
            }
        }

        $tables = ['product_categories', 'products', 'categories'];

        // Disable foreign key checks
        $this->testConnection->executeStatement('SET FOREIGN_KEY_CHECKS = 0');

        foreach ($tables as $table) {
            $this->testConnection->executeStatement("DELETE FROM `{$table}`");
            $this->testConnection->executeStatement("ALTER TABLE `{$table}` AUTO_INCREMENT = 1");
        }

        $this->testConnection->executeStatement('SET FOREIGN_KEY_CHECKS = 1');
    }

    private function createTestConnection(string $dbName): \Doctrine\DBAL\Connection
    {
        $params = $this->connection->getParams();
        $params['dbname'] = $dbName;

        $params['driverOptions'] = [
            \PDO::ATTR_EMULATE_PREPARES => false,
            \PDO::MYSQL_ATTR_USE_BUFFERED_QUERY => true,
        ];

        return \Doctrine\DBAL\DriverManager::getConnection($params);
    }

    private function runMigrationsOnConnection(\Doctrine\DBAL\Connection $connection): void
    {
        $dbName = $connection->getDatabase();
        if (!str_ends_with($dbName, '_test')) {
            throw new \RuntimeException("Expected test database connection, got: {$dbName}");
        }

        $config = new \Doctrine\Migrations\Configuration\Migration\ConfigurationArray([
            'migrations_paths' => [
                'migrations' => __DIR__ . '/../migrations'
            ],
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
            ],
        ]);

        $testMigrationFactory = \Doctrine\Migrations\DependencyFactory::fromConnection(
            $config,
            new \Doctrine\Migrations\Configuration\Connection\ExistingConnection($connection)
        );

        $metadataStorage = $testMigrationFactory->getMetadataStorage();
        $metadataStorage->ensureInitialized();

        $migrator = $testMigrationFactory->getMigrator();
        $planCalculator = $testMigrationFactory->getMigrationPlanCalculator();
        $plan = $planCalculator->getPlanUntilVersion($testMigrationFactory->getVersionAliasResolver()->resolveVersionAlias('latest'));
        $migratorConfiguration = new \Doctrine\Migrations\MigratorConfiguration();
        $migrator->migrate($plan, $migratorConfiguration);
    }

    private function createTestEntityManager(Connection $connection): EntityManagerInterface
    {
        $entityPaths = [
            __DIR__ . '/../src/Entity'
        ];

        $config = \Doctrine\ORM\ORMSetup::createAttributeMetadataConfiguration(
            $entityPaths,
            isDevMode: true,
            proxyDir: null,
            cache: new \Symfony\Component\Cache\Adapter\ArrayAdapter()
        );

        $config->setSecondLevelCacheEnabled(false);

        return new \Doctrine\ORM\EntityManager($connection, $config);
    }

    public function getTestEntityManager(): EntityManagerInterface
    {
        if ($this->testEntityManager === null) {
            throw new \RuntimeException('Test database not created. Call createTestDatabase() first.');
        }

        return $this->testEntityManager;
    }

    private function getTestDatabaseName(): string
    {
        $dbName = $_ENV['DB_NAME'] ?? 'product_catalog';
        return $dbName . '_test';
    }
}
