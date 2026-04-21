<?php

declare(strict_types=1);

namespace App\Contracts\Messaging;

use App\Enums\ChatPlatform;

interface ChatPlatformAdapter
{
    public function platform(): ChatPlatform;

    public function botUsername(): string;

    public function deepLinkUrl(): string;

    public function linkingCommandFor(string $token): string;
}
