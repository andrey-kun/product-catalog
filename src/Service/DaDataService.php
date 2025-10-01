<?php

namespace App\Service;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Exception;

class DaDataService
{
    private Client $httpClient;
    private string $apiKey;
    private string $apiUrl;
    private array $cache = [];

    public function __construct(string $apiKey, string $apiUrl = 'https://dadata.ru/api/')
    {
        $this->apiKey = $apiKey;
        $this->apiUrl = rtrim($apiUrl, '/');
        $this->httpClient = new Client([
            'headers' => [
                'Authorization' => 'Token ' . $this->apiKey,
                'Content-Type' => 'application/json',
                'Accept' => 'application/json'
            ]
        ]);
    }

    /**
     * Поиск организации по ИНН
     */
    public function findParty(string $inn): array
    {
        try {
            // Проверяем кэш перед запросом
            $cacheKey = 'dadata_party_' . md5($inn);
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }

            // Формируем URL для запроса
            $url = $this->apiUrl . '/find-party';

            // Подготавливаем данные для запроса
            $data = [
                'query' => $inn,
                'count' => 1
            ];

            // Выполняем запрос к API DaData
            $response = $this->httpClient->post($url, [
                'json' => $data
            ]);

            // Обрабатываем ответ
            $body = json_decode($response->getBody()->getContents(), true);
            if (isset($body['suggestions']) && !empty($body['suggestions'])) {
                $party = $body['suggestions'][0];
                $result = [
                    'valid' => true,
                    'party' => $party,
                    'inn' => $party['data']['inn'] ?? null
                ];
            } else {
                $result = [
                    'valid' => false,
                    'message' => 'Party not found'
                ];
            }

            // Кэшируем результат
            $this->cache[$cacheKey] = $result;
            return $result;
        } catch (RequestException $e) {
            // Если ошибка запроса, возвращаем недействительный результат
            return [
                'valid' => false,
                'message' => 'DaData API error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to find party: ' . $e->getMessage());
        }
    }

    /**
     * Поиск организации по наименованию
     */
    public function findPartyByName(string $name): array
    {
        try {
            // Проверяем кэш перед запросом
            $cacheKey = 'dadata_party_name_' . md5($name);
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }

            // Формируем URL для запроса
            $url = $this->apiUrl . '/find-party';

            // Подготавливаем данные для запроса
            $data = [
                'query' => $name,
                'count' => 1
            ];

            // Выполняем запрос к API DaData
            $response = $this->httpClient->post($url, [
                'json' => $data
            ]);

            // Обрабатываем ответ
            $body = json_decode($response->getBody()->getContents(), true);
            if (isset($body['suggestions']) && !empty($body['suggestions'])) {
                $party = $body['suggestions'][0];
                $result = [
                    'valid' => true,
                    'party' => $party
                ];
            } else {
                $result = [
                    'valid' => false,
                    'message' => 'Party not found'
                ];
            }

            // Кэшируем результат
            $this->cache[$cacheKey] = $result;
            return $result;
        } catch (RequestException $e) {
            // Если ошибка запроса, возвращаем недействительный результат
            return [
                'valid' => false,
                'message' => 'DaData API error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to find party by name: ' . $e->getMessage());
        }
    }

    /**
     * Получение информации о компании по ИНН
     */
    public function getPartyInfo(string $inn): array
    {
        try {
            // Проверяем кэш перед запросом
            $cacheKey = 'dadata_party_info_' . md5($inn);
            if (isset($this->cache[$cacheKey])) {
                return $this->cache[$cacheKey];
            }

            // Формируем URL для запроса
            $url = $this->apiUrl . '/clean/party';

            // Подготавливаем данные для запроса
            $data = [
                'query' => $inn
            ];

            // Выполняем запрос к API DaData
            $response = $this->httpClient->post($url, [
                'json' => $data
            ]);

            // Обрабатываем ответ
            $body = json_decode($response->getBody()->getContents(), true);
            if (isset($body['value']) && !empty($body['value'])) {
                $result = [
                    'valid' => true,
                    'party_info' => $body['value']
                ];
            } else {
                $result = [
                    'valid' => false,
                    'message' => 'Party info not found'
                ];
            }

            // Кэшируем результат
            $this->cache[$cacheKey] = $result;
            return $result;
        } catch (RequestException $e) {
            // Если ошибка запроса, возвращаем недействительный результат
            return [
                'valid' => false,
                'message' => 'DaData API error: ' . $e->getMessage()
            ];
        } catch (Exception $e) {
            throw new Exception('Failed to get party info: ' . $e->getMessage());
        }
    }

    /**
     * Очистка кэша
     */
    public function clearCache(): void
    {
        $this->cache = [];
    }

    /**
     * Получение размера кэша
     */
    public function getCacheSize(): int
    {
        return count($this->cache);
    }

    /**
     * Проверка валидности ИНН
     */
    public function validateInn(string $inn): bool
    {
        try {
            $result = $this->findParty($inn);
            return isset($result['valid']) && $result['valid'];
        } catch (Exception $e) {
            return false;
        }
    }
}