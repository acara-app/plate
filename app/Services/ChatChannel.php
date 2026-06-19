<?php

declare(strict_types=1);

namespace App\Services;

use Illuminate\Broadcasting\PrivateChannel;

final readonly class ChatChannel
{
    public const string PATTERN = 'chat.{userId}';

    public static function name(int $userId): string
    {
        return 'chat.'.$userId;
    }

    public static function private(int $userId): PrivateChannel
    {
        return new PrivateChannel(self::name($userId)); // @codeCoverageIgnore
    }
}
