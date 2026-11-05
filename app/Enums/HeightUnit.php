<?php

declare(strict_types=1);

namespace App\Enums;

enum HeightUnit: string
{
    case Cm = 'cm';
    case In = 'in';

    public function toCentimeters(float $value): float
    {
        return match ($this) {
            self::Cm => $value,
            self::In => $value * 2.54,
        };
    }
}
