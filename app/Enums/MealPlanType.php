<?php

declare(strict_types=1);

namespace App\Enums;

enum MealPlanType: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Custom = 'custom';

    public static function fromDays(int $totalDays): self
    {
        return match (true) {
            $totalDays <= 7 => self::Weekly,
            $totalDays <= 30 => self::Monthly,
            default => self::Custom,
        };
    }
}
