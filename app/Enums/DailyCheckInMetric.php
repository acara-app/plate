<?php

declare(strict_types=1);

namespace App\Enums;

enum DailyCheckInMetric: string
{
    case SleepHours = 'timeAsleep';
    case SleepQuality = 'sleepQuality';
    case StressLevel = 'stressLevel';

    public function unit(): string
    {
        return match ($this) {
            self::SleepHours => 'hours',
            self::SleepQuality, self::StressLevel => 'score',
        };
    }
}
