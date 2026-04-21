<?php

declare(strict_types=1);

namespace App\Actions\Messaging;

use App\Enums\ChatPlatform;
use App\Models\UserChatPlatformLink;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

final readonly class LinkChatPlatformByToken
{
    public function handle(ChatPlatform $platform, string $platformUserId, string $token): ?UserChatPlatformLink
    {
        $pending = UserChatPlatformLink::query()
            ->with('user')
            ->where('platform', $platform)
            ->where('linking_token', $token)
            ->where('token_expires_at', '>', now())
            ->first();

        if ($pending === null || $pending->user === null) {
            return null;
        }

        return DB::transaction(function () use ($pending, $platform, $platformUserId): UserChatPlatformLink {
            UserChatPlatformLink::query()
                ->where('platform', $platform)
                ->where('id', '!=', $pending->id)
                ->where(function (Builder $query) use ($pending, $platformUserId): void {
                    $query->where('user_id', $pending->user_id)
                        ->orWhere('platform_user_id', $platformUserId);
                })
                ->delete();

            $pending->markAsLinked($pending->user, $platformUserId);
            $pending->refresh();
            $pending->load('user');

            return $pending;
        });
    }
}
