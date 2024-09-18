<?php

declare(strict_types=1);

namespace App\Services\Usga;

use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;

class Client
{
    private const TOKEN_CACHE_KEY = 'usga_token';
    private const TOKEN_HOURS_TTL = 12;

    private const GOLFER_CACHE_KEY = 'usga_golfer_';
    private const GOLFER_HOURS_TTL = 1;

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
    public function getGolfer(int $id): ?array
    {
        if (Cache::has(self::GOLFER_CACHE_KEY . $id)) {
            return Cache::get(self::GOLFER_CACHE_KEY . $id);
        }

        $response = Http::withToken($this->authenticate())->get(
            config('usga.api.base_url') . '/golfers/search.json',
            [
                'per_page' => 1,
                'page' => 1,
                'golfer_id' => $id,
            ]
        );

        if (!$response->ok()) {
            $response->throw();
        }

        $golfers = $response->json('golfers');

        if (!isset($golfers[0])) {
            return null;
        }

        $golfer = Arr::only($golfers[0], ['first_name', 'last_name', 'email', 'state', 'handicap_index']);

        Cache::put(self::GOLFER_CACHE_KEY . $id, $golfer, now()->addHours(self::GOLFER_HOURS_TTL));

        return $golfer;
    }
}
