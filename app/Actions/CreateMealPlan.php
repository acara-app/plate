<?php

declare(strict_types=1);

namespace App\Actions;

use App\Enums\DietType;
use App\Enums\MealPlanGenerationStatus;
use App\Enums\MealPlanType;
use App\Models\MealPlan;
use App\Models\User;
use App\Utilities\LanguageUtil;

final readonly class CreateMealPlan
{
    public function handle(User $user, int $totalDays = 7, ?DietType $dietType = null): MealPlan
    {
        $mealPlanType = MealPlanType::fromDays($totalDays);
        $locale = LanguageUtil::resolve($user->locale)['code'];

        $name = $dietType instanceof DietType
            ? __('common.meal_plans.name_with_diet', [
                'days' => $totalDays,
                'diet' => __('common.meal_plans.diet_short.'.$dietType->value, [], $locale),
            ], $locale)
            : __('common.meal_plans.name_default', ['days' => $totalDays], $locale);

        /** @var MealPlan $mealPlan */
        $mealPlan = $user->mealPlans()->create([
            'type' => $mealPlanType,
            'name' => $name,
            'description' => __('common.meal_plans.default_description', [], $locale),
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
}
