<?php

declare(strict_types=1);

use App\Http\Controllers\Api\Messaging\ChatTurnsController;
use App\Http\Controllers\Api\V2 as ApiV2;
use Illuminate\Support\Facades\Route;

Route::prefix('v2/sync')->group(function (): void {
    Route::post('pair', ApiV2\MobileSyncPairController::class)
        ->middleware('throttle:5,1')
        ->name('api.v2.sync.pair');

    Route::middleware(['auth:sanctum', 'abilities:sync:push'])->group(function (): void {
        Route::post('health-entries', ApiV2\MobileSyncHealthEntriesController::class)
            ->middleware('throttle:60,1')
            ->name('api.v2.sync.health-entries');
    });
});

Route::middleware('sidecar.signature')
    ->prefix('v2/messaging/platforms/{platform}/users/{platformUserId}')
    ->name('api.v2.messaging.')
    ->group(function (): void {
        Route::post('chat-turns', [ChatTurnsController::class, 'store'])
            ->name('chat-turns.store');
    });
