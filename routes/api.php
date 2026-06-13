<?php

declare(strict_types=1);

use App\Http\Controllers\Api\V2 as ApiV2;
use App\Http\Controllers\ChatStopController;
use App\Http\Controllers\ChatStreamEventsController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Broadcast;
use Illuminate\Support\Facades\Route;

Route::post('v2/broadcasting/auth', fn (Request $request) => Broadcast::auth($request))
    ->middleware(['auth:sanctum', 'throttle:60,1'])
    ->name('api.v2.broadcasting.auth');

Route::prefix('v2/sync')->group(function (): void {
    Route::post('pair', ApiV2\MobileSyncPairController::class)
        ->middleware('throttle:5,1')
        ->name('api.v2.sync.pair');

    Route::post('devices', ApiV2\MobileSyncDeviceRegistrationController::class)
        ->middleware(['auth:sanctum', 'throttle:10,1'])
        ->name('api.v2.sync.devices');

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
            ->name('api.v2.chat.index');

        Route::get('conversations/{conversation}', [ApiV2\ChatController::class, 'show'])
            ->name('api.v2.chat.show');

        Route::post('conversations/{conversation}/stream', ApiV2\BroadcastChatController::class)
            ->name('api.v2.chat.stream');

        Route::post('conversations/{conversation}/stream/stop', ChatStopController::class)
            ->name('api.v2.chat.stream.stop');

        Route::get('conversations/{conversation}/stream/events', ChatStreamEventsController::class)
            ->name('api.v2.chat.stream.events');

        Route::delete('conversations/{conversation}', [ApiV2\ChatController::class, 'destroy'])
            ->name('api.v2.chat.destroy');

        Route::get('conversations/{conversation}/approvals/{approval}', [ApiV2\ApprovalController::class, 'show'])
            ->name('api.v2.chat.approvals.show');

        Route::post('conversations/{conversation}/approvals/{approval}/approve', [ApiV2\ApprovalController::class, 'approve'])
            ->name('api.v2.chat.approvals.approve');

        Route::post('conversations/{conversation}/approvals/{approval}/reject', [ApiV2\ApprovalController::class, 'reject'])
            ->name('api.v2.chat.approvals.reject');
    });

Route::prefix('v2/auth')->group(function (): void {
    Route::post('nonce', ApiV2\Auth\NonceController::class)
        ->middleware('throttle:10,1')
        ->name('api.v2.auth.nonce');

    Route::post('login', ApiV2\Auth\LoginController::class)
        ->middleware('throttle:10,1')
        ->name('api.v2.auth.login');

    Route::post('two-factor', ApiV2\Auth\TwoFactorChallengeController::class)
        ->middleware('throttle:10,1')
        ->name('api.v2.auth.two-factor');

    Route::post('google', ApiV2\Auth\GoogleAuthController::class)
        ->middleware('throttle:10,1')
        ->name('api.v2.auth.google');

    Route::post('apple', ApiV2\Auth\AppleAuthController::class)
        ->middleware('throttle:10,1')
        ->name('api.v2.auth.apple');

    Route::get('capabilities', ApiV2\Auth\CapabilitiesController::class)
        ->middleware('throttle:30,1')
        ->name('api.v2.auth.capabilities');

    Route::middleware('auth:sanctum')->group(function (): void {
        Route::get('me', ApiV2\Auth\MeController::class)
            ->middleware('throttle:60,1')
            ->name('api.v2.auth.me');
    });
});

Route::prefix('v2/account')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::post('consent', ApiV2\Account\AcceptConsentController::class)
            ->middleware('throttle:30,1')
            ->name('api.v2.account.consent');
    });
