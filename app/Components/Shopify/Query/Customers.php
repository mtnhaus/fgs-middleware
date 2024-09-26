<?php

declare(strict_types=1);

namespace App\Components\Shopify\Query;

class Customers
{
    private const QUERY = <<<'GQL'
        query ($first: Int, $after: String) {
            customers(first: $first, after: $after) {
                pageInfo {
                    hasNextPage
                    endCursor
                }
                edges {
                    node {
                        id
                        golfer_id: metafield(
                            namespace: "customer"
                            key: "golfer_id"
                        ) {
                            id
                            value
                        }
                        handicap_index: metafield(
                            namespace: "customer"
                            key: "handicap_index"
                        ) {
                            id
                            value
                        }
                    }
                }
            }
        }
    GQL;

    public static function build(int $first = 50, ?string $after = null): array
    {
        return [
            'query' => self::QUERY,
            'variables' => [
                'first' => $first,
                'after' => $after,
            ],
        ];
    }
}
