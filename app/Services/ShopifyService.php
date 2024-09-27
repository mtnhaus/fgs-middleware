<?php

declare(strict_types=1);

namespace App\Services;

use App\Components\Shopify\Client\Graphql;
use App\Components\Shopify\Client\Storefront;
use App\Components\Shopify\Mutation\Admin\CustomerUpdate;
use App\Components\Shopify\Mutation\Storefront\CustomerCreate;
use App\Components\Shopify\Query\Admin\Customers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    public function __construct(
        private Graphql $adminClient,
        private Storefront $storefrontClient
    ) {
    }

    public function getCustomers(int $pageSize = 50, ?string $endCursor = null): array
    {
        try {
            $response = $this->adminClient->query(Customers::build($pageSize, $endCursor));
        } catch (\Exception $e) {
            Log::channel('shopify')->error('Failed to fetch customers from Shopify.', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $data = $response->getDecodedBody();

        $hasNextPage = Arr::get($data, 'data.customers.pageInfo.hasNextPage', false);
        $endCursor = Arr::get($data, 'data.customers.pageInfo.endCursor', '');
        $customers = array_map(fn($node) => $node['node'], Arr::get($data, 'data.customers.edges', []));

        return [$hasNextPage, $endCursor, $customers];
    }

    public function createCustomer(array $data): string
    {
        $input = [
            'firstName' => Arr::get($data, 'first_name'),
            'lastName' => Arr::get($data, 'last_name'),
            'email' => Arr::get($data, 'email'),
            'password' => Arr::get($data, 'password'),
        ];

        try {
            $response = $this->storefrontClient->query(CustomerCreate::build($input));
        } catch (\Exception $e) {
            Log::channel('shopify')->error('Failed to create customer in Shopify.', [
                'input' => Arr::except($input, 'password'),
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        $customerId = (string) Arr::get($response->getDecodedBody(), 'data.customerCreate.customer.id');

        $input = [
            'id' => $customerId,
            'metafields' => [
                [
                    'namespace' => 'customer',
                    'key' => 'ghin_number',
                    'value' => (string) Arr::get($data, 'ghin_number', ''),
                ],
                [
                    'namespace' => 'customer',
                    'key' => 'handicap_index',
                    'value' => Arr::get($data, 'handicap_index', ''),
                ],
                [
                    'namespace' => 'customer',
                    'key' => 'tier',
                    'value' => Arr::get($data, 'tier', ''),
                ]
            ],
        ];

        $this->updateCustomer($input);

        return $customerId;
    }

    public function updateCustomer(array $input): void
    {
        try {
            $this->adminClient->query(CustomerUpdate::build($input));
        } catch (\Exception $e) {
            Log::channel('shopify')->error('Failed to update customer in Shopify.', [
                'input' => $input,
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }
    }
}
