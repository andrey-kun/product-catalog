<?php

use App\DI\Container;
use App\Controller\ProductController;
use App\Service\ProductService;
use App\Repository\ProductRepository;
use App\Infrastructure\Database\Connection;

// Подключение автозагрузчика Composer
require_once __DIR__ . '/../vendor/autoload.php';

// Инициализация DI контейнера
$container = new Container();

// Регистрация зависимостей
$container->set(Connection::class, function() {
    $config = require __DIR__ . '/../config/database.php';
    return new Connection($config);
});

$container->set(ProductRepository::class, function($container) {
    $db = $container->get(Connection::class);
    return new ProductRepository($db);
});

$container->set(ProductService::class, function($container) {
    $repository = $container->get(ProductRepository::class);
    return new ProductService($repository);
});

$container->set(ProductController::class, function($container) {
    $service = $container->get(ProductService::class);
    return new ProductController($service);
});

// Обработка запроса
try {
    // Определение метода и URI
    $method = $_SERVER['REQUEST_METHOD'];
    $uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);

    // Очистка URI от лишних символов
    $uri = trim($uri, '/');

    // Разбор маршрутов
    $route = explode('/', $uri);

    // Определение контроллера и метода
    if (empty($route[0])) {
        $controller = 'product';
        $action = 'index';
        $id = null;
    } else {
        $controller = $route[0];
        $action = isset($route[1]) ? $route[1] : 'index';
        $id = isset($route[2]) ? $route[2] : null;
    }

    // Получение контроллера из DI контейнера
    $controllerInstance = $container->get(ProductController::class);

    // Вызов соответствующего метода
    switch ($method) {
        case 'GET':
            if ($id !== null) {
                $response = $controllerInstance->show($id);
            } else {
                $response = $controllerInstance->index();
            }
            break;

        case 'POST':
            $response = $controllerInstance->store();
            break;

        case 'PUT':
            if ($id !== null) {
                $response = $controllerInstance->update($id);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            break;

        case 'DELETE':
            if ($id !== null) {
                $response = $controllerInstance->destroy($id);
            } else {
                http_response_code(405);
                echo json_encode(['error' => 'Method not allowed']);
                exit;
            }
            break;

        default:
            http_response_code(405);
            echo json_encode(['error' => 'Method not allowed']);
            exit;
    }

    // Вывод ответа
    header('Content-Type: application/json');
    echo $response;

} catch (Exception $e) {
    // Обработка ошибок
    http_response_code(500);
    echo json_encode([
        'error' => 'Internal server error',
        'message' => $e->getMessage()
    ]);
}