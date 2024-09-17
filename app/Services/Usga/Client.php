<?php

declare(strict_types=1);

namespace App\Services\Usga;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Client
{
    private const TOKEN_CACHE_KEY = 'usga_token';
    private const TOKEN_HOURS_TTL = 12;

    public function authenticate(): string
    {
        if (Cache::has(self::TOKEN_CACHE_KEY)) {
            return Cache::get(self::TOKEN_CACHE_KEY);
        }

        $response = Http::post(config('usga.api.base_url') . '/users/login.json', [
            'user' => [
                'email' => config('usga.api.user.email'),
                'password' => config('usga.api.user.password'),
                'remember_me' => true,
            ]
        ]);

        if (!$response->ok()) {
            $response->throw();
        }

        $token = (string) $response->json('token');

        Cache::put(self::TOKEN_CACHE_KEY, $token, now()->addHours(self::TOKEN_HOURS_TTL)->subMinute());

        return $token;
    }
}
