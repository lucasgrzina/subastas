<?php

use App\Http\Controllers\V1\ProductController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::apiResource('products', ProductController::class)->parameters(['products' => 'guid']);

    Route::patch('products/{guid}/restore', [ProductController::class, 'restore']);
});
