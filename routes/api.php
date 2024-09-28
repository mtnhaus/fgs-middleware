<?php

declare(strict_types=1);

use App\Http\Controllers\CustomersController;
use App\Http\Middleware\VerifyAuthToken;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyAuthToken::class)->group(function () {
    Route::patch('/customers', [CustomersController::class, 'update']);
    Route::post('/customers/verify', [CustomersController::class, 'verify']);
});
