<?php

declare(strict_types=1);

use App\Http\ResponseEmitter;
use DI\ContainerBuilder;
use Laminas\Diactoros\ServerRequestFactory;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use Symfony\Component\Dotenv\Dotenv;

error_reporting(E_ALL & ~E_DEPRECATED);

require_once __DIR__ . '/../vendor/autoload.php';

$dotenv = new Dotenv();
$dotenv->loadEnv(__DIR__ . '/../.env');

$containerBuilder = new ContainerBuilder();
$containerBuilder->useAutowiring(true);
$containerBuilder->useAttributes(true);
$containerBuilder->addDefinitions(__DIR__ . '/../config/di.php');
$container = $containerBuilder->build();

$request = ServerRequestFactory::fromGlobals();

$routerFactory = require __DIR__ . '/../config/routes.php';
$router = $routerFactory($container);

try {
    $response = $router->dispatch($request);
    ResponseEmitter::emit($response);

} catch (NotFoundException $e) {
    ResponseEmitter::emitError(404, 'Route not found', 'The requested endpoint does not exist');

} catch (MethodNotAllowedException $e) {
    ResponseEmitter::emitError(405, 'Method not allowed', 'The HTTP method is not allowed for this endpoint');

} catch (Exception $e) {
    ResponseEmitter::emitError(500, 'Internal server error', $e->getMessage());
}