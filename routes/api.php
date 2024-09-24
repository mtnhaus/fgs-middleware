<?php

use App\Http\Controllers\GolfersController;
use App\Http\Middleware\VerifyAuthToken;
use Illuminate\Support\Facades\Route;

Route::middleware(VerifyAuthToken::class)->group(function () {
    Route::get('/golfers/{id}', [GolfersController::class, 'get']);
});
