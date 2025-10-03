<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;

$dbConfig = require __DIR__ . '/../database.php';

$entityPaths = [
    __DIR__ . '/../../src/Entity'
];

$config = ORMSetup::createAttributeMetadataConfiguration(
    $entityPaths,
    true,
    null,
    new ArrayAdapter()
);

$config->setAutoCommit(false);

$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => $dbConfig['host'],
    'port' => $dbConfig['port'],
    'dbname' => $dbConfig['dbname'] . '_test',
    'user' => $dbConfig['username'],
    'password' => $dbConfig['password'],
    'charset' => $dbConfig['charset'] ?? 'utf8mb4',
    'driverOptions' => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci',
        PDO::ATTR_AUTOCOMMIT => false,
    ]
];

$connection = DriverManager::getConnection($connectionParams, $config);

return new EntityManager($connection, $config);
