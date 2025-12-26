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
     * Get the default meal plan type.
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
     * Execute the workflow to generate a single day's meals for a meal plan.
     *
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     */
    public function execute(
        User $user,
        int $totalDays = 7,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?MealPlan $existingMealPlan = null,
    ): Generator {
        if ($existingMealPlan instanceof MealPlan) {
            $mealPlan = $existingMealPlan;
        } else {
            /** @var MealPlan $mealPlan */
            $mealPlan = yield ActivityStub::make(
                InitializeMealPlanActivity::class,
                $user,
                $totalDays,
            );
        }

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
