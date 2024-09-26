<?php

declare(strict_types=1);

namespace App\Components\Shopify\DTO;

use Illuminate\Support\Arr;

class CostThrottleStatus
{
    public int $maximumAvailable = 0;
    public int $currentlyAvailable = 0;
    public int $restoreRate = 0;

    public function __construct(array $throttleStatus)
    {
        $this->maximumAvailable = (int) Arr::get($throttleStatus, 'maximumAvailable', 0);
        $this->currentlyAvailable = (int) Arr::get($throttleStatus, 'currentlyAvailable', 0);
        $this->restoreRate = (int) Arr::get($throttleStatus, 'restoreRate', 0);
    }
}
