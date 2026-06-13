<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class MealGlucosePatternData extends Data
{
    public function __construct(
        public float $carbs,
        public float $median,
        public float $min,
        public float $max,
        public int $count,
    ) {}
}
