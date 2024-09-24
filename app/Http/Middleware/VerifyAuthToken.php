<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class VerifyAuthToken
{
    public function handle(Request $request, \Closure $next): JsonResponse
    {
        if ($request->header('Authorization') !== 'Bearer ' . config('auth.token')) {
            abort(401, 'Unauthorized');
        }

        return $next($request);
    }
}
