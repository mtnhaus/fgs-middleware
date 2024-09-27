<?php

declare(strict_types=1);

namespace App\Http\Controllers;

use App\Http\Requests\CreateCustomerRequest;
use App\Http\Requests\VerifyCustomerRequest;
use App\Services\ShopifyService;
use App\Services\UsgaService;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\Log;

class CustomersController extends Controller
{
    public function create(CreateCustomerRequest $request, ShopifyService $shopifyService): JsonResponse
    {
        try {
            $shopifyService->createCustomer($request->validated());
        } catch (\Throwable) {
            return response()->json(['message' => 'Failed to create customer.'], status: 500);
        }

        return response()->json(status: 201);
    }

    public function verify(VerifyCustomerRequest $request, UsgaService $usga): JsonResponse
    {
        try {
            $golferId = $request->validated('ghin_number');
            $golfers = $usga->getGolfers([$golferId]);
            $golfer = Arr::first($golfers);
        } catch (\Throwable $e) {
            return response()->json(['message' => 'Unexpected Error'], 500);
        }

        if (!$golfer || strcasecmp($request->validated('last_name'), $golfer['last_name']) !== 0) {
            return response()->json(['message' => 'Not Found'], 404);
        }

        return response()->json(Arr::only($golfer, ['first_name', 'last_name', 'handicap_index', 'tier']));
    }
}
