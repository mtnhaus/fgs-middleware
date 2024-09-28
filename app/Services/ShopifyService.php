<?php

declare(strict_types=1);

namespace App\Services;

use App\Components\Shopify\Client\Graphql;
use App\Components\Shopify\Client\Storefront;
use App\Components\Shopify\Mutation\Admin\CustomerUpdate;
use App\Components\Shopify\Mutation\Storefront\CustomerCreate;
use App\Components\Shopify\Query\Admin\Customer;
use App\Components\Shopify\Query\Admin\Customers;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    public function __construct(private Graphql $adminClient) {}

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

    public function getCustomer(string $id): array
    {
        try {
            $response = $this->adminClient->query(Customer::build($id));
        } catch (\Exception $e) {
            Log::channel('shopify')->error('Failed to fetch customer from Shopify.', [
                'message' => $e->getMessage(),
            ]);

            throw $e;
        }

        return Arr::get($response->getDecodedBody(), 'data.customer');
    }

    public function updateCustomer(array $customer, array $data = [], array $metafields = []): void
    {
        $input = [
            'id' => Arr::get($customer, 'id'),
        ];

        $input = array_merge($data, $input);

        if ($metafields) {
            $input['metafields'] = [];

            foreach ($metafields as $key => $value) {
                $value = (string) $value;
                $id = Arr::get($customer, "{$key}.id");

                if ($id) {
                    $input['metafields'][] = [
                        'id' => $id,
                        'value' => $value,
                    ];
                } else {
                    $input['metafields'][] = [
                        'namespace' => 'customer',
                        'key' => $key,
                        'value' => $value,
                    ];
                }
            }
        }

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
