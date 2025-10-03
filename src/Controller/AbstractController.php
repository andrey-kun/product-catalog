<?php

declare(strict_types=1);

namespace App\Controller;

use JsonException;
use Laminas\Diactoros\Response\JsonResponse;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use function is_array;

abstract class AbstractController
{
    final protected function success(mixed $data, int $statusCode = 200): ResponseInterface
    {
        return new JsonResponse([
            'status' => 'success',
            'data' => $data,
        ], $statusCode);
    }

    final protected function error(string $message, int $statusCode = 400): ResponseInterface
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $message,
        ], $statusCode);
    }

    final protected function notFound(string $resource = 'Resource'): ResponseInterface
    {
        return $this->error("{$resource} not found", 404);
    }

    final protected function validationError(array $errors, string $message = 'Validation failed'): ResponseInterface
    {
        return new JsonResponse([
            'status' => 'error',
            'message' => $message,
            'errors' => $errors,
        ], 422);
    }

    /**
     * @throws JsonException
     */
    final protected function parseJsonBody(ServerRequestInterface $request): array
    {
        $body = (string)$request->getBody();
        $data = json_decode($body ?: '{}', true, 512, JSON_THROW_ON_ERROR);

        if (is_array($data)) {
            return $data;
        }

        return [];
    }

    final protected function extractId(array $args, string $key = 'id'): int
    {
        return (int)($args[$key] ?? 0);
    }
}
