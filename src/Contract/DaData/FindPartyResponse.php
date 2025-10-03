<?php

declare(strict_types=1);

namespace App\Contract\DaData;

final readonly class FindPartyResponse
{
    public function __construct(
        public array $suggestions = [],
    )
    {
    }

    public static function fromApiResponse(array $data): self
    {
        if (!isset($data['suggestions'])) {
            return new self();
        }

        $suggestions = [];
        foreach ($data['suggestions'] as $suggestionData) {
            $suggestions[] = new Suggestion($suggestionData['value'], $suggestionData['inn']);
        }

        return new self($suggestions);
    }
}
