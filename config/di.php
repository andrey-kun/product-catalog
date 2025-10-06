<?php

declare(strict_types=1);

use App\Client\DaDataClient;
use App\Client\DaDataClientInterface;
use App\Contract\CompanyDataProviderInterface;
use App\Contract\InnValidatorInterface;
use App\Contract\SearchServiceInterface;
use App\Provider\DaDataCompanyProvider;
use App\Repository\ProductRepository;
use App\Search\DatabaseSearchService;
use App\Search\ElasticsearchService;
use App\Service\SearchService;
use App\Validator\CompanyBasedInnValidator;
use App\Validator\SimpleInnValidator;
use Doctrine\Migrations\DependencyFactory;
use Doctrine\Migrations\Configuration\Migration\ConfigurationArray;
use Doctrine\Migrations\Configuration\Connection\ExistingConnection;
use Doctrine\ORM\EntityManagerInterface;
use Elastic\Elasticsearch\ClientBuilder;
use GuzzleHttp\Client;
use League\Route\Router;
use Psr\Container\ContainerInterface;
use Psr\Log\NullLogger;
use Psr\SimpleCache\CacheInterface;
use Symfony\Component\Cache\Adapter\FilesystemAdapter;
use Symfony\Component\Cache\Psr16Cache;
use Tests\TestDatabaseManager;

return [
    EntityManagerInterface::class => static fn() => require __DIR__ . '/doctrine/orm.php',
    CacheInterface::class => static fn() => new Psr16Cache(new FilesystemAdapter()),
    Router::class => static function (ContainerInterface $container) {
        $routerFactory = require __DIR__ . '/routes.php';
        return $routerFactory($container);
    },

    \App\Http\ExceptionHandlingRouter::class => static function (ContainerInterface $container) {
        return new \App\Http\ExceptionHandlingRouter($container->get(Router::class));
    },

    DependencyFactory::class => static function (ContainerInterface $container) {
        $entityManager = $container->get(EntityManagerInterface::class);
        $connection = $entityManager->getConnection();
        
        $config = new ConfigurationArray([
            'migrations_paths' => [
                'migrations' => __DIR__ . '/../migrations'
            ],
            'table_storage' => [
                'table_name' => 'doctrine_migration_versions',
            ],
        ]);
        
        return DependencyFactory::fromConnection($config, new ExistingConnection($connection));
    },

    TestDatabaseManager::class => static function (ContainerInterface $container) {
        return new TestDatabaseManager(
            $container->get(EntityManagerInterface::class),
            $container->get(DependencyFactory::class)
        );
    },

    DaDataClientInterface::class => static function () {
        $apiKey = $_ENV['DADATA_API_KEY'] ?? null;

        if ($apiKey && $apiKey !== 'your-dadata-api-key' && $apiKey !== 'test-key') {
            return new DaDataClient(new Client(), $apiKey);
        }

        return null;
    },

    CompanyDataProviderInterface::class => static function (ContainerInterface $container) {
        $client = $container->get(DaDataClientInterface::class);

        if ($client !== null) {
            return new DaDataCompanyProvider($client);
        }

        return null;
    },

    InnValidatorInterface::class => static function (ContainerInterface $container) {
        $companyProvider = $container->get(CompanyDataProviderInterface::class);

        if ($companyProvider !== null) {
            return new CompanyBasedInnValidator($companyProvider);
        }

        return new SimpleInnValidator();
    },

    \Elastic\Elasticsearch\Client::class => static function () {
        $host = $_ENV['ES_HOST'] ?? 'elasticsearch';
        $port = $_ENV['ES_PORT'] ?? '9200';

        return ClientBuilder::create()
            ->setHosts(["{$host}:{$port}"])
            ->build();
    },

    ElasticsearchService::class => static function (ContainerInterface $container) {
        return ElasticsearchService::create(
            $_ENV['ES_HOST'] ?? 'elasticsearch',
            $_ENV['ES_PORT'] ?? '9200',
            'products'
        );
    },

    DatabaseSearchService::class => static function (ContainerInterface $container) {
        return new DatabaseSearchService(
            $container->get(ProductRepository::class),
            $container->get(EntityManagerInterface::class)
        );
    },

    SearchServiceInterface::class => static function (ContainerInterface $container) {
        return new SearchService(
            $container->get(ElasticsearchService::class),
            $container->get(DatabaseSearchService::class),
            new NullLogger()
        );
    },

];
