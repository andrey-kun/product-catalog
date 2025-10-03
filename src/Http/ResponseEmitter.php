<?php

declare(strict_types=1);

namespace App\Http;

use JsonException;
use Psr\Http\Message\ResponseInterface;
use function sprintf;

class ResponseEmitter
{
    public static function emit(ResponseInterface $response): void
    {
        if (!headers_sent()) {
            http_response_code($response->getStatusCode());

            foreach ($response->getHeaders() as $name => $values) {
                foreach ($values as $value) {
                    header(sprintf('%s: %s', $name, $value), false);
                }
            }
        }

        echo $response->getBody()->getContents();
    }

    /**
     * @throws JsonException
     */
    public static function emitError(int $statusCode, string $error, string $message): void
    {
        if (!headers_sent()) {
            http_response_code($statusCode);
            header('Content-Type: application/json');
        }

        echo json_encode([
            'error' => $error,
            'message' => $message,
            'timestamp' => date('c')
        ], JSON_THROW_ON_ERROR);
    }
}
