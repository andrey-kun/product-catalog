<?php

declare(strict_types=1);

namespace Tests;

use Laminas\Diactoros\ServerRequest;
use Laminas\Diactoros\Uri;

class TestHelper
{
    public static function createGetRequest(string $uri, array $queryParams = []): ServerRequest
    {
        $uri = new Uri($uri);

        if (!empty($queryParams)) {
            $uri = $uri->withQuery(http_build_query($queryParams));
        }

        return new ServerRequest([], [], $uri, 'GET');
    }

    public static function createPostRequest(string $uri, array $data = []): ServerRequest
    {
        $uri = new Uri($uri);
        $body = !empty($data) ? json_encode($data) : '';

        return new ServerRequest(
            [],
            [],
            $uri,
            'POST',
            'php://input',
            ['Content-Type' => 'application/json'],
            [],
            [],
            $body
        );
    }

    public static function createPutRequest(string $uri, array $data = []): ServerRequest
    {
        $uri = new Uri($uri);
        $body = !empty($data) ? json_encode($data) : '';

        return new ServerRequest(
            [],
            [],
            $uri,
            'PUT',
            'php://input',
            ['Content-Type' => 'application/json'],
            [],
            [],
            $body
        );
    }

    public static function createDeleteRequest(string $uri): ServerRequest
    {
        $uri = new Uri($uri);

        return new ServerRequest([], [], $uri, 'DELETE');
    }

    public static function assertJsonResponse(array $response, int $expectedStatus, string $expectedStatusType): void
    {
        $body = json_decode($response['body'], true);

        \PHPUnit\Framework\Assert::assertEquals($expectedStatus, $response['status']);
        \PHPUnit\Framework\Assert::assertEquals($expectedStatusType, $body['status']);
        \PHPUnit\Framework\Assert::assertIsArray($body);
    }

    public static function assertProductData(array $product, array $expectedData): void
    {
        foreach ($expectedData as $key => $expectedValue) {
            \PHPUnit\Framework\Assert::assertArrayHasKey($key, $product);
            \PHPUnit\Framework\Assert::assertEquals($expectedValue, $product[$key]);
        }
    }

    public static function assertProductExists(array $product): void
    {
        \PHPUnit\Framework\Assert::assertArrayHasKey('id', $product);
        \PHPUnit\Framework\Assert::assertArrayHasKey('name', $product);
        \PHPUnit\Framework\Assert::assertArrayHasKey('inn', $product);
        \PHPUnit\Framework\Assert::assertArrayHasKey('barcode', $product);
        \PHPUnit\Framework\Assert::assertArrayHasKey('created_at', $product);
        \PHPUnit\Framework\Assert::assertArrayHasKey('updated_at', $product);
    }

    public static function generateUniqueInn(): string
    {
        return str_pad((string)random_int(1000000000, 9999999999), 10, '0', STR_PAD_LEFT);
    }

    public static function generateUniqueBarcode(): string
    {
        return str_pad((string)random_int(1000000000000, 9999999999999), 13, '0', STR_PAD_LEFT);
    }

    public static function generateUniqueProductName(): string
    {
        return 'Test Product ' . uniqid();
    }

    public static function createValidProductData(): array
    {
        return [
            'name' => self::generateUniqueProductName(),
            'inn' => self::generateUniqueInn(),
            'barcode' => self::generateUniqueBarcode(),
            'description' => 'Test product description'
        ];
    }

    public static function createInvalidProductData(): array
    {
        return [
            'name' => '',
            'inn' => 'invalid',
            'barcode' => '123',
            'description' => 'Test description'
        ];
    }

    public static function measureExecutionTime(callable $callback): float
    {
        $startTime = microtime(true);
        $callback();
        $endTime = microtime(true);

        return $endTime - $startTime;
    }

    public static function assertExecutionTimeLessThan(callable $callback, float $maxTime): void
    {
        $executionTime = self::measureExecutionTime($callback);
        \PHPUnit\Framework\Assert::assertLessThan($maxTime, $executionTime, "Execution time {$executionTime}s exceeded maximum {$maxTime}s");
    }
}
