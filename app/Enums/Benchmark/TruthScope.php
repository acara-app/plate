<?php

declare(strict_types=1);

namespace App\Enums\Benchmark;

enum TruthScope: string
{
    case PerItem = 'per-item';
    case MealOnly = 'meal-only';
}
