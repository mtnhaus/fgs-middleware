<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Services\Usga\Client;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Log;

class GolfersController extends Controller
{
    public function get(Client $usga, int $id): JsonResponse
    {
        $params = request()->validate([
            'first_name' => ['required', 'string', 'max:255'],
            'last_name' => ['required', 'string', 'max:255'],
        ]);

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
            strcasecmp($params['first_name'], $golfer['first_name']) !== 0
            || strcasecmp($params['last_name'], $golfer['last_name']) !== 0
        ) {
            return response()->json(['message' => 'Bad Request'], 400);
        }

        return response()->json($golfer);
    }
}
