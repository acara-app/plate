<?php

declare(strict_types=1);

namespace App\Workflows;

use App\DataObjects\DayMealsData;
use App\DataObjects\MealData;
use App\DataObjects\PreviousDayContext;
use App\Enums\AiModel;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealPlanType;
use App\Models\MealPlan;
use App\Models\User;
use Generator;
use Spatie\LaravelData\DataCollection;
use Workflow\ActivityStub;
use Workflow\Workflow;

final class GenerateMealPlanWorkflow extends Workflow
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
     * Execute the workflow to generate a multi-day meal plan sequentially.
     * Each day's meals are stored immediately after generation for better UX.
     *
     * @param  int  $initialDays  Number of days to generate initially (default 1 for on-demand generation)
     * @return array{user_id: int, total_days: int, status: string, meal_plan_id: int}
     *
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     */
    public function execute(
        User $user,
        int $totalDays = 7,
        AiModel $model = AiModel::Gemini25Flash,
        int $initialDays = 1,
    ): Generator {
        // Step 1: Create the meal plan record first (so user can see it immediately)
        /** @var MealPlan $mealPlan */
        $mealPlan = yield ActivityStub::make(
            CreateMealPlanActivity::class,
            $user,
            $totalDays,
        );

        $previousDaysContext = new PreviousDayContext;

        // Step 2: Generate only the initial days (remaining days generated on-demand)
        $daysToGenerate = min($initialDays, $totalDays);

        for ($dayNumber = 1; $dayNumber <= $daysToGenerate; $dayNumber++) {
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
            $isCompleted = $dayNumber === $totalDays;
            $mealPlan->update([
                'metadata' => array_merge($mealPlan->metadata ?? [], [
                    'days_completed' => $dayNumber,
                    'status' => $isCompleted
                        ? MealPlanGenerationStatus::Completed->value
                        : MealPlanGenerationStatus::Pending->value,
                ]),
            ]);
        }

        $finalStatus = $daysToGenerate >= $totalDays
            ? MealPlanGenerationStatus::Completed->value
            : MealPlanGenerationStatus::Pending->value;

        return [
            'user_id' => $user->id,
            'total_days' => $totalDays,
            'days_generated' => $daysToGenerate,
            'status' => $finalStatus,
            'meal_plan_id' => $mealPlan->id,
        ];
    }
}
