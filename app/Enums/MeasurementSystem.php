<?php

declare(strict_types=1);

namespace App\Enums;

enum MeasurementSystem: string
{
    case Metric = 'metric';
    case Imperial = 'imperial';

    public static function feetAndInchesToCentimeters(int $feet, float $inches): float
    {
        return round((($feet * 12) + $inches) * 2.54, 2);
    }

    public function weightInKilograms(float $weight): float
    {
        return match ($this) {
            self::Metric => round($weight, 2),
            self::Imperial => round(WeightUnit::Lb->toKg($weight), 2),
        };
    }
}
