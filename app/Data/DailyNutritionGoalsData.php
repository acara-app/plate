<?php

declare(strict_types=1);

namespace App\Data;

use App\Enums\DietType;
use Spatie\LaravelData\Data;

final class DailyNutritionGoalsData extends Data
{
    private const int FIBER_GRAMS_PER_1000_KCAL = 14;

    public function __construct(
        public int $calories,
        public int $protein,
        public int $carbs,
        public int $fat,
        public int $fiber,
    ) {}

    public static function forCalories(int $calories, DietType $dietType): self
    {
        $split = $dietType->macroTargets();

        return new self(
            calories: $calories,
            protein: (int) round($calories * $split['protein'] / 100 / 4),
            carbs: (int) round($calories * $split['carbs'] / 100 / 4),
            fat: (int) round($calories * $split['fat'] / 100 / 9),
            fiber: (int) round($calories * self::FIBER_GRAMS_PER_1000_KCAL / 1000),
        );
    }
}
