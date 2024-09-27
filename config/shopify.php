<?php

return [
    'domain' => env('SHOPIFY_DOMAIN', ''),
    'api' => [
        'key' => env('SHOPIFY_API_KEY', ''),
        'secret_key' => env('SHOPIFY_API_SECRET_KEY', ''),
        'access_token' => env('SHOPIFY_API_ACCESS_TOKEN', ''),
        'storefront_access_token' => env('SHOPIFY_API_STOREFRONT_ACCESS_TOKEN', ''),
        'scopes' => env('SHOPIFY_API_SCOPES', ''),
        'version' => env('SHOPIFY_API_VERSION', ''),
    ],
];
