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

        Route::patch('conversations/{conversation}/pin', [ApiV2\ChatController::class, 'pin'])
            ->name('api.v2.chat.pin');

        Route::patch('conversations/{conversation}/unpin', [ApiV2\ChatController::class, 'unpin'])
            ->name('api.v2.chat.unpin');

        Route::patch('conversations/{conversation}/keep', [ApiV2\ChatController::class, 'keep'])
            ->name('api.v2.chat.keep');

        Route::patch('conversations/{conversation}/unkeep', [ApiV2\ChatController::class, 'unkeep'])
            ->name('api.v2.chat.unkeep');

        Route::post('conversations/{conversation}/approvals', ApiV2\ApprovalDecisionController::class)
            ->name('api.v2.chat.approvals.decide');
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

        Route::put('timezone', ApiV2\Account\UpdateTimezoneController::class)
            ->middleware('throttle:10,1,api-timezone')
            ->name('api.v2.account.timezone');
    });

Route::prefix('v2/snap-to-track')
    ->middleware('auth:sanctum')
    ->group(function (): void {
        Route::post('analyze', ApiV2\SnapToTrack\AnalyzeSnapToTrackPhotoController::class)
            ->middleware('throttle:snap-to-track-analyze')
            ->name('api.v2.snap-to-track.analyze');

        Route::post('drafts/{draft}/meal', ApiV2\SnapToTrack\StoreSnapToTrackMealController::class)
            ->middleware('throttle:60,1')
            ->name('api.v2.snap-to-track.meal');

        Route::post('estimate-item', ApiV2\SnapToTrack\EstimateSnapToTrackItemController::class)
            ->middleware('throttle:20,1,api-snap-estimate')
            ->name('api.v2.snap-to-track.estimate-item');
    });

Route::prefix('v2/health-entries')
    ->middleware(['auth:sanctum', 'throttle:120,1,api-logbook'])
    ->group(function (): void {
        Route::get('/', ApiV2\HealthEntry\ListHealthEntryController::class)
            ->name('api.v2.health-entries.index');

        Route::get('logged-dates', ApiV2\HealthEntry\ListLoggedDatesController::class)
            ->name('api.v2.health-entries.logged-dates');

        Route::get('suggestions', ApiV2\HealthEntry\ListHealthEntrySuggestionsController::class)
            ->name('api.v2.health-entries.suggestions');

        Route::post('/', ApiV2\HealthEntry\StoreHealthEntryController::class)
            ->name('api.v2.health-entries.store');

        Route::post('water', ApiV2\HealthEntry\StoreWaterIntakeController::class)
            ->name('api.v2.health-entries.water.store');

        Route::post('check-in', ApiV2\HealthEntry\StoreDailyCheckInController::class)
            ->name('api.v2.health-entries.check-in.store');

        Route::get('{group}/photo', ApiV2\HealthEntry\ShowMealPhotoController::class)
            ->whereUuid('group')
            ->name('api.v2.health-entries.photo');

        Route::get('{healthSyncSample}', ApiV2\HealthEntry\ShowHealthEntryController::class)
            ->whereNumber('healthSyncSample')
            ->name('api.v2.health-entries.show');

        Route::put('{healthSyncSample}', ApiV2\HealthEntry\UpdateHealthEntryController::class)
            ->whereNumber('healthSyncSample')
            ->name('api.v2.health-entries.update');

        Route::delete('{healthSyncSample}', ApiV2\HealthEntry\DestroyHealthEntryController::class)
            ->whereNumber('healthSyncSample')
            ->name('api.v2.health-entries.destroy');
    });
