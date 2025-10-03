<?php

declare(strict_types=1);

namespace App\Exception;

use RuntimeException;
use Throwable;

final class ProductException extends RuntimeException
{
    public function __construct(string $message = '', int $code = 0, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}