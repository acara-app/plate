<?php

declare(strict_types=1);

use App\Models\User;
use App\Models\UserTelegramChat;
use DefStudio\Telegraph\Models\TelegraphBot;
use DefStudio\Telegraph\Models\TelegraphChat;

it('reproduces unique constraint violation when relinking telegram', function (): void {
    $user = User::factory()->create();
    $bot = TelegraphBot::factory()->create();

    $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);

    $existingChat = UserTelegramChat::factory()->for($user)->create([
        'telegraph_chat_id' => $telegraphChat->id,
        'is_active' => true,
        'linked_at' => now(),
    ]);

    $pendingChat = UserTelegramChat::factory()->for($user)->create([
        'telegraph_chat_id' => null,
        'is_active' => true,
        'linking_token' => 'ABC123XY',
        'token_expires_at' => now()->addHours(24),
        'linked_at' => null,
    ]);


    expect(function () use ($pendingChat, $telegraphChat): void {
        $pendingChat->update(['telegraph_chat_id' => $telegraphChat->id]);
    })->toThrow(Illuminate\Database\UniqueConstraintViolationException::class);
});

it('prevents duplicate by deleting existing link before update', function (): void {
    $user = User::factory()->create();
    $bot = TelegraphBot::factory()->create();

    $telegraphChat = TelegraphChat::factory()->for($bot, 'bot')->create([
        'chat_id' => '123456789',
    ]);

    $existingChat = UserTelegramChat::factory()->for($user)->create([
        'telegraph_chat_id' => $telegraphChat->id,
        'is_active' => true,
        'linked_at' => now(),
    ]);

    $pendingChat = UserTelegramChat::factory()->for($user)->create([
        'telegraph_chat_id' => null,
        'is_active' => true,
        'linking_token' => 'ABC123XY',
        'token_expires_at' => now()->addHours(24),
        'linked_at' => null,
    ]);

    UserTelegramChat::query()
        ->where('user_id', $user->id)
        ->where('telegraph_chat_id', $telegraphChat->id)
        ->where('id', '!=', $pendingChat->id)
        ->delete();

    $pendingChat->update(['telegraph_chat_id' => $telegraphChat->id]);

    expect(UserTelegramChat::find($existingChat->id))->toBeNull();
    expect($pendingChat->fresh()->telegraph_chat_id)->toBe($telegraphChat->id);
});
