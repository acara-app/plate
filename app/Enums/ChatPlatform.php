<?php

declare(strict_types=1);

namespace App\Enums;

use App\Contracts\Messaging\ChatPlatformAdapter;
use InvalidArgumentException;

enum ChatPlatform: string
{
    case Telegram = 'telegram';

    public function label(): string
    {
        return match ($this) {
            self::Telegram => 'Telegram',
        };
    }

    public function adapter(): ChatPlatformAdapter
    {
        $class = config(sprintf('messaging.platforms.%s.adapter', $this->value));

        if (! is_string($class) || ! class_exists($class)) {
            throw new InvalidArgumentException(sprintf('No adapter configured for chat platform [%s].', $this->value));
        }

        /** @var ChatPlatformAdapter $adapter */
        $adapter = resolve($class);

        return $adapter;
    }
}
