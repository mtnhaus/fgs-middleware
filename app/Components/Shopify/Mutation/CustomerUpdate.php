<?php

declare(strict_types=1);

namespace App\Components\Shopify\Mutation;

class CustomerUpdate
{
    private const QUERY = <<<'GQL'
        mutation ($input: CustomerInput!) {
            customerUpdate(input: $input) {
                userErrors {
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
