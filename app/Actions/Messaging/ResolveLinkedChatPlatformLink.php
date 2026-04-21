<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\ChatPlatform;
use App\Models\UserChatPlatformLink;

final readonly class ResolveLinkedChatPlatformLink
{
    public function handle(ChatPlatform $platform, string $platformUserId): ?UserChatPlatformLink
    {
        return UserChatPlatformLink::query()
            ->with('user')
            ->forPlatformUser($platform, $platformUserId)
            ->linked()
            ->first();
    }
}
