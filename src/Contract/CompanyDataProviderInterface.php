<?php

declare(strict_types=1);

namespace App\Contract;

use App\Contract\DaData\PartyType;
use App\Exception\ExternalServiceException;

interface CompanyDataProviderInterface
{
    /**
     * @throws ExternalServiceException
     */
    public function findByInn(string $inn, PartyType $partyType): ?CompanyData;
}
