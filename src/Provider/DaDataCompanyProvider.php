<?php

declare(strict_types=1);

namespace App\Provider;

use App\Client\DaDataClientInterface;
use App\Contract\CompanyData;
use App\Contract\CompanyDataProviderInterface;
use App\Contract\DaData\PartyType;

final class DaDataCompanyProvider implements CompanyDataProviderInterface
{
    public function __construct(
        private readonly DaDataClientInterface $client
    )
    {
    }

    public function findByInn(string $inn, PartyType $partyType): ?CompanyData
    {
        $response = $this->client->findByInn($inn, $partyType);
        
        if (!$response->isValid()) {
            return null;
        }

        return new CompanyData(
            inn: $response->inn ?? $inn,
            name: $response->value ?? 'Unknown Company',
        );
    }
}
