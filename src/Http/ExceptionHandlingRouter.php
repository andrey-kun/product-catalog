<?php

declare(strict_types=1);

namespace App\Http;

use Laminas\Diactoros\Response\JsonResponse;
use League\Route\Http\Exception\MethodNotAllowedException;
use League\Route\Http\Exception\NotFoundException;
use League\Route\Router;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

class ExceptionHandlingRouter
{
    public function __construct(
        private Router $router
    ) {}

    public function dispatch(ServerRequestInterface $request): ResponseInterface
    {
        try {
            return $this->router->dispatch($request);
        } catch (NotFoundException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Not Found'
            ], 404);
        } catch (MethodNotAllowedException $e) {
            return new JsonResponse([
                'status' => 'error',
                'message' => 'Method Not Allowed'
            ], 405);
        }
    }
}
