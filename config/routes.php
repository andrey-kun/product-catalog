<?php

use App\Controller\ProductController;
use App\Http\RouteHandlerFactory;
use Composer\InstalledVersions;
use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Router;
use League\Route\Strategy\ApplicationStrategy;
use Psr\Container\ContainerInterface;

if (!function_exists('configureRoutes')) {
    function configureRoutes(Router $router, RouteHandlerFactory $handlerFactory): Router
    {

    $router->group('/api/v1', static function ($group) use ($handlerFactory) {
        $group->map('GET', '/products', $handlerFactory->create(ProductController::class, 'listProducts'));
        $group->map('GET', '/products/{id:number}', $handlerFactory->create(ProductController::class, 'getProduct'));
        $group->map('POST', '/products', $handlerFactory->create(ProductController::class, 'createProduct'));
        $group->map('PUT', '/products/{id:number}', $handlerFactory->create(ProductController::class, 'updateProduct'));
        $group->map('DELETE', '/products/{id:number}', $handlerFactory->create(ProductController::class, 'deleteProduct'));
    });

    $router->map('GET', '/health', static function () {
        return new JsonResponse([
            'status' => 'ok',
            'timestamp' => date('c'),
            'version' => InstalledVersions::getRootPackage()['pretty_version'],
        ]);
    });

    return $router;
    }
}

return static function (ContainerInterface $container): Router {
    $router = new Router();

    $strategy = new ApplicationStrategy();
    $strategy->setContainer($container);
    $router->setStrategy($strategy);

    // Configure routes
    $router = configureRoutes($router, new RouteHandlerFactory($container));

    return $router;
};

