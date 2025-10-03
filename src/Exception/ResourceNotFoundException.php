<?php

declare(strict_types=1);

namespace App\Exception;

use Exception;

final class ResourceNotFoundException extends Exception
{
    public function __construct(
        private readonly string $resourceType,
        private readonly string|int $resourceId,
        string $message = ''
    ) {
        $defaultMessage = "{$this->resourceType} with ID '{$this->resourceId}' not found";
        parent::__construct($message ?: $defaultMessage);
    }

    public function getResourceType(): string
    {
        return $this->resourceType;
    }

    public function getResourceId(): string|int
    {
        return $this->resourceId;
    }
}
