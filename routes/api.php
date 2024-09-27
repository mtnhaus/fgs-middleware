<?php

declare(strict_types=1);

use App\Http\Controllers\CustomersController;
use App\Http\Middleware\VerifyAuthToken;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyAuthToken::class)->group(function () {
    Route::post('/customers', [CustomersController::class, 'create']);
    Route::post('/customers/verify', [CustomersController::class, 'verify']);
});
