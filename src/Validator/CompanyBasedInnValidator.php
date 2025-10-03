<?php

declare(strict_types=1);

namespace App\Validator;

use App\Contract\DaData\PartyType;
use App\Contract\InnValidatorInterface;
use App\Contract\InnValidationResult;
use App\Contract\CompanyDataProviderInterface;
use App\Exception\ExternalServiceException;

final readonly class CompanyBasedInnValidator implements InnValidatorInterface
{
    public function __construct(
        private CompanyDataProviderInterface $companyProvider
    ) {}

    public function validate(string $inn, PartyType $partyType): InnValidationResult
    {
        try {
            $company = $this->companyProvider->findByInn($inn, $partyType);

            if ($company === null) {
                return InnValidationResult::invalid("Company with INN '{$inn}' not found");
            }

            return InnValidationResult::valid($company->name);

        } catch (ExternalServiceException $e) {
            return InnValidationResult::failed($e->getMessage());
        }
    }
}
