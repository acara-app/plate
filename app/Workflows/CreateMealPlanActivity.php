<?php

declare(strict_types=1);

namespace App\Workflows;

use App\Models\MealPlan;
use App\Models\User;
use Workflow\Activity;

final class CreateMealPlanActivity extends Activity
{
    public $tries = 3;

    public $timeout = 30;

    /**
     * Create an empty meal plan record that will be populated incrementally.
     */
    public function execute(User $user, int $totalDays): MealPlan
    {
        $mealPlanType = GenerateMealPlanWorkflow::getMealPlanType($totalDays);

        // Delete old meal plans of the same type
        $user->mealPlans()
            ->where('type', $mealPlanType)
            ->delete();

        /** @var MealPlan $mealPlan */
        $mealPlan = $user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => "{$totalDays}-Day Personalized Meal Plan",
            'description' => 'AI-generated meal plan tailored to your nutritional needs and preferences.',
            'duration_days' => $totalDays,
            'target_daily_calories' => null,
            'macronutrient_ratios' => null,
            'metadata' => [
                'generated_at' => now()->toIso8601String(),
                'generation_method' => 'workflow',
                'status' => 'generating',
                'days_completed' => 0,
            ],
        ]);

        return $mealPlan;
    }
}
