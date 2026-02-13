<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthEntrySource: string
{
    case Web = 'web';
    case Telegram = 'telegram';
}
