<?php

declare(strict_types=1);

namespace App\Services;

use App\Enums\HeightUnit;
use App\Enums\WeightUnit;

final class BodyMassIndex
{
    public const float PLAUSIBLE_MINIMUM = 15.0;

    public const float PLAUSIBLE_MAXIMUM = 45.0;

    public const int MINIMUM_WEIGHT_KG = 20;

    public const int MAXIMUM_WEIGHT_KG = 500;

    public const int MINIMUM_HEIGHT_CM = 50;

    public const int MAXIMUM_HEIGHT_CM = 300;

    public static function isPlausible(float $weightKg, float $heightCm): bool
    {
        if ($weightKg <= 0 || $heightCm <= 0) {
            return false;
        }

        $heightInMeters = $heightCm / 100;
        $bodyMassIndex = $weightKg / ($heightInMeters * $heightInMeters);

        return $bodyMassIndex >= self::PLAUSIBLE_MINIMUM && $bodyMassIndex < self::PLAUSIBLE_MAXIMUM;
    }

    public static function isWeightInRange(float $weightKg): bool
    {
        return $weightKg >= self::MINIMUM_WEIGHT_KG && $weightKg <= self::MAXIMUM_WEIGHT_KG;
    }

    public static function isHeightInRange(float $heightCm): bool
    {
        return $heightCm >= self::MINIMUM_HEIGHT_CM && $heightCm <= self::MAXIMUM_HEIGHT_CM;
    }

    public static function looksLikePounds(float $weightKg, float $heightCm): bool
    {
        return ! self::isPlausible($weightKg, $heightCm)
            && self::isPlausible(WeightUnit::Lb->toKg($weightKg), $heightCm);
    }

    public static function looksLikeInches(float $weightKg, float $heightCm): bool
    {
        return ! self::isPlausible($weightKg, $heightCm)
            && self::isPlausible($weightKg, HeightUnit::In->toCentimeters($heightCm));
    }

    /**
     * @return array{min: int, max: int}
     */
    public static function weightRangeInPounds(): array
    {
        return [
            'min' => (int) ceil(WeightUnit::Lb->fromKg(self::MINIMUM_WEIGHT_KG)),
            'max' => (int) floor(WeightUnit::Lb->fromKg(self::MAXIMUM_WEIGHT_KG)),
        ];
    }

    /**
     * @return array{min: int, max: int}
     */
    public static function heightRangeInInches(): array
    {
        return [
            'min' => (int) ceil(HeightUnit::In->fromCentimeters(self::MINIMUM_HEIGHT_CM)),
            'max' => (int) floor(HeightUnit::In->fromCentimeters(self::MAXIMUM_HEIGHT_CM)),
        ];
    }
}
