<?php

declare(strict_types=1);

namespace App\Actions;

use App\Data\DailyNutritionGoalsData;
use App\Enums\GoalChoice;
use App\Models\UserProfile;

final readonly class CalculateDailyNutritionGoals
{
    private const int MINIMUM_CALORIES = 1200;

    private const int FIBER_GRAMS_PER_1000_KCAL = 14;

    public function handle(?UserProfile $profile): ?DailyNutritionGoalsData
    {
        $tdee = $profile?->tdee;

        if (! $profile instanceof UserProfile || $tdee === null || ! $profile->goal_choice instanceof GoalChoice) {
            return null;
        }

        $calories = (int) round(max(self::MINIMUM_CALORIES, match ($profile->goal_choice) {
            GoalChoice::WeightLoss => $tdee - 500,
            GoalChoice::BuildMuscle => $tdee + 300,
            GoalChoice::Spikes, GoalChoice::HeartHealth, GoalChoice::HealthyEating => $tdee - 300,
        }));
        $split = $profile->dietType()->macroTargets();

        return new DailyNutritionGoalsData(
            calories: $calories,
            protein: (int) round($calories * $split['protein'] / 100 / 4),
            carbs: (int) round($calories * $split['carbs'] / 100 / 4),
            fat: (int) round($calories * $split['fat'] / 100 / 9),
            fiber: (int) round($calories * self::FIBER_GRAMS_PER_1000_KCAL / 1000),
        );
    }
}
