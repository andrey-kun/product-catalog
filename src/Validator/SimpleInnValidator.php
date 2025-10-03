<?php

declare(strict_types=1);

namespace App\Validator;

use App\Contract\InnValidationResult;
use App\Contract\InnValidatorInterface;
use App\Contract\DaData\PartyType;

final class SimpleInnValidator implements InnValidatorInterface
{
    public function validate(string $inn, PartyType $partyType): InnValidationResult
    {
        if (!preg_match('/^\d{10}$|^\d{12}$/', $inn)) {
            return InnValidationResult::invalid('INN must be 10 or 12 digits');
        }

        return InnValidationResult::valid("Test Company (INN: {$inn})");
    }
}
