<?php

declare(strict_types=1);

namespace App\Services;

use App\Components\Shopify\GraphqlClient;
use App\Components\Shopify\Mutation\CustomerUpdate;
use App\Components\Shopify\Query\Customers;
use App\Services\Shopify\Query;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class ShopifyService
{
    public function __construct(private GraphqlClient $client)
    {
    }

    public function getCustomers(int $pageSize = 50, ?string $endCursor = null): array
    {
        $response = $this->client->query(Customers::build($pageSize, $endCursor));

        if ($response->getStatusCode() !== 200) {
            Log::channel('shopify')->error('Failed to fetch customers from Shopify.', [
                'code' => $response->getStatusCode(),
                'response' => $response->getBody()->getContents(),
            ]);

            throw new \RuntimeException('Failed to fetch customers from Shopify.');
        }

        $data = $response->getDecodedBody();

        $hasNextPage = Arr::get($data, 'data.customers.pageInfo.hasNextPage', false);
        $endCursor = Arr::get($data, 'data.customers.pageInfo.endCursor', '');
        $customers = array_map(fn($node) => $node['node'], Arr::get($data, 'data.customers.edges', []));

        return [$hasNextPage, $endCursor, $customers];
    }

    public function updateCustomer(array $input): void
    {
        $response = $this->client->query(CustomerUpdate::build($input));

        if ($response->getStatusCode() !== 200) {
            Log::channel('shopify')->error('Failed to update customer in Shopify.', [
                'code' => $response->getStatusCode(),
                'response' => $response->getBody()->getContents(),
            ]);

            throw new \RuntimeException('Failed to update customer in Shopify.');
        }
    }
}
