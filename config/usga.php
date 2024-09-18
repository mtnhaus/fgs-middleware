<?php

return [
    'api' => [
        'base_url' => trim(env('USGA_API_BASE_URL', ''), '/'),
        'user' => [
            'email' => env('USGA_API_USER_EMAIL', ''),
            'password' => env('USGA_API_USER_PASSWORD', ''),
        ],
    ],
];
