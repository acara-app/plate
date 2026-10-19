<?php

declare(strict_types=1);

namespace App\Data;

use Spatie\LaravelData\Data;

final class DailyNutritionGoalsData extends Data
{
    public function __construct(
        public int $calories,
        public int $protein,
        public int $carbs,
        public int $fat,
        public int $fiber,
    ) {}
}
