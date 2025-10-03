<?php

declare(strict_types=1);

namespace App\Contract;

use App\Contract\DaData\PartyType;

interface InnValidatorInterface
{
    public function validate(string $inn, PartyType $partyType): InnValidationResult;
}
