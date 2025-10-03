<?php

declare(strict_types=1);

namespace App\Client;

use App\Contract\DaData\FindPartyRequest;
use App\Contract\DaData\FindPartyResponse;
use App\Contract\DaData\PartyType;
use App\Exception\ExternalServiceException;
use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use SensitiveParameter;
use Throwable;

final class DaDataClient
{
    private const string API_URL = 'https://suggestions.dadata.ru/suggestions/api/4_1/rs/';
    private const int TIMEOUT = 5;

    public function __construct(
        private readonly Client                       $httpClient,
        #[SensitiveParameter] private readonly string $apiKey,
    )
    {
    }

    /**
     * @throws ExternalServiceException
     */
    public function findParty(FindPartyRequest $request): FindPartyResponse
    {
        try {
            $response = $this->httpClient->post(self::API_URL . 'findById/party', [
                'headers' => [
                    'Authorization' => "Token {$this->apiKey}",
                    'Content-Type' => 'application/json',
                    'Accept' => 'application/json',
                ],
                'json' => (array)$request,
                'timeout' => self::TIMEOUT,
            ]);

            $data = json_decode($response->getBody()->getContents(), true, 512, JSON_THROW_ON_ERROR);

            return FindPartyResponse::fromApiResponse($data);

        } catch (RequestException $e) {
            throw new ExternalServiceException(
                "DaData API request failed for '$request->query': {$e->getMessage()}",
                previous: $e
            );

        } catch (Throwable $e) {
            throw new ExternalServiceException(
                "DaData API error for '$request->query': {$e->getMessage()}",
                previous: $e
            );
        }
    }

    /**
     * @throws ExternalServiceException
     */
    public function findByInn(string $inn, PartyType $partyType): FindPartyResponse
    {
        $request = new FindPartyRequest(
            query: $inn,
            count: 1,
            type: $partyType,
        );

        return $this->findParty($request);
    }
}
