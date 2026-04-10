<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V1 as ApiV1;
use App\Http\Controllers\Api\V2 as ApiV2;
use Illuminate\Support\Facades\Route;

Route::prefix('v1/sync')->group(function (): void {
    Route::post('pair', ApiV1\MobileSyncPairController::class)
        ->middleware('throttle:5,1')
        ->name('api.v1.sync.pair');

    Route::middleware(['auth:sanctum', 'abilities:sync:push'])->group(function (): void {
        Route::post('health-entries', ApiV1\MobileSyncHealthEntriesController::class)
            ->middleware('throttle:60,1')
            ->name('api.v1.sync.health-entries');
    });
});

Route::prefix('v2/sync')->group(function (): void {
    Route::middleware(['auth:sanctum', 'abilities:sync:push'])->group(function (): void {
        Route::post('health-entries', ApiV2\MobileSyncHealthEntriesController::class)
            ->middleware('throttle:60,1')
            ->name('api.v2.sync.health-entries');
    });
});
