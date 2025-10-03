<?php

declare(strict_types=1);

namespace App\Contract;

readonly class InnValidationResult
{
    public function __construct(
        public bool $isValid,
        public ?string $companyName = null,
        public ?string $errorMessage = null
    ) {}

    public static function valid(string $companyName): self
    {
        return new self(true, $companyName);
    }

    public static function invalid(string $errorMessage): self
    {
        return new self(false, null, $errorMessage);
    }

    public static function failed(string $errorMessage): self
    {
        return new self(false, null, "Validation service failed: {$errorMessage}");
    }
}
