<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use Closure;

final class BuildPreviousDaysContextStep
{
    /**
     * @param  Closure(MealPlanDayContext): MealPlanDayContext  $next
     */
    public function handle(MealPlanDayContext $context, Closure $next): MealPlanDayContext
    {
        $previousMeals = $context->mealPlan->meals()
            ->where('day_number', '<', $context->dayNumber)
            ->orderBy('day_number')
            ->get()
            ->groupBy('day_number');

        foreach ($previousMeals as $dayNumber => $meals) {
            /** @var array<string> $mealNames */
            $mealNames = $meals->pluck('name')->toArray();
            $context->previousDaysContext->addDayMeals((int) $dayNumber, $mealNames);
        }

        return $next($context);
    }
}
