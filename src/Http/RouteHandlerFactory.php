<?php

declare(strict_types=1);

namespace App\Http;

use Psr\Container\ContainerInterface;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;

final readonly class RouteHandlerFactory
{
    public function __construct(
        private ContainerInterface $container
    )
    {
    }

    public function create(string $controllerClass, string $method): callable
    {
        return function (ServerRequestInterface $request, ?array $args = null)
        use ($controllerClass, $method): ResponseInterface {
            $controller = $this->container->get($controllerClass);

            return ControllerInvoker::invoke(
                [$controller, $method],
                $request,
                $args ?? []
            );
        };
    }
}
