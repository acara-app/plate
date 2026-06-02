<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use App\Enums\MealPlanGenerationStatus;
use Closure;

final class FinalizeMealPlanDayStep
{
    /**
     * @param  Closure(MealPlanDayContext): MealPlanDayContext  $next
     */
    public function handle(MealPlanDayContext $context, Closure $next): MealPlanDayContext
    {
        $mealPlan = $context->mealPlan;

        $daysCompleted = max(
            $mealPlan->metadata['days_completed'] ?? 0,
            $context->dayNumber,
        );

        $isCompleted = $daysCompleted >= $context->totalDays;

        $metadata = $mealPlan->metadata ?? [];
        unset($metadata[sprintf('day_%d_status', $context->dayNumber)]);

        $mealPlan->update([
            'metadata' => array_merge($metadata, [
                'days_completed' => $daysCompleted,
                'status' => $isCompleted
                    ? MealPlanGenerationStatus::Completed->value
                    : MealPlanGenerationStatus::Pending->value,
                sprintf('day_%d_generated_at', $context->dayNumber) => now()->toIso8601String(),
            ]),
        ]);

        return $next($context);
    }
}
