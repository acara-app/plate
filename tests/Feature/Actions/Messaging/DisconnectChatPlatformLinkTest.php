<?php

declare(strict_types=1);

use App\Actions\Messaging\DisconnectChatPlatformLink;
use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\DB;

it('deactivates the active link for a platform', function (): void {
    $user = User::factory()->create();
    $link = UserChatPlatformLink::factory()->linked($user)->create();

    $affected = resolve(DisconnectChatPlatformLink::class)->handle($user, ChatPlatform::Telegram);

    expect($affected)->toBe(1);
    expect($link->fresh()->is_active)->toBeFalse();
});

it('ignores already inactive links and other platforms', function (): void {
    $user = User::factory()->create();
    UserChatPlatformLink::factory()->linked($user)->inactive()->create();

    DB::table('user_chat_platform_links')->insert([
        'user_id' => $user->id,
        'platform' => 'whatsapp',
        'platform_user_id' => '123',
        'is_active' => true,
        'linked_at' => now(),
        'created_at' => now(),
        'updated_at' => now(),
    ]);

    $affected = resolve(DisconnectChatPlatformLink::class)->handle($user, ChatPlatform::Telegram);

    expect($affected)->toBe(0);
    expect(DB::table('user_chat_platform_links')->where('platform', 'whatsapp')->value('is_active'))->toBe(1);
});
