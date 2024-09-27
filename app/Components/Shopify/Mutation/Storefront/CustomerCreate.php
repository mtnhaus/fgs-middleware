<?php

declare(strict_types=1);

namespace App\Components\Shopify\Mutation\Storefront;

class CustomerCreate
{
    private const QUERY = <<<'GQL'
        mutation ($input: CustomerCreateInput!) {
            customerCreate(input: $input) {
                customer {
                    id
                }
                customerUserErrors {
                    message
                    field
                }
            }
        }
    GQL;

    public static function build(array $input): array
    {
        return [
            'query' => self::QUERY,
            'variables' => [
                'input' => $input,
            ],
        ];
    }
}
