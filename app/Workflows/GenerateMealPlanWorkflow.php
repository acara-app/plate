<?php

declare(strict_types=1);

namespace App\Workflows;

use App\DataObjects\DayMealsData;
use App\DataObjects\MealData;
use App\DataObjects\PreviousDayContext;
use App\Enums\AiModel;
use App\Enums\MealPlanType;
use App\Models\MealPlan;
use App\Models\User;
use Generator;
use Spatie\LaravelData\DataCollection;
use Workflow\ActivityStub;
use Workflow\Workflow;

final class GenerateMealPlanWorkflow extends Workflow
{
    public $timeout = 1800; // 30 minutes for 7 days

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
     * Execute the workflow to generate a multi-day meal plan sequentially.
     * Each day's meals are stored immediately after generation for better UX.
     *
     * @return array{user_id: int, total_days: int, status: string, meal_plan_id: int}
     */
    public function execute(User $user, int $totalDays = 7, AiModel $model = AiModel::Gemini25Flash): Generator
    {
        // Step 1: Create the meal plan record first (so user can see it immediately)
        /** @var MealPlan $mealPlan */
        $mealPlan = yield ActivityStub::make(
            CreateMealPlanActivity::class,
            $user,
            $totalDays,
        );

        $previousDaysContext = new PreviousDayContext;

        // Step 2: Generate and store each day's meals immediately
        for ($dayNumber = 1; $dayNumber <= $totalDays; $dayNumber++) {
            // Generate meals for this day
            /** @var DayMealsData $dayMeals */
            $dayMeals = yield ActivityStub::make(
                GenerateDayMealsActivity::class,
                $user,
                $dayNumber,
                $totalDays,
                $model,
                $previousDaysContext,
            );

            // Store meals immediately (user can see progress)
            yield ActivityStub::make(
                StoreDayMealsActivity::class,
                $mealPlan,
                $dayMeals,
                $dayNumber,
            );

            // Update context for next day's variety
            $mealNames = $dayMeals->meals->toCollection()->pluck('name')->toArray();
            $previousDaysContext->addDayMeals($dayNumber, $mealNames);

            // Update meal plan metadata with progress
            $mealPlan->update([
                'metadata' => array_merge($mealPlan->metadata ?? [], [
                    'days_completed' => $dayNumber,
                    'status' => $dayNumber === $totalDays ? 'completed' : 'generating',
                ]),
            ]);
        }

        return [
            'user_id' => $user->id,
            'total_days' => $totalDays,
            'status' => 'completed',
            'meal_plan_id' => $mealPlan->id,
        ];
    }
}
