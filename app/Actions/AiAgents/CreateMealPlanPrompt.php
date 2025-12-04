<?php

declare(strict_types=1);

namespace App\Actions\AiAgents;

use App\DataObjects\PreviousDayContext;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;
use Illuminate\Support\Collection;
use RuntimeException;

final readonly class CreateMealPlanPrompt
{
    public function __construct(
        private AnalyzeGlucoseDataAction $analyzeGlucoseData,
    ) {}

    /**
     * Generate a prompt for multi-day meal plan generation (legacy).
     */
    public function handle(User $user): string
    {
        $context = $this->buildContext($user);

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
    ): string {
        $context = $this->buildContext($user);

        return view('ai.agents.create-day-meal-plan', [
            'context' => $context,
            'dayNumber' => $dayNumber,
            'totalDays' => $totalDays,
            'previousDaysContext' => $previousDaysContext?->toPromptText(),
        ])->render();
    }

    /**
     * Build the context array from user profile.
     *
     * @return array<string, mixed>
     */
    private function buildContext(User $user): array
    {
        $user->loadMissing([
            'profile.goal',
            'profile.lifestyle',
            'profile.dietaryPreferences',
            'profile.healthConditions',
        ]);

        $profile = $user->profile;

        throw_unless($profile instanceof UserProfile, RuntimeException::class, 'User profile is required to create a meal plan.');

        return [
            // Physical metrics
            'age' => $profile->age,
            'height' => $profile->height, // in cm
            'weight' => $profile->weight, // in kg
            'sex' => $profile->sex?->value,
            'bmi' => $profile->calculateBMI(),
            'bmr' => $profile->calculateBMR(),
            'tdee' => $profile->calculateTDEE(),

            // Goals
            'goal' => $profile->goal?->name,
            'targetWeight' => $profile->target_weight,
            'additionalGoals' => $profile->additional_goals,

            // Lifestyle
            'lifestyle' => $profile->lifestyle ? [
                'name' => $profile->lifestyle->name,
                'activityLevel' => $profile->lifestyle->activity_level,
                'sleepHours' => $profile->lifestyle->sleep_hours,
                'occupation' => $profile->lifestyle->occupation,
                'description' => $profile->lifestyle->description,
                'activityMultiplier' => $profile->lifestyle->activity_multiplier,
            ] : null,

            /** @var Collection<array-key, DietaryPreference> $dietaryPreferences */
            'dietaryPreferences' => $profile->dietaryPreferences->map(fn (DietaryPreference $pref): array => [
                'name' => $pref->name,
                'type' => $pref->type,
                'description' => $pref->description,
            ])->toArray(),

            /** @var Collection<array-key, HealthCondition> $healthConditions */
            'healthConditions' => $profile->healthConditions->map(
                function (HealthCondition $condition): array {
                    $pivot = $condition->pivot;

                    return [
                        'name' => $condition->name,
                        'description' => $condition->description,
                        'nutritionalImpact' => $condition->nutritional_impact,
                        'recommendedNutrients' => $condition->recommended_nutrients,
                        'nutrientsToLimit' => $condition->nutrients_to_limit,
                        'notes' => $pivot?->notes,
                    ];
                }
            )->toArray(),

            // Calculated values
            'dailyCalorieTarget' => $this->calculateDailyCalorieTarget($profile),
            'macronutrientRatios' => $this->calculateMacronutrientRatios($profile),

            // Glucose data analysis
            'glucoseAnalysis' => $this->analyzeGlucoseData->handle($user, 30),
        ];
    }

    /**
     * Calculate daily calorie target based on TDEE and goals
     */
    private function calculateDailyCalorieTarget(UserProfile $profile): ?float
    {
        $tdee = $profile->calculateTDEE();

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
     *
     * @return array{protein: int, carbs: int, fat: int}
     */
    private function calculateMacronutrientRatios(UserProfile $profile): array
    {
        if (! $profile->goal) {
            // Default balanced ratio
            return ['protein' => 30, 'carbs' => 40, 'fat' => 30];
        }

        // Adjust based on goal
        return match ($profile->goal->name) {
            'Weight Loss', 'Lose Weight' => ['protein' => 35, 'carbs' => 30, 'fat' => 35],
            'Muscle Gain', 'Gain Weight' => ['protein' => 30, 'carbs' => 45, 'fat' => 25],
            'Maintain Weight', 'Maintenance' => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
            default => ['protein' => 30, 'carbs' => 40, 'fat' => 30],
        };
    }
}
