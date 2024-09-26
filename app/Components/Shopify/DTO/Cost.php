<?php

declare(strict_types=1);

namespace App\Components\Shopify\DTO;

use Illuminate\Support\Arr;

class Cost
{
    public int $requestedQueryCost = 0;
    public ?int $actualQueryCost = 0;
    public ?CostThrottleStatus $throttleStatus = null;

    public function __construct(array $cost)
    {
        $this->requestedQueryCost = (int) Arr::get($cost, 'requestedQueryCost', 0);
        $this->actualQueryCost = (int) Arr::get($cost, 'actualQueryCost') ?: null;
        $this->throttleStatus = new CostThrottleStatus(Arr::get($cost, 'throttleStatus', []));
    }
}
