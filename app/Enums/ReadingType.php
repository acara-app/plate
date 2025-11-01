<?php

declare(strict_types=1);

namespace App\Enums;

enum ReadingType: string
{
    case Fasting = 'fasting';
    case BeforeMeal = 'before-meal';
    case PostMeal = 'post-meal';
    case Random = 'random';
}
