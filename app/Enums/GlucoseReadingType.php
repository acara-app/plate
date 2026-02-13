<?php

declare(strict_types=1);

namespace App\Enums;

enum GlucoseReadingType: string
{
    case Fasting = 'fasting';
    case BeforeMeal = 'before-meal';
    case PostMeal = 'post-meal';
    case Random = 'random';

    public function label(): string
    {
        return match ($this) {
            self::Fasting => 'Fasting',
            self::BeforeMeal => 'Before meal',
            self::PostMeal => 'Post-meal',
            self::Random => 'Random',
        };
    }
}
