<?php

declare(strict_types=1);

namespace App\Enums;

enum SleepQuality: int
{
    case Poor = 1;
    case Fair = 2;
    case Good = 3;
}
