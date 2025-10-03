<?php

namespace App\Contract\DaData;

class Suggestion
{
    public function __construct(
        public string $value,
        public string $inn
    )
    {
    }
}