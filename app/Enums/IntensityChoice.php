<?php

declare(strict_types=1);

namespace App\Enums;

enum IntensityChoice: string
{
    case Balanced = 'balanced';
    case Aggressive = 'aggressive';

    public function label(): string
    {
        return match ($this) {
            self::Balanced => 'Balanced (Sustainable)',
            self::Aggressive => 'Aggressive (Fast Results)',
        };
    }
}
