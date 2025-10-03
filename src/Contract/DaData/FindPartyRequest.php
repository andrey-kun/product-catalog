<?php

declare(strict_types=1);

namespace App\Contract\DaData;

final readonly class FindPartyRequest
{
    public function __construct(
        public string    $query,
        public int       $count,
        public PartyType $type
    )
    {
    }
}
