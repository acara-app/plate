<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanContext\MacronutrientRatiosData;
use App\DataObjects\MealPlanContext\MealPlanContextData;
use App\DataObjects\PreviousDayContext;
use App\Models\User;
use App\Models\UserProfile;
use RuntimeException;

final readonly class MealPlanPromptBuilder
{
    public function __construct(
        private GlucoseDataAnalyzer $glucoseDataAnalyzer,
    ) {}

    /**
     * Generate a prompt for multi-day meal plan generation (legacy).
     */
    public function handle(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): string
    {
        $context = $this->buildContext($user, $glucoseAnalysis);

        return view('ai.agents.create-meal-plan', [
            'context' => $context,
        ])->render();
    }

    /**
     * Generate a prompt for single-day meal plan generation.
     */
    public function handleForDay(
        User $user,
        int $dayNumber,
        int $totalDays = 7,
        ?PreviousDayContext $previousDaysContext = null,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
    ): string {
        $context = $this->buildContext($user, $glucoseAnalysis);

        return view('ai.agents.create-day-meal-plan', [
            'context' => $context,
            'dayNumber' => $dayNumber,
            'totalDays' => $totalDays,
            'previousDaysContext' => $previousDaysContext?->toPromptText(),
        ])->render();
    }

    /**
     * Build the context data object from user profile.
     */
    private function buildContext(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): MealPlanContextData
    {
        $user->loadMissing([
            'profile.goal',
            'profile.lifestyle',
            'profile.dietaryPreferences',
            'profile.healthConditions',
        ]);

        throw_unless($user->profile instanceof UserProfile, RuntimeException::class, 'User profile is required to create a meal plan.');
        /**
         * @var UserProfile $profile
         */
        $profile = $user->profile;

        return MealPlanContextData::from([
            ...$profile->toArray(),
            'goal_name' => $profile->goal?->name,
            'lifestyle' => $profile->lifestyle,
            'dietary_preferences' => $profile->dietaryPreferences,
            'health_conditions' => $profile->healthConditions,
            'daily_calorie_target' => $this->calculateDailyCalorieTarget($profile),
            'macronutrient_ratios' => $this->calculateMacronutrientRatios($profile),
            'glucose_analysis' => $glucoseAnalysis ?? $this->glucoseDataAnalyzer->handle($user, 30),
        ]);
    }

    /**
     * Calculate daily calorie target based on TDEE and goals
     */
    private function calculateDailyCalorieTarget(UserProfile $profile): ?float
    {
        $tdee = $profile->tdee;

        if (! $tdee || ! $profile->goal) {
            return null;
        }

        // Adjust calories based on goal
        return match ($profile->goal->name) {
            'Weight Loss', 'Lose Weight' => round($tdee - 500, 2), // 500 calorie deficit
            'Weight Gain', 'Gain Weight', 'Muscle Gain' => round($tdee + 300, 2), // 300 calorie surplus
            'Maintain Weight', 'Maintenance' => round($tdee, 2),
            default => round($tdee, 2),
        };
    }

    /**
     * Calculate macronutrient ratios based on goal and health conditions
     */
    private function calculateMacronutrientRatios(UserProfile $profile): MacronutrientRatiosData
    {
        if (! $profile->goal) {
            // Default balanced ratio
            return new MacronutrientRatiosData(protein: 30, carbs: 40, fat: 30);
        }

        // Adjust based on goal
        return match ($profile->goal->name) {
            'Weight Loss', 'Lose Weight' => new MacronutrientRatiosData(protein: 35, carbs: 30, fat: 35),
            'Muscle Gain', 'Gain Weight' => new MacronutrientRatiosData(protein: 30, carbs: 45, fat: 25),
            'Maintain Weight', 'Maintenance' => new MacronutrientRatiosData(protein: 30, carbs: 40, fat: 30),
            default => new MacronutrientRatiosData(protein: 30, carbs: 40, fat: 30),
        };
    }
}
