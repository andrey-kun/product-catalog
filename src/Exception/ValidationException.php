<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

final class ValidationException extends Exception
{
    /**
     * @param array<string> $errors
     */
    public function __construct(
        private readonly array $errors,
        string $message = 'Validation failed'
    ) {
        parent::__construct($message);
    }

    /**
     * @return array<string>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }

    public function hasErrors(): bool
    {
        return !empty($this->errors);
    }
}
