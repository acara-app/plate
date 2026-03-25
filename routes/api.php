<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1 as ApiV1;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/sync')->group(function (): void {
    Route::post('pair', ApiV1\MobileSyncPairController::class)
        ->middleware('throttle:5,1')
        ->name('api.v1.sync.pair');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::post('health-entries', ApiV1\MobileSyncHealthEntriesController::class)
            ->name('api.v1.sync.health-entries');
    });
});
