<?php

use App\Http\Controllers\V1\ReferenceDataController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->middleware('auth:sanctum')->group(function () {
    Route::get('wineries', [ReferenceDataController::class, 'wineries']);
    Route::get('grape-varieties', [ReferenceDataController::class, 'grapeVarieties']);
    Route::get('wine-regions', [ReferenceDataController::class, 'wineRegions']);
});
