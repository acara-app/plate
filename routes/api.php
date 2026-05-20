<?php

declare(strict_types=1);

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

Route::prefix('v2/chat')
    ->middleware(['auth:sanctum', 'abilities:chat:converse'])
    ->group(function (): void {
        Route::get('conversations', [ApiV2\ChatController::class, 'index'])
            ->middleware('throttle:60,1')
            ->name('api.v2.chat.index');

        Route::get('conversations/{conversation}', [ApiV2\ChatController::class, 'show'])
            ->middleware('throttle:60,1')
            ->name('api.v2.chat.show');

        Route::post('conversations/{conversation}/stream', [ApiV2\ChatController::class, 'stream'])
            ->middleware('throttle:30,1')
            ->name('api.v2.chat.stream');

        Route::delete('conversations/{conversation}', [ApiV2\ChatController::class, 'destroy'])
            ->middleware('throttle:30,1')
            ->name('api.v2.chat.destroy');
    });
