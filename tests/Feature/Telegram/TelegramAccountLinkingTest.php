<?php

declare(strict_types=1);

use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use App\Services\Telegram\TelegramWebhookHandler;
use DefStudio\Telegraph\Facades\Telegraph;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;
use Tests\Fixtures\TelegramWebhookPayloads;

covers(TelegramWebhookHandler::class);

it('links a pending record via the /link command and clears stale links for the same platform user id', function (): void {
    Telegraph::fake();

    $bot = TelegraphBot::factory()->create();
    $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);

    $user = User::factory()->create();

    $stale = UserChatPlatformLink::factory()->linked($user)->create([
        'platform_user_id' => '123456789',
    ]);

    $pending = UserChatPlatformLink::factory()->create([
        'user_id' => $user->id,
        'platform' => ChatPlatform::Telegram,
        'is_active' => true,
        'linking_token' => 'ABC123XY',
        'token_expires_at' => now()->addHours(24),
        'linked_at' => null,
    ]);

    $this->postJson(
        route('telegraph.webhook', ['token' => $bot->token]),
        TelegramWebhookPayloads::message('/link ABC123XY', (string) $telegraphChat->chat_id),
    )->assertSuccessful();

    expect(UserChatPlatformLink::query()->find($stale->id))->toBeNull();
    expect($pending->fresh()->platform_user_id)->toBe('123456789');
    expect($pending->fresh()->linked_at)->not->toBeNull();
});
