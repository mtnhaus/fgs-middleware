<?php

declare(strict_types=1);

namespace App\Components\Shopify\Query\Admin;

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
                        ghin_number: metafield(
                            namespace: "customer"
                            key: "ghin_number"
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
                        },
                        tier: metafield(
                            namespace: "customer"
                            key: "tier"
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
