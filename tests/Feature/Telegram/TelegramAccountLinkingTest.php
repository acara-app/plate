<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserChatPlatformLink;
use App\Services\Telegram\TelegramWebhookHandler;

covers(TelegramWebhookHandler::class);

it('links telegram account and removes duplicates for the same user', function (): void {
    $user = User::factory()->create();

    $existingLink = UserChatPlatformLink::factory()->telegram()->linked($user)->create([
        'platform_user_id' => '123456789',
    ]);

    $pendingLink = UserChatPlatformLink::factory()->telegram()->pending($user)->create([
        'linking_token' => 'ABC123XY',
        'token_expires_at' => now()->addHours(24),
    ]);

    UserChatPlatformLink::query()
        ->where('platform', 'telegram')
        ->where('id', '!=', $pendingLink->id)
        ->where(function ($query) use ($pendingLink): void {
            $query->where('user_id', $pendingLink->user_id)
                ->orWhere('platform_user_id', '123456789');
        })
        ->delete();

    $pendingLink->update(['platform_user_id' => '123456789']);

    expect(UserChatPlatformLink::query()->find($existingLink->id))->toBeNull()
        ->and($pendingLink->fresh()->platform_user_id)->toBe('123456789');
});
