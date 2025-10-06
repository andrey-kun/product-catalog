<?php

declare(strict_types=1);

namespace App\Client;

use App\Contract\DaData\FindPartyRequest;
use App\Contract\DaData\FindPartyResponse;
use App\Contract\DaData\PartyType;
use App\Exception\ExternalServiceException;

interface DaDataClientInterface
{
    /**
     * @throws ExternalServiceException
     */
    public function findParty(FindPartyRequest $request): FindPartyResponse;

    /**
     * @throws ExternalServiceException
     */
    public function findByInn(string $inn, PartyType $partyType): FindPartyResponse;
}
