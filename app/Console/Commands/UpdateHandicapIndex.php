<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use App\Services\UsgaService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;

class UpdateHandicapIndex extends Command implements Isolatable
{
    protected $signature = 'app:update-handicap-index {--page-size=50 : Page size for Shopify API}';

    protected $description = 'Update golfers\' handicap index';

    public function __construct(private ShopifyService $shopify, private UsgaService $usga)
    {
        parent::__construct();
    }

    public function handle()
    {
        $this->info('Updating handicap index');
        $this->newLine();

        $pageSize = (int) $this->option('page-size');
        $endCursor = null;

        do {
            [$hasNextPage, $endCursor, $customers] = $this->shopify->getCustomers($pageSize, $endCursor);

            $this->line(sprintf('Processing batch of %d customers...', count($customers)));

            $customers = Arr::where($customers, fn($customer) => Arr::has($customer, 'golfer_id.value'));
            $golferIds = Arr::pluck($customers, 'golfer_id.value');

            try {
                $golfers = $this->usga->getGolfers($golferIds);
            } catch (\Throwable) {
                // Just continue processing. Error logged elsewhere.
                continue;
            }

            if (!$golfers) {
                continue;
            }

            $handicapIndexes = Arr::mapWithKeys(
                $golfers,
                fn($golfer) => [Arr::get($golfer, 'ghin') => Arr::get($golfer, 'handicap_index')]
            );

            foreach ($customers as $customer) {
                $this->updateCustomer($customer, $handicapIndexes);
            }
        } while ($hasNextPage);

        $this->newLine();
        $this->info('Done');
    }

    private function updateCustomer(array $customer, array &$handicapIndexes): void
    {
        $golferId = Arr::get($customer, 'golfer_id.value');
        $handicapIndex = Arr::get($customer, 'handicap_index.value', '');
        $newHandicapIndex = Arr::get($handicapIndexes, $golferId);

        if ($handicapIndex && $handicapIndex === $newHandicapIndex) {
            return;
        }

        $metafield = [
            'namespace' => 'customer',
            'key' => 'handicap_index',
            'value' => $newHandicapIndex,
        ];

        $metafieldId = Arr::get($customer, 'handicap_index.id');

        if ($metafieldId) {
            Arr::set($metafield, 'id', $metafieldId);
        }

        try {
            $this->shopify->updateCustomer(['id' => Arr::get($customer, 'id'), 'metafields' => [$metafield]]);
        } catch (\Throwable) {
            // Just continue processing. Error logged elsewhere.
        }
    }
}
