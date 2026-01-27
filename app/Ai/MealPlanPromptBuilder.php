<?php

declare(strict_types=1);

namespace App\Ai;

use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealPlanContext\MacronutrientRatiosData;
use App\DataObjects\MealPlanContext\MealPlanContextData;
use App\DataObjects\PreviousDayContext;
use App\Enums\DietType;
use App\Enums\GoalChoice;
use App\Models\User;
use App\Models\UserProfile;
use App\Services\DietMapper;
use RuntimeException;

final readonly class MealPlanPromptBuilder
{
    public function __construct(
        private GlucoseDataAnalyzer $glucoseDataAnalyzer,
    ) {}

    /**
     * Generate a prompt for multi-day meal plan generation.
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
        ?string $customPrompt = null,
    ): string {
        $context = $this->buildContext($user, $glucoseAnalysis);

        return view('ai.agents.create-day-meal-plan', [
            'context' => $context,
            'dayNumber' => $dayNumber,
            'totalDays' => $totalDays,
            'previousDaysContext' => $previousDaysContext?->toPromptText(),
            'prompt' => $customPrompt,
        ])->render();
    }

    /**
     * Build the context data object from user profile.
     */
    private function buildContext(User $user, ?GlucoseAnalysisData $glucoseAnalysis = null): MealPlanContextData
    {
        $user->loadMissing([
            'profile.dietaryPreferences',
            'profile.healthConditions',
            'profile.medications',
        ]);

        throw_unless($user->profile instanceof UserProfile, RuntimeException::class, 'User profile is required to create a meal plan.');
        /**
         * @var UserProfile $profile
         */
        $profile = $user->profile;

        $dietType = $this->calculateDietType($profile);
        $macroTargets = $dietType->macroTargets();

        return MealPlanContextData::from([
            ...$profile->toArray(),
            'goal' => $profile->goal_choice?->label(),
            'dietary_preferences' => $profile->dietaryPreferences,
            'health_conditions' => $profile->healthConditions,
            'medications' => $profile->medications,
            'daily_calorie_target' => $this->calculateDailyCalorieTarget($profile),
            'macronutrient_ratios' => new MacronutrientRatiosData(
                protein: $macroTargets['protein'],
                carbs: $macroTargets['carbs'],
                fat: $macroTargets['fat'],
            ),
            'diet_type' => $dietType,
            'diet_type_label' => $dietType->label(),
            'diet_type_focus' => $dietType->focus(),
            'glucose_analysis' => $glucoseAnalysis ?? $this->glucoseDataAnalyzer->handle($user, 30),
        ]);
    }

    /**
     * Calculate the DietType based on user profile choices.
     */
    private function calculateDietType(UserProfile $profile): DietType
    {
        return DietMapper::map(
            $profile->goal_choice ?? GoalChoice::HealthyEating,
            $profile->animal_product_choice ?? \App\Enums\AnimalProductChoice::Omnivore,
            $profile->intensity_choice ?? \App\Enums\IntensityChoice::Balanced,
        );
    }

    /**
     * Calculate daily calorie target based on TDEE and goals
     */
    private function calculateDailyCalorieTarget(UserProfile $profile): ?float
    {
        $tdee = $profile->tdee;

        if (! $tdee || ! $profile->goal_choice) {
            return null;
        }

        return match ($profile->goal_choice) {
            GoalChoice::WeightLoss => round($tdee - 500, 2),
            GoalChoice::BuildMuscle => round($tdee + 300, 2),
            GoalChoice::Spikes, GoalChoice::HeartHealth, GoalChoice::HealthyEating => round($tdee - 300, 2),
        };
    }
}
