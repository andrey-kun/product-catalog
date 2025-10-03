<?php

declare(strict_types=1);

namespace App\Contract;

final readonly class CompanyData
{
    public function __construct(
        public string $inn,
        public string $name,
    ) {}
}
