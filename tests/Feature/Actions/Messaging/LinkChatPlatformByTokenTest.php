<?php

declare(strict_types=1);

use App\Actions\Messaging\LinkChatPlatformByToken;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\DB;

it('links a pending record when token matches', function (): void {
    $user = User::factory()->create();
    $pending = UserChatPlatformLink::factory()->pendingLink()->create([
        'user_id' => $user->id,
        'platform' => ChatPlatform::Telegram,
        'linking_token' => 'ABCD1234',
        'token_expires_at' => now()->addHour(),
    ]);

    $linked = resolve(LinkChatPlatformByToken::class)->handle(ChatPlatform::Telegram, '54321', 'ABCD1234');

    expect($linked)->not->toBeNull();
    expect($linked?->id)->toBe($pending->id);
    expect($linked?->platform_user_id)->toBe('54321');
    expect($linked?->linked_at)->not->toBeNull();
    expect($linked?->linking_token)->toBeNull();
});

it('returns null when token is unknown', function (): void {
    $result = resolve(LinkChatPlatformByToken::class)->handle(ChatPlatform::Telegram, '999', 'NOTHING1');

    expect($result)->toBeNull();
});

it('returns null when token has expired', function (): void {
    UserChatPlatformLink::factory()->create([
        'user_id' => User::factory(),
        'platform' => ChatPlatform::Telegram,
        'linking_token' => 'EXPIRED1',
        'token_expires_at' => now()->subMinute(),
    ]);

    $result = resolve(LinkChatPlatformByToken::class)->handle(ChatPlatform::Telegram, '999', 'EXPIRED1');

    expect($result)->toBeNull();
});

it('does not link tokens issued for a different platform', function (): void {
    DB::table('user_chat_platform_links')->insert([
        'user_id' => User::factory()->create()->id,
        'platform' => 'whatsapp',
        'linking_token' => 'CROSSPL1',
        'token_expires_at' => now()->addHour(),
        'is_active' => true,
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $result = resolve(LinkChatPlatformByToken::class)->handle(ChatPlatform::Telegram, '999', 'CROSSPL1');

    expect($result)->toBeNull();
});

it('removes prior links for the same (platform, platform_user_id) pair', function (): void {
    $user = User::factory()->create();
    $stale = UserChatPlatformLink::factory()->create([
        'user_id' => $user->id,
        'platform' => ChatPlatform::Telegram,
        'platform_user_id' => '12345',
        'is_active' => false,
        'linked_at' => now()->subDay(),
    ]);
    $pending = UserChatPlatformLink::factory()->pendingLink()->create([
        'user_id' => $user->id,
        'platform' => ChatPlatform::Telegram,
        'linking_token' => 'NEWTOKE1',
        'token_expires_at' => now()->addHour(),
    ]);

    resolve(LinkChatPlatformByToken::class)->handle(ChatPlatform::Telegram, '12345', 'NEWTOKE1');

    expect(UserChatPlatformLink::query()->whereKey($stale->id)->exists())->toBeFalse();
    expect($pending->fresh()->platform_user_id)->toBe('12345');
});
