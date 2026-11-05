<?php

declare(strict_types=1);

namespace App\Services;

final class BodyMassIndex
{
    public const float PLAUSIBLE_MINIMUM = 15.0;

    public const float PLAUSIBLE_MAXIMUM = 45.0;

    public static function isPlausible(float $weightKg, float $heightCm): bool
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            return false;
        }

        $heightInMeters = $heightCm / 100;
        $bodyMassIndex = $weightKg / ($heightInMeters * $heightInMeters);

        return $bodyMassIndex >= self::PLAUSIBLE_MINIMUM && $bodyMassIndex < self::PLAUSIBLE_MAXIMUM;
    }
}
