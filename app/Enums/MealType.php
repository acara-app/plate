<?php

declare(strict_types=1);

namespace App\Enums;

use Carbon\CarbonInterface;

enum MealType: string
{
    case Breakfast = 'breakfast';
    case Lunch = 'lunch';
    case Dinner = 'dinner';
    case Snack = 'snack';

    public static function forLocalTime(CarbonInterface $time): self
    {
        return match (true) {
            $time->hour >= 4 && $time->hour < 11 => self::Breakfast,
            $time->hour >= 11 && $time->hour < 16 => self::Lunch,
            $time->hour >= 16 && $time->hour < 22 => self::Dinner,
            default => self::Snack,
        };
    }
}
