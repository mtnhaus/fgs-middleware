<?php

declare(strict_types=1);

namespace App\Providers;

use App\Components\Shopify\Client\Graphql;
use App\Services\ShopifyService;
use Illuminate\Support\ServiceProvider;
use Shopify\Auth\FileSessionStorage;
use Shopify\Context;

class ShopifyServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        Context::initialize(
            config('shopify.api.key'),
            config('shopify.api.access_token'),
            config('shopify.api.scopes'),
            config('app.url'),
            new FileSessionStorage(),
            config('shopify.api.version'),
            false,
            true
        );

        $this->app->singleton(Graphql::class, fn() => new Graphql(
            config('shopify.domain'),
            config('shopify.api.access_token')
        ));

        $this->app->singleton(ShopifyService::class, fn() => new ShopifyService(
            app(Graphql::class)
        ));
    }
}
