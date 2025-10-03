<?php

declare(strict_types=1);

namespace App\Factory;

use App\Contract\Elasticsearch\ElasticsearchSearchRequest;
use App\Contract\ProductSearchFilters;

final class ElasticsearchSearchRequestFactory
{
    public static function fromProductSearchFilters(ProductSearchFilters $filters): ElasticsearchSearchRequest
    {
        $must = [];
        $should = [];

        if (!empty($filters->query)) {
            $should[] = [
                'match' => [
                    'name' =>
                        [
                            'query' => $filters->query, 'fuzziness' => 'AUTO'
                        ]
                ]
            ];
            $should[] = [
                'match' => [
                    'description' =>
                        [
                            'query' => $filters->query, 'fuzziness' => 'AUTO'
                        ]
                ]
            ];
            $should[] = [
                'match' => [
                    'inn' => $filters->query
                ]
            ];
            $should[] = [
                'match' => [
                    'barcode' => $filters->query
                ]
            ];
        }

        if ($filters->categoryId !== null) {
            $must[] = ['term' => ['categories.id' => $filters->categoryId]];
        }

        if (!empty($filters->inn)) {
            $must[] = ['term' => ['inn' => $filters->inn]];
        }

        if (!empty($filters->barcode)) {
            $must[] = ['term' => ['barcode' => $filters->barcode]];
        }

        $query = [];
        if (!empty($must)) {
            $query['bool']['must'] = $must;
        }
        if (!empty($should)) {
            $query['bool']['should'] = $should;
            $query['bool']['minimum_should_match'] = 1;
        }

        if (empty($query)) {
            $query = ['match_all' => []];
        }

        return new ElasticsearchSearchRequest(
            query: $query,
            from: $filters->offset,
            size: $filters->limit
        );
    }
}
