<?php

use App\Http\Controllers\V1\AuctionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('auctions', AuctionController::class)->parameters(['auctions' => 'guid']);
});
