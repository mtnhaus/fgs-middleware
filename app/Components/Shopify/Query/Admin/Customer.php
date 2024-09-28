<?php

declare(strict_types=1);

namespace App\Components\Shopify\Query\Admin;

class Customer
{
    private const QUERY = <<<'GQL'
        query ($id: ID!) {
            customer(id: $id) {
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
    GQL;

    public static function build(string $id): array
    {
        return [
            'query' => self::QUERY,
            'variables' => [
                'id' => $id,
            ],
        ];
    }
}
