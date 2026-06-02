<?php

declare(strict_types=1);

namespace App\Workflows\MealPlanPipeline;

use App\Workflows\MealPlanDayGeneratorActivity;
use Closure;

final readonly class GenerateDayMealsStep
{
    public function __construct(private MealPlanDayGeneratorActivity $generator) {}

    /**
     * @param  Closure(MealPlanDayContext): MealPlanDayContext  $next
     */
    public function handle(MealPlanDayContext $context, Closure $next): MealPlanDayContext
    {
        $context->dayMeals = $this->generator->handle(
            $context->user,
            $context->dayNumber,
            $context->totalDays,
            $context->previousDaysContext,
            $context->glucoseAnalysis,
            $context->mealPlan,
            $context->dietType,
        );

        return $next($context);
    }
}
