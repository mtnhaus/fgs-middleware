<?php

declare(strict_types=1);

namespace App\Components\Shopify\Client;

use Shopify\Clients\HttpHeaders;
use Shopify\Context;

class Storefront extends Graphql
{
    protected function getApiPath(): string
    {
        return 'api/' . Context::$API_VERSION . '/graphql.json';
    }

    protected function getAccessTokenHeader(): array
    {
        $accessToken = Context::$IS_PRIVATE_APP
            ? (Context::$PRIVATE_APP_STOREFRONT_ACCESS_TOKEN ?: $this->token)
            : $this->token;

        return [HttpHeaders::X_SHOPIFY_STOREFRONT_ACCESS_TOKEN, $accessToken];
    }
}
