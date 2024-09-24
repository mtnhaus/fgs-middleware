<?php

declare(strict_types=1);

namespace App\Providers;

use App\Services\UsgaService;
use Illuminate\Support\ServiceProvider;

class UsgaServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->app->singleton(UsgaService::class, fn() => new UsgaService());
    }
}
