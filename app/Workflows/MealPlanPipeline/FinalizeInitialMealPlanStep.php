<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use App\Enums\MealPlanGenerationStatus;
use Closure;

final class FinalizeInitialMealPlanStep
{
    /**
     * @param  Closure(MealPlanDayContext): MealPlanDayContext  $next
     */
    public function handle(MealPlanDayContext $context, Closure $next): MealPlanDayContext
    {
        $context->mealPlan->update([
            'metadata' => array_merge($context->mealPlan->metadata ?? [], [
                'days_completed' => 1,
                'status' => MealPlanGenerationStatus::Pending->value,
            ]),
        ]);

        return $next($context);
    }
}
