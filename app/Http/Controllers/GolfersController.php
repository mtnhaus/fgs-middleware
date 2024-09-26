<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GetGolferRequest;
use App\Services\UsgaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class GolfersController extends Controller
{
    public function get(GetGolferRequest $request, UsgaService $usga, int $id): JsonResponse
    {
        try {
            $golfer = Arr::first($usga->getGolfers([$id]));
        } catch (\Throwable $e) {
            Log::channel('usga')->error("Failed to fetch golfer with ID {$id}.", ['exception' => $e]);
            return response()->json(['message' => 'Unexpected Error'], 500);
        }

        if (!$golfer) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        if (
            strcasecmp($request->validated('first_name'), $golfer['first_name']) !== 0
            || strcasecmp($request->validated('last_name'), $golfer['last_name']) !== 0
        ) {
            return response()->json(['message' => 'Bad Request'], 400);
        }

        return response()->json(Arr::only($golfer, ['first_name', 'last_name', 'email', 'handicap_index']));
    }
}
