<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\Tier;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class UsgaService
{
    private const RETRY_ATTEMPTS = 3;
    private const RETRY_DELAY_MS = 250;

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

        $request = Http::createPendingRequest();

        $request->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS, throw: false);

        $response = $request->post(config('usga.api.base_url') . '/users/login.json', [
            'user' => [
                'email' => config('usga.api.user.email'),
                'password' => config('usga.api.user.password'),
                'remember_me' => true,
            ],
        ]);

        if (!$response->ok()) {
            Log::channel('usga')->error('Failed to authenticate', [
                'code' => $response->status(),
                'response' => $response->body(),
            ]);

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

        $request = Http::createPendingRequest();

        $request->retry(self::RETRY_ATTEMPTS, self::RETRY_DELAY_MS, throw: false);
        $request->withToken($this->authenticate());

        $response = $request->get(config('usga.api.base_url') . '/golfers/search.json', [
            'per_page' => count($ids),
            'page' => 1,
            'golfer_id' => implode(',', $ids),
        ]);

        if (!$response->ok()) {
            Log::channel('usga')->error('Failed to fetch golfers', [
                'code' => $response->status(),
                'response' => $response->body(),
                'golfer_ids' => $ids,
            ]);

            $response->throw();
        }

        $golfers = Arr::map(
            $response->json('golfers', []),
            function ($golfer) {
                $tier = $this->qualify(Arr::get($golfer, 'handicap_index'));
                Arr::set($golfer, 'tier', $tier);

                return $golfer;
            }
        );

        Cache::put(self::GOLFERS_CACHE_KEY . $idsKey, $golfers, now()->addHours(self::GOLFERS_HOURS_TTL));

        return $golfers;
    }

    public function qualify(string $handicapIndex): string
    {
        return match (true) {
            $handicapIndex === 'NH' => Tier::UNDEFINED->value,
            str_starts_with($handicapIndex, '+') => Tier::FT_PLUS->value,
            (float) $handicapIndex >= 0 && (float) $handicapIndex < 5 => Tier::FT1->value,
            (float) $handicapIndex >= 5 && (float) $handicapIndex < 10 => Tier::FT2->value,
            (float) $handicapIndex >= 10 && (float) $handicapIndex < 15 => Tier::FT3->value,
            (float) $handicapIndex >= 15 && (float) $handicapIndex < 20 => Tier::FT4->value,
            default => Tier::UNDEFINED->value,
        };
    }
}
