<?php

declare(strict_types=1);

namespace App\Enums;

enum WeightUnit: string
{
    case Kg = 'kg';
    case Lb = 'lb';

    public function toKg(float $value): float
    {
        return match ($this) {
            self::Kg => $value,
            self::Lb => $value * 0.45359237,
        };
    }
}
