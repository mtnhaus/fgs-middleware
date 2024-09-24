<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\GetGolferRequest;
use App\Services\Usga\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GolfersController extends Controller
{
    public function get(GetGolferRequest $request, Client $usga, int $id): JsonResponse
    {
        try {
            $golfer = $usga->getGolfer($id);
        } catch (\Throwable $e) {
            Log::error('Error fetching golfer from USGA API', ['exception' => $e]);
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

        return response()->json($golfer);
    }
}
