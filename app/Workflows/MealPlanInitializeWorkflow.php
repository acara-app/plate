<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Data\DayMealsData;
use App\Data\GlucoseAnalysis\GlucoseAnalysisData;
use App\Data\MealData;
use App\Data\PreviousDayContext;
use App\Enums\DietType;
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
    /**
     * @var int
     */
    public $timeout = 1800;

    /**
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

    public static function getMealPlanType(int $totalDays): MealPlanType
    {
        return match (true) {
            $totalDays <= 7 => MealPlanType::Weekly,
            $totalDays <= 30 => MealPlanType::Monthly,
            default => MealPlanType::Custom,
        };
    }

    public static function createMealPlan(User $user, int $totalDays = 7, ?DietType $dietType = null): MealPlan
    {
        $mealPlanType = self::getMealPlanType($totalDays);

        // @codeCoverageIgnoreStart
        $name = $dietType instanceof DietType
            ? sprintf('%d-Day %s Plan', $totalDays, $dietType->shortName())
            : $totalDays.'-Day Personalized Plan';
        // @codeCoverageIgnoreEnd

        /** @var MealPlan $mealPlan */
        $mealPlan = $user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => $name,
            'description' => 'AI-generated meal plan tailored to your nutritional needs and preferences.',
            'duration_days' => $totalDays,
            'target_daily_calories' => null,
            'macronutrient_ratios' => null,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generation_method' => 'workflow',
                'status' => MealPlanGenerationStatus::Generating->value,
                'days_completed' => 0,
                'diet_type' => $dietType?->value,
            ],
        ]);

        return $mealPlan;
    }

    /**
     * @codeCoverageIgnore Generator methods with yield are executed by the workflow engine
     *
     * @return Generator<int, mixed, mixed, mixed>
     */
    public function execute(
        User $user,
        MealPlan $mealPlan,
        ?GlucoseAnalysisData $glucoseAnalysis = null,
        ?DietType $dietType = null,
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
            $mealPlan,
            $dietType,
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
