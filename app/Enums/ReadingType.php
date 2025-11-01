<?php

declare(strict_types=1);

namespace App\Enums;

enum ReadingType: string
{
    case Fasting = 'Fasting';
    case BeforeMeal = 'BeforeMeal';
    case PostMeal = 'PostMeal';
    case Random = 'Random';
}
