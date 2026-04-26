<?php

declare(strict_types=1);

namespace App\Actions;

use Carbon\CarbonImmutable;

final readonly class CalculateCaffeineSleepCutoff
{
    public const float HALF_LIFE_HOURS = 5.0;

    public const float RESIDUAL_MG_THRESHOLD = 50.0;

    public function handle(?CarbonImmutable $bedtime, float $perCupMg, int $cups): ?CarbonImmutable
    {
        if (! $bedtime instanceof CarbonImmutable) {
            return null;
        }

        if ($perCupMg <= 0.0 || $cups <= 0) {
            return $bedtime;
        }

        $totalMg = $perCupMg * $cups;

        if ($totalMg <= self::RESIDUAL_MG_THRESHOLD) {
            return $bedtime;
        }

        $hoursBeforeBed = self::HALF_LIFE_HOURS * log($totalMg / self::RESIDUAL_MG_THRESHOLD, 2);

        return $bedtime->subSeconds((int) round($hoursBeforeBed * 3600));
    }
}
