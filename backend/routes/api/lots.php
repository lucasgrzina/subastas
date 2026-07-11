<?php

use App\Http\Controllers\V1\LotController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('lots', LotController::class)->parameters(['lots' => 'guid']);

    Route::post('lots/{guid}/bids', [LotController::class, 'storeBid']);
    Route::get('lots/{guid}/bids', [LotController::class, 'bids']);
    Route::post('lots/{guid}/close', [LotController::class, 'close']);
});
