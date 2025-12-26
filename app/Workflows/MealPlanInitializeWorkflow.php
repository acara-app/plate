<?php

declare(strict_types=1);

namespace App\Workflows;

use App\DataObjects\DayMealsData;
use App\DataObjects\GlucoseAnalysis\GlucoseAnalysisData;
use App\DataObjects\MealData;
use App\DataObjects\PreviousDayContext;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealPlanType;
use App\Models\MealPlan;
use App\Models\User;
use Generator;
use Spatie\LaravelData\DataCollection;
use Workflow\ActivityStub;
use Workflow\Workflow;

final class MealPlanInitializeWorkflow extends Workflow
{
    public int $timeout = 1800; // 30 minutes for 7 days

    /**
     * Convert all days' meals to a collection of MealData with proper day numbers.
     *
     * @param  array<int, DayMealsData>  $allDaysMeals
     * @return DataCollection<int, MealData>
     */
    public static function convertToMealDataCollection(array $allDaysMeals): DataCollection
    {
        $meals = [];

        foreach ($allDaysMeals as $dayNumber => $dayMeals) {
            foreach ($dayMeals->meals as $singleDayMeal) {
                $meals[] = $singleDayMeal->toMealData($dayNumber);
            }
        }

        return new DataCollection(MealData::class, $meals);
    }

    /**
     * Get the meal plan type based on duration.
     */
    public static function getMealPlanType(int $totalDays): MealPlanType
    {
        return match (true) {
            $totalDays <= 7 => MealPlanType::Weekly,
            $totalDays <= 30 => MealPlanType::Monthly,
            default => MealPlanType::Custom,
        };
    }

    /**
     * Create a meal plan with Generating status.
     *
     * This must be called synchronously before starting the workflow
     * to ensure the user sees the "Generating" state immediately.
     */
    public static function createMealPlan(User $user, int $totalDays = 7): MealPlan
    {
        $mealPlanType = self::getMealPlanType($totalDays);

        /** @var MealPlan $mealPlan */
        $mealPlan = $user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => $totalDays.'-Day Personalized Meal Plan',
            'description' => 'AI-generated meal plan tailored to your nutritional needs and preferences.',
            'duration_days' => $totalDays,
            'target_daily_calories' => null,
            'macronutrient_ratios' => null,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generation_method' => 'workflow',
                'status' => MealPlanGenerationStatus::Generating->value,
                'days_completed' => 0,
            ],
        ]);

        return $mealPlan;
    }

    /**
     * Execute the workflow to generate and save day 1 meals for a meal plan.
     *
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     */
    public function execute(
        User $user,
        MealPlan $mealPlan,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
    ): Generator {
        $totalDays = $mealPlan->duration_days;

        /** @var DayMealsData $dayMeals */
        $dayMeals = yield ActivityStub::make(
            MealPlanDayGeneratorActivity::class,
            $user,
            1,
            $totalDays,
            new PreviousDayContext,
            $glucoseAnalysis,
        );

        yield ActivityStub::make(
            SaveDayMealsActivity::class,
            $mealPlan,
            $dayMeals,
            1,
        );

        $mealPlan->update([
            'metadata' => array_merge($mealPlan->metadata ?? [], [
                'days_completed' => 1,
                'status' => MealPlanGenerationStatus::Pending->value,
            ]),
        ]);

        return [
            'user_id' => $user->id,
            'total_days' => $totalDays,
            'days_generated' => 1,
            'status' => MealPlanGenerationStatus::Pending->value,
            'meal_plan_id' => $mealPlan->id,
        ];
    }
}
