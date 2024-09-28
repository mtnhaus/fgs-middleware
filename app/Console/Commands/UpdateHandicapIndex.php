<?php

namespace App\Console\Commands;

use App\Services\ShopifyService;
use App\Services\UsgaService;
use Illuminate\Console\Command;
use Illuminate\Contracts\Console\Isolatable;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

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
        $startedAt = microtime(true);

        $this->info('Updating handicap index');
        $this->newLine();

        $pageSize = (int) $this->option('page-size');
        $endCursor = null;

        do {
            [$hasNextPage, $endCursor, $customers] = $this->shopify->getCustomers($pageSize, $endCursor);

            $this->line(sprintf('Processing batch of %d customers...', count($customers)));

            $customers = Arr::where($customers, fn($customer) => Arr::has($customer, 'ghin_number.value'));
            $golferIds = Arr::pluck($customers, 'ghin_number.value');

            try {
                $golfers = $this->usga->getGolfers($golferIds);
            } catch (\Throwable) {
                // Just continue processing. Error logged elsewhere.
                continue;
            }

            if (!$golfers) {
                continue;
            }

            $golfers = Arr::mapWithKeys(
                $golfers,
                fn($golfer) => [Arr::get($golfer, 'ghin') => $golfer]
            );

            foreach ($customers as $customer) {
                $this->updateCustomer($customer, $golfers);
            }
        } while ($hasNextPage);

        $this->newLine();
        $this->info('Done');

        $finishedAt = microtime(true);
        $processTime = number_format($finishedAt - $startedAt, 2);

        Log::info('Handicap Index Update completed in ' . $processTime . ' seconds');
    }

    private function updateCustomer(array $customer, array &$golfers): void
    {
        $golferId = Arr::get($customer, 'ghin_number.value');
        $handicapIndex = Arr::get($customer, 'handicap_index.value', '');
        $golfer = Arr::get($golfers, $golferId);
        $newHandicapIndex = Arr::get($golfer, 'handicap_index', '');
        $newTier = Arr::get($golfer, 'tier', '');

        if ($handicapIndex && $handicapIndex === $newHandicapIndex) {
            return;
        }

        try {
            $this->shopify->updateCustomer(
                customer: $customer,
                metafields: ['handicap_index' => $newHandicapIndex, 'tier' => $newTier]
            );
        } catch (\Throwable) {
            // Just continue processing. Error logged elsewhere.
        }
    }
}
