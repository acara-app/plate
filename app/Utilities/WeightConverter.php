<?php

declare(strict_types=1);

namespace App\Utilities;

use InvalidArgumentException;

final class WeightConverter
{
    public const float LB_TO_KG = 0.45359237;

    public static function convertToKg(float $value, string $unit): float
    {
        return match (mb_strtolower($unit)) {
            'kg' => $value,
            'lb', 'lbs' => $value * self::LB_TO_KG,
            default => throw new InvalidArgumentException("Unsupported weight unit: {$unit}"),
        };
    }
}
