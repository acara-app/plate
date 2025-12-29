<?php

declare(strict_types=1);

namespace App\Enums;

/**
 * Glucose reading type enum - renamed from ReadingType for clarity.
 */
enum GlucoseReadingType: string
{
    case Fasting = 'fasting';
    case BeforeMeal = 'before-meal';
    case PostMeal = 'post-meal';
    case Random = 'random';
}
