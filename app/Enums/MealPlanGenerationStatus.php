<?php

declare(strict_types=1);

namespace App\Enums;

enum MealPlanGenerationStatus: string
{
    case Pending = 'pending';
    case Generating = 'generating';
    case Completed = 'completed';
    case Failed = 'failed';
    case Paused = 'paused';
}
