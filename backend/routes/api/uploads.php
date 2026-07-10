<?php

use App\Http\Controllers\V1\TempUploadController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/uploads')->middleware('auth:sanctum')->group(function () {
    Route::post('/images', [TempUploadController::class, 'store']);
});
