<?php

declare(strict_types=1);

namespace App\Enums;

enum DailyCheckInMetric: string
{
    case SleepHours = 'selfReportedSleep';
    case SleepQuality = 'sleepQuality';
    case StressLevel = 'stressLevel';

    /**
     * @return list<string>
     */
    public static function values(): array
    {
        return array_column(self::cases(), 'value');
    }

    public function unit(): string
    {
        return match ($this) {
            self::SleepHours => 'hours',
            self::SleepQuality, self::StressLevel => 'score',
        };
    }
}
