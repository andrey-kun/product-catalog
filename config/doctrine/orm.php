<?php

use Doctrine\DBAL\DriverManager;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\ORMSetup;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;

$dbConfig = require __DIR__ . '/../database.php';

$isDevMode = ($_ENV['APP_ENV'] ?? 'development') === 'development';

$cache = $isDevMode
    ? new ArrayAdapter()
    : new FilesystemAdapter('doctrine_cache', 0, __DIR__ . '/../../var/cache/doctrine');

$entityPaths = [
    __DIR__ . '/../../src/Entity'
];

$proxyDir = __DIR__ . '/../../var/cache/doctrine/proxies';
$proxyNamespace = 'App\Proxies';

$config = ORMSetup::createAttributeMetadataConfiguration(
    $entityPaths,
    $isDevMode,
    $proxyDir,
    $cache
);

$config->setProxyNamespace($proxyNamespace);

$connectionParams = [
    'driver' => 'pdo_mysql',
    'host' => $dbConfig['host'],
    'port' => $dbConfig['port'],
    'dbname' => $dbConfig['dbname'],
    'user' => $dbConfig['username'],
    'password' => $dbConfig['password'],
    'charset' => $dbConfig['charset'] ?? 'utf8mb4',
    'driverOptions' => [
        PDO::MYSQL_ATTR_INIT_COMMAND => 'SET NAMES utf8mb4 COLLATE utf8mb4_unicode_ci'
    ]
];

$connection = DriverManager::getConnection($connectionParams, $config);

return new EntityManager($connection, $config);
