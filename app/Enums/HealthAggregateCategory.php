<?php

declare(strict_types=1);

namespace App\Enums;

enum HealthAggregateCategory: string
{
    case Cumulative = 'cumulative';
    case Instantaneous = 'instantaneous';
    case SlowChanging = 'slow_changing';
    case Event = 'event';
    case Sleep = 'sleep';
}
