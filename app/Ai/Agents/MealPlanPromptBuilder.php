<?php

declare(strict_types=1);

namespace App\Ai\Agents;

use App\DataObjects\MealPlanContext\DietaryPreferenceData;
use App\DataObjects\MealPlanContext\HealthConditionData;
use App\DataObjects\MealPlanContext\LifestyleData;
use App\DataObjects\MealPlanContext\MacronutrientRatiosData;
use App\DataObjects\MealPlanContext\MealPlanContextData;
use App\DataObjects\PreviousDayContext;
use App\Models\DietaryPreference;
use App\Models\HealthCondition;
use App\Models\User;
use App\Models\UserProfile;
use RuntimeException;
use Spatie\LaravelData\DataCollection;

final readonly class MealPlanPromptBuilder
{
    public function __construct(
        private GlucoseDataAnalyzer $glucoseDataAnalyzer,
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
     * Build the context data object from user profile.
     */
    private function buildContext(User $user): MealPlanContextData
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

        return new MealPlanContextData(
            // Physical metrics
            age: $profile->age,
            height: $profile->height,
            weight: $profile->weight,
            sex: $profile->sex?->value,
            bmi: $profile->calculateBMI(),
            bmr: $profile->calculateBMR(),
            tdee: $profile->calculateTDEE(),

            // Goals
            goal: $profile->goal?->name,
            targetWeight: $profile->target_weight,
            additionalGoals: $profile->additional_goals,

            // Lifestyle
            lifestyle: $profile->lifestyle ? new LifestyleData(
                name: $profile->lifestyle->name,
                activityLevel: $profile->lifestyle->activity_level,
                sleepHours: $profile->lifestyle->sleep_hours,
                occupation: $profile->lifestyle->occupation,
                description: $profile->lifestyle->description,
                activityMultiplier: $profile->lifestyle->activity_multiplier,
            ) : null,

            // Dietary preferences
            dietaryPreferences: new DataCollection(
                DietaryPreferenceData::class,
                $profile->dietaryPreferences->map(fn (DietaryPreference $pref): DietaryPreferenceData => new DietaryPreferenceData(
                    name: $pref->name,
                    type: $pref->type,
                    description: $pref->description,
                ))->toArray(),
            ),

            // Health conditions
            healthConditions: new DataCollection(
                HealthConditionData::class,
                $profile->healthConditions->map(fn (HealthCondition $condition): HealthConditionData => new HealthConditionData(
                    name: $condition->name,
                    description: $condition->description,
                    nutritionalImpact: $condition->nutritional_impact,
                    recommendedNutrients: $condition->recommended_nutrients,
                    nutrientsToLimit: $condition->nutrients_to_limit,
                    notes: $condition->pivot?->notes,
                ))->toArray(),
            ),

            // Calculated values
            dailyCalorieTarget: $this->calculateDailyCalorieTarget($profile),
            macronutrientRatios: $this->calculateMacronutrientRatios($profile),

            // Glucose data analysis
            glucoseAnalysis: $this->glucoseDataAnalyzer->handle($user, 30),
        );
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
