<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\ChatPlatform;
use App\Models\User;

final readonly class DisconnectChatPlatformLink
{
    public function handle(User $user, ChatPlatform $platform): int
    {
        return $user->chatPlatformLinks()
            ->where('platform', $platform)
            ->where('is_active', true)
            ->update(['is_active' => false]);
    }
}
