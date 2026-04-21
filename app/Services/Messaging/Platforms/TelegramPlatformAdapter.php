<?php

declare(strict_types=1);

namespace App\Services\Messaging\Platforms;

use App\Contracts\Messaging\ChatPlatformAdapter;
use App\Enums\ChatPlatform;

final class TelegramPlatformAdapter implements ChatPlatformAdapter
{
    public function platform(): ChatPlatform
    {
        return ChatPlatform::Telegram;
    }

    public function botUsername(): string
    {
        $username = config('messaging.platforms.telegram.bot_username');

        return is_string($username) ? $username : '';
    }

    public function deepLinkUrl(): string
    {
        $base = config('messaging.platforms.telegram.deep_link_url');

        return mb_rtrim(is_string($base) ? $base : '', '/').'/'.$this->botUsername();
    }

    public function linkingCommandFor(string $token): string
    {
        return '/link '.$token;
    }
}
