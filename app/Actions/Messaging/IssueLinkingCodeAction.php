<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Models\UserChatPlatformLink;
use RuntimeException;

final class IssueLinkingCodeAction
{
    /**
     * @return array{code: string, expires_at: string, link: UserChatPlatformLink}
     */
    public function handle(string $platform, string $platformUserId): array
    {
        $link = UserChatPlatformLink::query()->firstOrCreate(['platform' => $platform, 'platform_user_id' => $platformUserId], ['is_active' => false]);

        if (! $link->isTokenValid()) {
            $link->generateToken();
        }

        throw_if($link->linking_token === null || $link->token_expires_at === null, RuntimeException::class, 'Failed to issue linking token.');

        return [
            'code' => $link->linking_token,
            'expires_at' => $link->token_expires_at->toIso8601String(),
            'link' => $link,
        ];
    }
}
