<?php

use App\Http\Controllers\V1\CurrencyController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('currencies', CurrencyController::class)->parameters(['currencies' => 'guid']);
});
