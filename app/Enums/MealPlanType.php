<?php

declare(strict_types=1);

namespace App\Enums;

enum MealPlanType: string
{
    case Weekly = 'weekly';
    case Monthly = 'monthly';
    case Custom = 'custom';
}
