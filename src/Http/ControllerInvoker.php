<?php

declare(strict_types=1);

namespace App\Http;

use Exception;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Laminas\Diactoros\Response\JsonResponse;
use ReflectionMethod;
use function is_array;

final class ControllerInvoker
{
    public static function invoke(callable $controller, ServerRequestInterface $request, array $args = []): ResponseInterface
    {
        try {
            if (is_array($controller)) {
                [$controllerInstance, $method] = $controller;
                $result = self::invokeControllerMethod($controllerInstance, $method, $request, $args);
            } else {
                $result = $controller($request, $args);
            }

            if ($result instanceof ResponseInterface) {
                return $result;
            }

            return new JsonResponse($result);

        } catch (Exception $e) {
            return new JsonResponse([
                'error' => 'Internal server error',
                'message' => $e->getMessage()
            ], 500);
        }
    }

    private static function invokeControllerMethod(object $controller, string $method, ServerRequestInterface $request, array $args): mixed
    {
        $reflection = new ReflectionMethod($controller, $method);
        $parameters = $reflection->getParameters();
        
        $methodArgs = [];
        
        foreach ($parameters as $parameter) {
            $paramType = $parameter->getType();
            
            if ($paramType && !$paramType->isBuiltin()) {
                $typeName = $paramType->getName();
                
                if ($typeName === ServerRequestInterface::class || is_subclass_of($typeName, ServerRequestInterface::class)) {
                    $methodArgs[] = $request;
                }
            } elseif ($parameter->getName() === 'args') {
                $methodArgs[] = $args;
            }
        }
        
        return $reflection->invoke($controller, ...$methodArgs);
    }
}
