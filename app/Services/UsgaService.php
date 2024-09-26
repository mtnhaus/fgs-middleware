<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsgaService
{
    private const TOKEN_CACHE_KEY = 'usga_token';
    private const TOKEN_HOURS_TTL = 12;

    private const GOLFERS_CACHE_KEY = 'usga_golfers_';
    private const GOLFERS_HOURS_TTL = 1;

    /**
     * @link https://app.swaggerhub.com/apis-docs/GHIN/GHIN2020AllSandbox/1.0#/User%20APIs/post_users_login__format_ Swagger
     */
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

    /**
     * @link https://app.swaggerhub.com/apis-docs/GHIN/GHIN2020AllSandbox/1.0#/Golfer%20APIs/get_golfers_search__format_ Swagger
     */
    public function getGolfers(array $ids): ?array
    {
        sort($ids, SORT_NUMERIC);
        $idsKey = md5(implode('_', $ids));

        if (Cache::has(self::GOLFERS_CACHE_KEY . $idsKey)) {
            return Cache::get(self::GOLFERS_CACHE_KEY . $idsKey);
        }

        $response = Http::withToken($this->authenticate())->get(
            config('usga.api.base_url') . '/golfers/search.json',
            [
                'per_page' => count($ids),
                'page' => 1,
                'golfer_id' => implode(',', $ids),
            ]
        );

        if (!$response->ok()) {
            Log::channel('usga')->error('Failed to fetch golfers', [
                'code' => $response->status(),
                'response' => $response->body(),
                'golfer_ids' => $ids,
            ]);

            $response->throw();
        }

        $golfers = $response->json('golfers', []);

        Cache::put(self::GOLFERS_CACHE_KEY . $idsKey, $golfers, now()->addHours(self::GOLFERS_HOURS_TTL));

        return $golfers;
    }
}
