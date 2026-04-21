<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\ChatPlatform;
use App\Models\User;
use App\Models\UserChatPlatformLink;
use Illuminate\Support\Facades\DB;

final readonly class GenerateChatPlatformLinkToken
{
    /**
     * @return array{link: UserChatPlatformLink, token: string}
     */
    public function handle(User $user, ChatPlatform $platform, int $expiresInHours = 24): array
    {
        return DB::transaction(function () use ($user, $platform, $expiresInHours): array {
            $user->chatPlatformLinks()
                ->where('platform', $platform)
                ->where('is_active', true)
                ->update(['is_active' => false]);

            $link = $user->chatPlatformLinks()->create([
                'platform' => $platform,
                'is_active' => true,
            ]);

            $token = $link->generateToken($expiresInHours);

            return ['link' => $link, 'token' => $token];
        });
    }
}
